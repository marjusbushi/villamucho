<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChannelSyncLog extends Model
{
    public $timestamps = false; // append-only; only created_at

    protected $fillable = [
        'channel',
        'direction',
        'action',
        'room_type_id',
        'reservation_id',
        'status',
        'request',
        'response',
        'error',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'request' => 'array',
            'response' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /** Best-effort: a logging failure must never break the sync it is recording. */
    public static function record(array $data): void
    {
        try {
            static::create(array_merge(['channel' => 'beds24', 'created_at' => now()], $data));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
