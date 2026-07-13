<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\GuestDocument;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GuestDocumentAiTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    private function guest(string $email = 'guest@example.test'): Guest
    {
        return Guest::create([
            'first_name' => 'Old',
            'last_name' => 'Name',
            'email' => $email,
        ]);
    }

    private function upload(User $admin, Guest $guest): GuestDocument
    {
        $this->actingAs($admin)->post(route('guests.documents.store', $guest), [
            'type' => 'passport',
            'file' => UploadedFile::fake()->create('passport.pdf', 100, 'application/pdf'),
        ])->assertRedirect();

        $document = GuestDocument::where('guest_id', $guest->id)->firstOrFail();
        Storage::disk('local')->put($document->path, '%PDF-1.4 private test document');

        return $document;
    }

    private function fakeExtraction(): void
    {
        config()->set('services.gemini.key', 'secret-test-key');
        config()->set('services.gemini.model', 'gemini-test-model');
        config()->set('services.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta');

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [[
                        'functionCall' => [
                            'name' => 'submit_guest_identity',
                            'args' => [
                                'first_name' => ['value' => 'Marjus', 'confidence' => 99],
                                'last_name' => ['value' => 'Bushi', 'confidence' => 99],
                                'nationality' => ['value' => 'alb', 'confidence' => 98],
                                'date_of_birth' => ['value' => '1992-04-18', 'confidence' => 97],
                                'document_type' => ['value' => 'passport', 'confidence' => 99],
                                'document_number' => ['value' => 'BA1234567', 'confidence' => 96],
                            ],
                        ],
                    ]]],
                ]],
            ]),
        ]);
    }

    public function test_ai_extracts_but_does_not_change_guest_before_manual_approval(): void
    {
        Storage::fake('local');
        $admin = $this->admin();
        $guest = $this->guest();
        $document = $this->upload($admin, $guest);
        $this->fakeExtraction();

        $response = $this->actingAs($admin)->postJson(route('guests.documents.analyze', [$guest, $document]));

        $response->assertOk()
            ->assertJsonPath('document.ai_status', 'ready')
            ->assertJsonPath('document.ai_extraction.fields.document_number.value', 'BA1234567')
            ->assertJsonPath('document.ai_extraction.fields.nationality.value', 'ALB');

        $guest->refresh();
        $this->assertSame('Old', $guest->first_name);
        $this->assertNull($guest->document_number);

        Http::assertSent(function ($request) {
            $body = $request->data();

            return $request->hasHeader('x-goog-api-key', 'secret-test-key')
                && ! str_contains($request->url(), 'secret-test-key')
                && ($body['contents'][0]['parts'][1]['inlineData']['mimeType'] ?? null) === 'application/pdf'
                && ! empty($body['contents'][0]['parts'][1]['inlineData']['data']);
        });
    }

    public function test_staff_applies_only_selected_server_stored_fields(): void
    {
        Storage::fake('local');
        $admin = $this->admin();
        $guest = $this->guest();
        $document = $this->upload($admin, $guest);
        $this->fakeExtraction();
        $this->actingAs($admin)->postJson(route('guests.documents.analyze', [$guest, $document]))->assertOk();

        $this->actingAs($admin)->putJson(route('guests.documents.apply-ai', [$guest, $document]), [
            'fields' => ['date_of_birth', 'document_type', 'document_number'],
            'document_number' => 'TAMPERED-BROWSER-VALUE',
        ])->assertOk()
            ->assertJsonPath('guest.document_number', 'BA1234567')
            ->assertJsonPath('document.ai_status', 'reviewed');

        $guest->refresh();
        $document->refresh();
        $this->assertSame('Old', $guest->first_name, 'An unselected field must remain untouched.');
        $this->assertSame('BA1234567', $guest->document_number, 'The server-stored extraction wins over browser input.');
        $this->assertSame('1992-04-18', $guest->date_of_birth->toDateString());
        $this->assertSame($admin->id, $document->ai_reviewed_by);
        $this->assertSame('reviewed', $document->ai_status);
    }

    public function test_document_cannot_be_analyzed_for_another_guest(): void
    {
        Storage::fake('local');
        $admin = $this->admin();
        $guest = $this->guest();
        $otherGuest = $this->guest('other@example.test');
        $document = $this->upload($admin, $guest);
        $this->fakeExtraction();

        $this->actingAs($admin)
            ->postJson(route('guests.documents.analyze', [$otherGuest, $document]))
            ->assertNotFound();

        Http::assertNothingSent();
    }

    public function test_unconfigured_ai_returns_a_safe_error_without_changing_guest(): void
    {
        Storage::fake('local');
        config()->set('services.gemini.key', null);
        $admin = $this->admin();
        $guest = $this->guest();
        $document = $this->upload($admin, $guest);

        $this->actingAs($admin)
            ->postJson(route('guests.documents.analyze', [$guest, $document]))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Asistenti AI nuk është konfiguruar. Shto çelësin te Cilësimet → Asistenti AI.');

        $this->assertSame('failed', $document->fresh()->ai_status);
        $this->assertNull($guest->fresh()->document_number);
    }
}
