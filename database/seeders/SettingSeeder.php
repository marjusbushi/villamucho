<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // Hotel Info
            ['group' => 'hotel', 'key' => 'name', 'value' => 'Hotel Demo', 'type' => 'text'],
            ['group' => 'hotel', 'key' => 'address', 'value' => 'Rruga e Durresit 100, Tirane', 'type' => 'text'],
            ['group' => 'hotel', 'key' => 'phone', 'value' => '+355 4 234 5678', 'type' => 'text'],
            ['group' => 'hotel', 'key' => 'email', 'value' => 'info@hoteldemo.al', 'type' => 'text'],
            ['group' => 'hotel', 'key' => 'timezone', 'value' => 'Europe/Tirane', 'type' => 'text'],
            ['group' => 'hotel', 'key' => 'currency', 'value' => 'EUR', 'type' => 'text'],
            ['group' => 'hotel', 'key' => 'check_in_time', 'value' => '14:00', 'type' => 'text'],
            ['group' => 'hotel', 'key' => 'check_out_time', 'value' => '11:00', 'type' => 'text'],
            ['group' => 'hotel', 'key' => 'logo', 'value' => null, 'type' => 'image'],

            // Financial
            ['group' => 'financial', 'key' => 'tax_rate', 'value' => '20', 'type' => 'number'],
            ['group' => 'financial', 'key' => 'payment_methods', 'value' => json_encode(['cash', 'card', 'room_charge']), 'type' => 'json'],
            ['group' => 'financial', 'key' => 'default_currency_symbol', 'value' => '€', 'type' => 'text'],
            ['group' => 'financial', 'key' => 'folio_categories', 'value' => json_encode(['room', 'restaurant', 'bar', 'minibar', 'extra', 'tax', 'discount']), 'type' => 'json'],

            // Housekeeping
            ['group' => 'housekeeping', 'key' => 'task_types', 'value' => json_encode(['checkout_clean', 'stayover_clean', 'deep_clean', 'inspection']), 'type' => 'json'],
            ['group' => 'housekeeping', 'key' => 'auto_create_on_checkout', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'housekeeping', 'key' => 'default_priority', 'value' => 'normal', 'type' => 'text'],
        ];

        foreach ($settings as $setting) {
            Setting::firstOrCreate(
                ['group' => $setting['group'], 'key' => $setting['key']],
                ['value' => $setting['value'], 'type' => $setting['type']]
            );
        }
    }
}
