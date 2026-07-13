<?php

namespace App\Observers;

use App\Models\Payment;
use App\Services\FinanceLedger;
use Illuminate\Support\Facades\Log;

/**
 * Mirrors folio payments into the finance ledger. STRICTLY best-effort: a
 * finance failure must NEVER break a checkout or a payment write — it is
 * reported and the backfill command heals the gap later.
 */
class PaymentObserver
{
    public function created(Payment $payment): void
    {
        $this->sync($payment);
    }

    public function updated(Payment $payment): void
    {
        $this->sync($payment); // covers voiding: the ledger row is removed
    }

    public function deleted(Payment $payment): void
    {
        try {
            app(FinanceLedger::class)->removeFor($payment);
        } catch (\Throwable $e) {
            Log::warning('Finance ledger cleanup failed: '.$e->getMessage());
        }
    }

    private function sync(Payment $payment): void
    {
        try {
            app(FinanceLedger::class)->recordFolioPayment($payment);
        } catch (\Throwable $e) {
            report($e); // surface it, but never break the folio write
        }
    }
}
