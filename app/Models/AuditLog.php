<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'causer_id',
        'action',
        'subject_type',
        'subject_id',
        'properties',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function causer()
    {
        return $this->belongsTo(User::class, 'causer_id');
    }

    /**
     * Record one audit entry for a state/money-changing action.
     * Best-effort: a logging failure must never break the real action.
     */
    public static function record(string $action, ?Model $subject = null, array $properties = []): void
    {
        try {
            static::create([
                'causer_id' => auth()->id(),
                'action' => $action,
                'subject_type' => $subject ? $subject::class : null,
                'subject_id' => $subject?->getKey(),
                'properties' => $properties ?: null,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
