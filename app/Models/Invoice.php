<?php

namespace App\Models;

/**
 * A sales invoice (receivable): auto-created from a folio checkout, or manual
 * B2B (company/agency with NIPT). Serially numbered per year per tenant —
 * structure ready for fiscalization without implementing it.
 */
class Invoice extends TenantModel
{
    protected $fillable = [
        'number', 'guest_id', 'reservation_id', 'company_name', 'company_nipt',
        'company_address', 'issue_date', 'due_date', 'currency', 'total',
        'status', 'notes',
    ];

    protected $casts = [
        'issue_date' => 'date:Y-m-d',
        'due_date' => 'date:Y-m-d',
        'total' => 'decimal:2',
    ];

    public function guest()
    {
        return $this->belongsTo(Guest::class);
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function payments()
    {
        return $this->hasMany(FinancePayment::class);
    }

    public function paidBase(): float
    {
        return round((float) $this->payments()->sum('amount_base'), 2);
    }

    public function remainingBase(): float
    {
        return max(0, round((float) $this->total - $this->paidBase(), 2));
    }

    public function refreshStatus(): void
    {
        $paid = $this->paidBase();
        $this->status = $paid <= 0 ? 'open' : ($paid + 0.005 >= (float) $this->total ? 'paid' : 'partial');
        $this->saveQuietly();
    }

    /** Next serial "YYYY-NNNNNN" for the year — call inside a transaction. */
    public static function nextNumber(?\DateTimeInterface $date = null): string
    {
        $year = ($date ?: now())->format('Y');
        $last = static::where('number', 'like', "{$year}-%")->lockForUpdate()->orderByDesc('number')->value('number');
        $seq = $last ? ((int) substr($last, 5)) + 1 : 1;

        return sprintf('%s-%06d', $year, $seq);
    }
}
