<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomInventorySnapshot extends Model
{
    protected $fillable = [
        'snapshot_date',
        'total_rooms',
        'out_of_order',
        'available',
    ];

    protected function casts(): array
    {
        return [
            'snapshot_date' => 'date',
            'total_rooms' => 'integer',
            'out_of_order' => 'integer',
            'available' => 'integer',
        ];
    }
}
