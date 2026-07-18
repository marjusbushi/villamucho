<?php

namespace App\Models;

class PosTable extends TenantModel
{
    protected $fillable = ['number', 'name', 'area', 'seats', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return [
            'seats' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
