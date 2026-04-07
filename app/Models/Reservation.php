<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reservation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'room_id',
        'guest_id',
        'created_by',
        'check_in_date',
        'check_out_date',
        'status',
        'total_amount',
        'adults',
        'children',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'check_in_date' => 'date',
            'check_out_date' => 'date',
            'total_amount' => 'decimal:2',
        ];
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function guest()
    {
        return $this->belongsTo(Guest::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function folioItems()
    {
        return $this->hasMany(FolioItem::class);
    }

    public function getNightsAttribute(): int
    {
        return $this->check_in_date->diffInDays($this->check_out_date);
    }

    /**
     * Check if a room is available for the given date range, excluding this reservation.
     */
    public static function isRoomAvailable(int $roomId, string $checkIn, string $checkOut, ?int $excludeId = null): bool
    {
        $query = static::where('room_id', $roomId)
            ->whereNotIn('status', ['cancelled', 'checked_out'])
            ->where('check_in_date', '<', $checkOut)
            ->where('check_out_date', '>', $checkIn);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return !$query->exists();
    }
}
