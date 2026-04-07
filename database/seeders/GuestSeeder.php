<?php

namespace Database\Seeders;

use App\Models\Guest;
use Illuminate\Database\Seeder;

class GuestSeeder extends Seeder
{
    public function run(): void
    {
        $guests = [
            [
                'first_name' => 'Arben', 'last_name' => 'Hoxha',
                'email' => 'arben.hoxha@gmail.com', 'phone' => '+355691234567',
                'document_type' => 'id_card', 'document_number' => 'I12345678',
                'nationality' => 'ALB', 'date_of_birth' => '1985-03-15',
                'preferences' => ['room_type' => 'Double', 'floor' => 'high', 'pillow' => 'firm'],
            ],
            [
                'first_name' => 'Elena', 'last_name' => 'Koci',
                'email' => 'elena.koci@outlook.com', 'phone' => '+355692345678',
                'document_type' => 'passport', 'document_number' => 'BA1234567',
                'nationality' => 'ALB', 'date_of_birth' => '1990-07-22',
                'preferences' => ['room_type' => 'Suite', 'quiet' => true],
            ],
            [
                'first_name' => 'Marco', 'last_name' => 'Rossi',
                'email' => 'marco.rossi@libero.it', 'phone' => '+393401234567',
                'document_type' => 'passport', 'document_number' => 'YA1234567',
                'nationality' => 'ITA', 'date_of_birth' => '1978-11-03',
                'preferences' => ['room_type' => 'Single'],
            ],
            [
                'first_name' => 'Anna', 'last_name' => 'Mueller',
                'email' => 'anna.mueller@web.de', 'phone' => '+491601234567',
                'document_type' => 'passport', 'document_number' => 'C1234567X',
                'nationality' => 'DEU', 'date_of_birth' => '1992-01-28',
                'preferences' => ['room_type' => 'Family', 'extra_bed' => true],
            ],
            [
                'first_name' => 'Besnik', 'last_name' => 'Dervishi',
                'email' => 'besnik.d@yahoo.com', 'phone' => '+355693456789',
                'document_type' => 'id_card', 'document_number' => 'I87654321',
                'nationality' => 'ALB', 'date_of_birth' => '1988-05-10',
            ],
            [
                'first_name' => 'Sarah', 'last_name' => 'Johnson',
                'email' => 'sarah.j@gmail.com', 'phone' => '+447911123456',
                'document_type' => 'passport', 'document_number' => '533456789',
                'nationality' => 'GBR', 'date_of_birth' => '1995-09-17',
                'preferences' => ['room_type' => 'Suite', 'late_checkout' => true],
            ],
            [
                'first_name' => 'Dritan', 'last_name' => 'Leka',
                'email' => 'dritan.leka@hotmail.com', 'phone' => '+355694567890',
                'document_type' => 'drivers_license', 'document_number' => 'AL12345',
                'nationality' => 'ALB', 'date_of_birth' => '1982-12-01',
            ],
            [
                'first_name' => 'Maria', 'last_name' => 'Popescu',
                'email' => 'maria.p@gmail.com', 'phone' => '+40721234567',
                'document_type' => 'passport', 'document_number' => '12345678',
                'nationality' => 'ROU', 'date_of_birth' => '1987-06-14',
                'preferences' => ['room_type' => 'Double', 'smoking' => false],
            ],
            [
                'first_name' => 'Luca', 'last_name' => 'Bianchi',
                'email' => 'luca.b@gmail.com', 'phone' => '+393481234567',
                'document_type' => 'id_card', 'document_number' => 'AY1234567',
                'nationality' => 'ITA', 'date_of_birth' => '1993-04-20',
            ],
            [
                'first_name' => 'Enkeleda', 'last_name' => 'Brahimi',
                'email' => 'enkeleda.b@gmail.com', 'phone' => '+355695678901',
                'document_type' => 'id_card', 'document_number' => 'I11223344',
                'nationality' => 'ALB', 'date_of_birth' => '1991-08-30',
                'preferences' => ['room_type' => 'Twin', 'early_checkin' => true],
            ],
        ];

        foreach ($guests as $guest) {
            Guest::firstOrCreate(
                ['email' => $guest['email']],
                $guest
            );
        }
    }
}
