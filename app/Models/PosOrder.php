<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosOrder extends Model
{
    protected $fillable = [
        'reservation_id',
        'table_number',
        'status',
        'payment_method',
        'total_amount',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
        ];
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(PosOrderItem::class);
    }

    public function recalculateTotal(): void
    {
        $this->update([
            'total_amount' => $this->items()->sum('total_price'),
        ]);
    }
}
