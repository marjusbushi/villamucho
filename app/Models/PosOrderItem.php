<?php

namespace App\Models;

class PosOrderItem extends TenantModel
{
    protected $fillable = ['pos_order_id', 'pos_order_round_id', 'menu_item_id', 'quantity', 'unit_price', 'total_price'];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
        ];
    }

    public function order()
    {
        return $this->belongsTo(PosOrder::class, 'pos_order_id');
    }

    public function menuItem()
    {
        return $this->belongsTo(MenuItem::class);
    }

    public function round()
    {
        return $this->belongsTo(PosOrderRound::class, 'pos_order_round_id');
    }
}
