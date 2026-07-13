<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\FinanceAccount;
use App\Models\FinancePayment;
use App\Models\Invoice;
use App\Models\Setting;
use App\Services\CurrencyRates;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Finance module (Phase 1): Paneli + Arka & Banka + Pagesat.
 * The ledger is fed automatically (FinanceLedger); these screens read it and
 * accept the few MANUAL movements: ad-hoc payments and transfers.
 * Bank accounts are visible only with view_bank_accounts — everywhere.
 */
class FinanceController extends Controller
{
    public function index(Request $request): Response
    {
        FinanceAccount::ensureDefaults();
        $accounts = $this->visibleAccounts($request);
        $today = CarbonImmutable::today();

        // 14-day cash-flow in base EUR (transfers move money between our own
        // pockets — they are neither income nor spend, so they stay out).
        $from = $today->subDays(13);
        $rows = FinancePayment::where('paid_at', '>=', $from->startOfDay())
            ->whereIn('direction', ['in', 'out'])
            ->get(['direction', 'amount_base', 'paid_at'])
            ->groupBy(fn ($p) => $p->paid_at->toDateString());
        $cashflow = [];
        for ($d = $from; $d->lte($today); $d = $d->addDay()) {
            $ofDay = $rows->get($d->toDateString(), collect());
            $cashflow[] = [
                'date' => $d->toDateString(),
                'in' => round((float) $ofDay->where('direction', 'in')->sum('amount_base'), 2),
                'out' => round((float) $ofDay->where('direction', 'out')->sum('amount_base'), 2),
            ];
        }

        $openBills = Bill::with('supplier:id,name')->where('status', '!=', 'paid')->get();
        $openInvoices = Invoice::where('status', '!=', 'paid')->get();

        $alerts = $openBills
            ->filter(fn ($b) => $b->due_date && $b->due_date->lte($today))
            ->map(fn ($b) => [
                'label' => 'Bill '.($b->number ?: '#'.$b->id).' — '.($b->supplier?->name ?? ''),
                'amount' => (float) $b->remainingBase(),
                'severity' => 'error',
                'badge' => $b->due_date->isToday() ? 'Afati SOT' : 'Vonesë '.$b->due_date->diffInDays($today).' ditë',
            ])->values();

        $arkaLimit = (float) Setting::get('financial.arka_limit', 1000);
        $cash = $accounts->firstWhere('type', 'cash');
        if ($cash && $cash['balance'] > $arkaLimit) {
            $alerts->push([
                'label' => 'Arka mbi limitin e sigurisë (€'.number_format($arkaLimit, 0).') — bëj depozitim në bankë',
                'amount' => $cash['balance'],
                'severity' => 'warning',
                'badge' => 'Sugjerim',
            ]);
        }

        return Inertia::render('Finance/Index', array_merge($this->shared($request), [
            'accounts' => $accounts,
            'receivables' => ['total' => round((float) $openInvoices->sum(fn ($i) => $i->remainingBase()), 2), 'count' => $openInvoices->count()],
            'payables' => ['total' => round((float) $openBills->sum(fn ($b) => $b->remainingBase()), 2), 'count' => $openBills->count()],
            'cashflow' => $cashflow,
            'alerts' => $alerts,
            'latest' => $this->paymentRows(FinancePayment::with(['account:id,name', 'counterAccount:id,name'])
                ->latest('paid_at')->latest('id')->limit(8)->get()),
        ]));
    }

    public function accounts(Request $request): Response
    {
        FinanceAccount::ensureDefaults();
        $accounts = $this->visibleAccounts($request);
        $selectedId = (int) $request->input('account_id') ?: ($accounts->first()['id'] ?? null);
        // Never leak a hidden (bank) ledger through a hand-typed account_id.
        if (! $accounts->firstWhere('id', $selectedId)) {
            abort(403);
        }

        // Ledger with a running balance, computed oldest-first then shown newest-first.
        $rows = FinancePayment::with(['account:id,name', 'counterAccount:id,name'])
            ->where(fn ($q) => $q->where('account_id', $selectedId)->orWhere('counter_account_id', $selectedId))
            ->orderBy('paid_at')->orderBy('id')
            ->get();
        $running = 0.0;
        $ledger = $rows->map(function (FinancePayment $p) use (&$running, $selectedId) {
            $delta = match (true) {
                $p->direction === 'in' && $p->account_id === $selectedId => (float) $p->amount,
                $p->direction === 'out' && $p->account_id === $selectedId => -(float) $p->amount,
                $p->direction === 'transfer' && $p->account_id === $selectedId => -(float) $p->amount,
                default => (float) $p->amount, // transfer INTO this account
            };
            $running = round($running + $delta, 2);

            return array_merge($this->paymentRow($p), ['delta' => $delta, 'balance' => $running]);
        })->reverse()->values();

        return Inertia::render('Finance/Accounts', array_merge($this->shared($request), [
            'accounts' => $accounts,
            'selectedId' => $selectedId,
            'ledger' => $ledger,
        ]));
    }

    public function payments(Request $request): Response
    {
        FinanceAccount::ensureDefaults();
        $direction = $request->input('direction');
        $q = FinancePayment::with(['account:id,name', 'counterAccount:id,name'])
            ->latest('paid_at')->latest('id');
        if (in_array($direction, ['in', 'out', 'transfer'], true)) {
            $q->where('direction', $direction);
        }
        if ($request->input('source') === 'manual') {
            $q->where('source', 'manual');
        }
        if (! $request->user()->can('view_bank_accounts')) {
            $bankIds = FinanceAccount::where('type', 'bank')->pluck('id');
            $q->whereNotIn('account_id', $bankIds)
                ->where(fn ($w) => $w->whereNull('counter_account_id')->orWhereNotIn('counter_account_id', $bankIds));
        }

        return Inertia::render('Finance/Payments', array_merge($this->shared($request), [
            'accounts' => $this->visibleAccounts($request),
            'filters' => ['direction' => $direction, 'source' => $request->input('source')],
            'payments' => $q->paginate(30)->withQueryString()->through(fn ($p) => $this->paymentRow($p)),
        ]));
    }

    /** Manual movement: an ad-hoc arkëtim (in) or expense (out). */
    public function storePayment(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'direction' => ['required', 'in:in,out'],
            'account_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999'],
            'currency' => ['required', 'in:EUR,ALL'],
            'fx_rate' => ['required_if:currency,ALL', 'nullable', 'numeric', 'min:1'],
            'method' => ['required', 'in:cash,card,bank'],
            'description' => ['required', 'string', 'max:300'],
            'paid_at' => ['nullable', 'date'],
        ]);

        // An outgoing manual payment is spending money — owner/manager only.
        if ($data['direction'] === 'out' && ! $request->user()->can('pay_bills')) {
            abort(403);
        }
        $account = $this->visibleAccounts($request)->firstWhere('id', (int) $data['account_id']);
        if (! $account) {
            abort(403); // never move money on an account this user cannot see
        }

        FinancePayment::create([
            'direction' => $data['direction'],
            'account_id' => $account['id'],
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'fx_rate' => $data['currency'] === 'ALL' ? $data['fx_rate'] : null,
            'method' => $data['method'],
            'source' => 'manual',
            'description' => $data['description'],
            'paid_at' => $data['paid_at'] ?? now(),
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', $data['direction'] === 'in' ? 'Arkëtimi u regjistrua.' : 'Pagesa u regjistrua.');
    }

    /** Transfer between two SAME-currency accounts — one atomic ledger row. */
    public function storeTransfer(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'from_account_id' => ['required', 'integer', 'different:to_account_id'],
            'to_account_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999'],
            'description' => ['nullable', 'string', 'max:300'],
        ]);

        $visible = $this->visibleAccounts($request);
        $from = $visible->firstWhere('id', (int) $data['from_account_id']);
        $to = $visible->firstWhere('id', (int) $data['to_account_id']);
        if (! $from || ! $to) {
            abort(403);
        }
        if ($from['currency'] !== $to['currency']) {
            return back()->with('error', 'Transferta lejohet vetëm mes llogarive me të njëjtën monedhë (Faza 1).');
        }

        DB::transaction(function () use ($data, $from, $to, $request) {
            FinancePayment::create([
                'direction' => 'transfer',
                'account_id' => $from['id'],
                'counter_account_id' => $to['id'],
                'amount' => $data['amount'],
                'currency' => $from['currency'],
                'fx_rate' => null,
                'method' => $from['type'] === 'cash' ? 'cash' : 'bank',
                'source' => 'manual',
                'description' => ($data['description'] ?? null) ?: "Transfertë {$from['name']} → {$to['name']}",
                'paid_at' => now(),
                'created_by' => $request->user()->id,
            ]);
        });

        return back()->with('success', 'Transferta u krye.');
    }

    // -- helpers ------------------------------------------------------------

    /** Active accounts with balances; banks hidden without view_bank_accounts. */
    protected function visibleAccounts(Request $request)
    {
        $q = FinanceAccount::where('is_active', true)->orderBy('id');
        if (! $request->user()->can('view_bank_accounts')) {
            $q->where('type', 'cash');
        }

        return $q->get()->map(fn (FinanceAccount $a) => [
            'id' => $a->id,
            'name' => $a->name,
            'type' => $a->type,
            'currency' => $a->currency,
            'iban' => $a->iban,
            'balance' => $a->balance(),
        ])->values();
    }

    protected function shared(Request $request): array
    {
        return [
            'baseCurrency' => 'EUR',
            'fxRate' => CurrencyRates::rate('ALL'),
            'fxUpdatedAt' => CurrencyRates::updatedAt(),
            'can' => [
                'createPayment' => $request->user()->can('create_payment'),
                'payBills' => $request->user()->can('pay_bills'),
                'transfers' => $request->user()->can('manage_transfers'),
                'bank' => $request->user()->can('view_bank_accounts'),
            ],
        ];
    }

    protected function paymentRows($payments)
    {
        return $payments->map(fn ($p) => $this->paymentRow($p))->values();
    }

    protected function paymentRow(FinancePayment $p): array
    {
        return [
            'id' => $p->id,
            'direction' => $p->direction,
            'account' => $p->account?->name,
            'counter_account' => $p->counterAccount?->name,
            'amount' => (float) $p->amount,
            'currency' => $p->currency,
            'amount_base' => (float) $p->amount_base,
            'method' => $p->method,
            'source' => $p->source,
            'description' => $p->description,
            'paid_at' => $p->paid_at->toDateTimeString(),
        ];
    }
}
