<?php

namespace App\Services;

use App\Models\GuestDocument;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class GuestDocumentAiExtractor
{
    private const MAX_BYTES = 10 * 1024 * 1024;

    private const SUPPORTED_MIMES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    /** @return array{fields: array<string, array{value: ?string, confidence: int}>} */
    public function extract(GuestDocument $document): array
    {
        $client = app(GeminiClient::class);
        if (! $client->configured()) {
            throw new RuntimeException('Asistenti AI nuk është konfiguruar. Shto çelësin te Cilësimet → Asistenti AI.');
        }

        $mime = strtolower((string) $document->mime);
        if (! in_array($mime, self::SUPPORTED_MIMES, true)) {
            throw new RuntimeException('AI mund të lexojë vetëm dokumente JPG, PNG, WEBP ose PDF.');
        }

        if ($document->size > self::MAX_BYTES) {
            throw new RuntimeException('Dokumenti është shumë i madh për analizën AI. Maksimumi është 10 MB.');
        }

        $disk = Storage::disk('local');
        if (! $disk->exists($document->path)) {
            throw new RuntimeException('Skedari privat i dokumentit nuk u gjet.');
        }

        $result = $client->structuredWithInlineData(
            'You extract identity data from passports, identity cards, and driving licences. Never guess. Return null when a field is absent or unreadable. Dates must use YYYY-MM-DD. Nationality must use an ISO 3166-1 alpha-3 code. Confidence is an integer from 0 to 100.',
            "Read this private identity document. The staff selected the upload type '{$document->type}'. Extract only the requested identity fields.",
            $disk->get($document->path),
            $mime,
            $this->tool(),
            'submit_guest_identity',
        );

        return ['fields' => $this->normalize($result)];
    }

    public function model(): string
    {
        return app(GeminiClient::class)->model();
    }

    /** @return array<string,mixed> */
    private function tool(): array
    {
        $field = fn (array $valueSchema): array => [
            'type' => 'object',
            'properties' => [
                'value' => $valueSchema + ['nullable' => true],
                'confidence' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 100],
            ],
            'required' => ['value', 'confidence'],
        ];

        return [
            'name' => 'submit_guest_identity',
            'description' => 'Return identity fields read directly from the supplied document.',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'first_name' => $field(['type' => 'string']),
                    'last_name' => $field(['type' => 'string']),
                    'nationality' => $field(['type' => 'string']),
                    'date_of_birth' => $field(['type' => 'string']),
                    'document_type' => $field(['type' => 'string', 'enum' => ['passport', 'id_card', 'drivers_license']]),
                    'document_number' => $field(['type' => 'string']),
                ],
                'required' => ['first_name', 'last_name', 'nationality', 'date_of_birth', 'document_type', 'document_number'],
            ],
        ];
    }

    /** @return array<string, array{value: ?string, confidence: int}> */
    private function normalize(array $result): array
    {
        $fields = [];
        foreach (['first_name', 'last_name', 'nationality', 'date_of_birth', 'document_type', 'document_number'] as $key) {
            $item = is_array($result[$key] ?? null) ? $result[$key] : [];
            $value = isset($item['value']) ? trim((string) $item['value']) : null;
            $value = $value === '' ? null : $value;

            if ($key === 'nationality' && $value !== null) {
                $value = preg_match('/^[A-Za-z]{3}$/', $value) ? strtoupper($value) : null;
            }
            if ($key === 'date_of_birth' && $value !== null && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                $value = null;
            }
            if ($key === 'document_type' && ! in_array($value, ['passport', 'id_card', 'drivers_license'], true)) {
                $value = null;
            }

            $fields[$key] = [
                'value' => $value,
                'confidence' => max(0, min(100, (int) ($item['confidence'] ?? 0))),
            ];
        }

        return $fields;
    }
}
