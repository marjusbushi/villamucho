<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FolioItem extends Model
{
    protected $fillable = [
        'reservation_id',
        'description',
        'amount',
        'type',
        'charge_date',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'charge_date' => 'date',
        ];
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }
}
