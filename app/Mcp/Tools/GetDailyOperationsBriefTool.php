<?php

namespace App\Mcp\Tools;

use App\Models\Bill;
use App\Models\CleaningTask;
use App\Models\Invoice;
use App\Models\MaintenanceIssue;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use App\Services\BaseCurrency;
use App\Services\TenantBillingService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Carbon;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class GetDailyOperationsBriefTool extends LoraTool
{
    protected string $name = 'get-daily-operations-brief';

    protected string $description = 'Get a concise, read-only daily hotel brief: arrivals, departures, in-house stays, room status, and permitted housekeeping, maintenance, and finance attention items.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'date' => $schema->string()->description('Optional YYYY-MM-DD in the hotel timezone; defaults to today.'),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $user = $this->user($request);
        $data = $request->validate(['date' => ['nullable', 'date']]);
        $date = Carbon::parse($data['date'] ?? today()->toDateString())->startOfDay();
        $brief = [
            'date' => $date->toDateString(),
            'currency' => BaseCurrency::code(),
            'available_sections' => [],
            'links' => [],
        ];

        if ($this->enabled('reservations_enabled') && $this->allowed($user, 'view_reservations')) {
            $arrivals = Reservation::query()->with(['guest:id,first_name,last_name', 'room:id,room_number'])
                ->whereDate('check_in_date', $date)->where('status', '!=', 'cancelled')
                ->orderBy('eta')->limit(30)->get();
            $departures = Reservation::query()->with(['guest:id,first_name,last_name', 'room:id,room_number'])
                ->whereDate('check_out_date', $date)->where('status', '!=', 'cancelled')
                ->orderBy('etd')->limit(30)->get();
            $inHouse = Reservation::query()->where('status', 'checked_in')
                ->whereDate('check_in_date', '<=', $date)->whereDate('check_out_date', '>', $date)->count();

            $brief['available_sections'][] = 'reservations';
            $brief['reservations'] = [
                'arrivals_count' => $arrivals->count(),
                'departures_count' => $departures->count(),
                'in_house_count' => $inHouse,
                'arrivals' => $arrivals->map(fn (Reservation $reservation) => $this->stayRow($reservation, 'arrival'))->values()->all(),
                'departures' => $departures->map(fn (Reservation $reservation) => $this->stayRow($reservation, 'departure'))->values()->all(),
            ];
            $brief['links']['reservations'] = url('/pms/reservations?date='.$date->toDateString());
        }

        if ($this->allowed($user, 'view_rooms')) {
            $brief['available_sections'][] = 'rooms';
            $brief['rooms'] = Room::query()->selectRaw('status, count(*) as total')
                ->groupBy('status')->pluck('total', 'status')->map(fn ($total) => (int) $total)->all();
            $brief['links']['rooms'] = url('/pms/rooms');
        }

        if ($this->enabled('housekeeping_enabled', false)
            && $this->moduleEnabled(TenantBillingService::HOUSEKEEPING)
            && $this->allowed($user, 'view_housekeeping')) {
            $tasks = CleaningTask::query()->with('room:id,room_number')
                ->whereNull('archived_at')->whereNotIn('status', ['completed', 'inspected'])
                ->orderByRaw("case when priority = 'urgent' then 0 when priority = 'high' then 1 else 2 end")
                ->latest('id')->limit(10)->get();
            $brief['available_sections'][] = 'housekeeping';
            $brief['housekeeping'] = [
                'open_count' => CleaningTask::query()->whereNull('archived_at')
                    ->whereNotIn('status', ['completed', 'inspected'])->count(),
                'attention' => $tasks->map(fn (CleaningTask $task) => [
                    'id' => $task->id,
                    'room' => $task->room?->room_number,
                    'type' => $task->type,
                    'status' => $task->status,
                    'priority' => $task->priority,
                    'href' => url('/pms/housekeeping?task_id='.$task->id),
                ])->values()->all(),
            ];
            $brief['links']['housekeeping'] = url('/pms/housekeeping');
        }

        if ($this->enabled('maintenance_enabled', false) && $this->allowed($user, 'view_maintenance')) {
            $issues = MaintenanceIssue::query()->with('room:id,room_number')
                ->whereNotIn('status', ['resolved', 'verified', 'closed'])
                ->orderByRaw("case when priority = 'urgent' then 0 when priority = 'high' then 1 else 2 end")
                ->latest('id')->limit(10)->get();
            $brief['available_sections'][] = 'maintenance';
            $brief['maintenance'] = [
                'open_count' => MaintenanceIssue::query()
                    ->whereNotIn('status', ['resolved', 'verified', 'closed'])->count(),
                'attention' => $issues->map(fn (MaintenanceIssue $issue) => [
                    'id' => $issue->id,
                    'title' => $issue->title,
                    'room' => $issue->room?->room_number,
                    'status' => $issue->status,
                    'priority' => $issue->priority,
                    'room_blocked' => (bool) $issue->room_blocked,
                    'href' => url('/pms/maintenance?issue_id='.$issue->id),
                ])->values()->all(),
            ];
            $brief['links']['maintenance'] = url('/pms/maintenance');
        }

        if ($this->enabled('finance_enabled', false)
            && $this->moduleEnabled(TenantBillingService::FINANCE)
            && $this->allowed($user, 'view_finance')) {
            $overdueInvoices = Invoice::query()->whereIn('status', ['open', 'partial'])
                ->whereDate('due_date', '<', $date)->with('payments')->get();
            $overdueBills = Bill::query()->whereIn('status', ['open', 'partial'])
                ->whereDate('due_date', '<', $date)->with('payments')->get();
            $brief['available_sections'][] = 'finance';
            $brief['finance'] = [
                'overdue_sales_invoices' => $overdueInvoices->count(),
                'receivable_overdue_base' => round($overdueInvoices->sum(fn (Invoice $invoice) => $invoice->remainingBase()), 2),
                'overdue_purchase_bills' => $overdueBills->count(),
                'payable_overdue_base' => round($overdueBills->sum(fn (Bill $bill) => $bill->remainingBase()), 2),
            ];
            $brief['links']['finance'] = url('/pms/finance');
        }

        return Response::structured($brief);
    }

    private function allowed(User $user, string $permission): bool
    {
        return $user->is_super_admin || $user->can($permission);
    }

    private function stayRow(Reservation $reservation, string $kind): array
    {
        return [
            'id' => $reservation->id,
            'guest' => $reservation->guest?->full_name,
            'room' => $reservation->room?->room_number,
            'status' => $reservation->status,
            'time' => $kind === 'arrival' ? $reservation->eta : $reservation->etd,
            'href' => url('/pms/reservations/'.$reservation->id),
        ];
    }
}
