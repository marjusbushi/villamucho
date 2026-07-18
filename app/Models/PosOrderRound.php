<?php

namespace App\Models;

class PosOrderRound extends TenantModel
{
    protected $fillable = [
        'pos_order_id', 'sequence', 'status', 'destination',
        'sent_at', 'printed_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'sent_at' => 'datetime',
            'printed_at' => 'datetime',
        ];
    }

    public function order()
    {
        return $this->belongsTo(PosOrder::class, 'pos_order_id');
    }

    public function items()
    {
        return $this->hasMany(PosOrderItem::class, 'pos_order_round_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
