<?php

namespace App\Observers;

use App\Models\PosShift;
use App\Services\FinanceLedger;

/** Mirrors shift closes into the Arka ledger — best-effort, never blocking. */
class PosShiftObserver
{
    public function updated(PosShift $shift): void
    {
        try {
            app(FinanceLedger::class)->recordShiftClose($shift);
        } catch (\Throwable $e) {
            report($e); // a finance failure must never block closing a shift
        }
    }
}
