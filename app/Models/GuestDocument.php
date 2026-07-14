<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuestDocument extends TenantModel
{
    protected $fillable = [
        'guest_id',
        'type',
        'original_name',
        'path',
        'mime',
        'size',
        'uploaded_by',
        'ai_status',
        'ai_extraction',
        'ai_model',
        'ai_error',
        'ai_extracted_at',
        'ai_reviewed_at',
        'ai_reviewed_by',
    ];

    protected function casts(): array
    {
        return [
            'ai_extraction' => 'array',
            'ai_extracted_at' => 'datetime',
            'ai_reviewed_at' => 'datetime',
        ];
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function aiReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ai_reviewed_by');
    }
}
