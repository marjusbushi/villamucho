<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends TenantModel
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'nipt', 'category', 'phone', 'email', 'address',
        'payment_terms_days', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function bills()
    {
        return $this->hasMany(Bill::class);
    }

    /** What we still owe this supplier, in base EUR (unpaid bill remainders). */
    public function openBalanceBase(): float
    {
        return round($this->bills()
            ->where('status', '!=', 'paid')
            ->get()
            ->sum(fn (Bill $b) => $b->remainingBase()), 2);
    }
}
