<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CleaningTask extends Model
{
    protected $fillable = [
        'room_id',
        'assigned_to',
        'type',
        'status',
        'priority',
        'notes',
        'issue_reported',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
