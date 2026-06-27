<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A reusable master list of amenities: create once, then tick them on each room
     * type. Backfilled from the amenities already used across room_types so nothing
     * is lost (the deploy runs migrate --force with no seeders, so it must live here).
     */
    public function up(): void
    {
        Schema::create('amenities', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Collect distinct amenity names from existing room types (amenities is a JSON array).
        $names = [];
        foreach (DB::table('room_types')->pluck('amenities') as $json) {
            if (!$json) {
                continue;
            }
            $arr = json_decode($json, true);
            if (is_array($arr)) {
                foreach ($arr as $a) {
                    $a = trim((string) $a);
                    if ($a !== '') {
                        $names[$a] = true;
                    }
                }
            }
        }

        $i = 0;
        foreach (array_keys($names) as $name) {
            DB::table('amenities')->insert([
                'name' => $name,
                'sort_order' => $i++,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('amenities');
    }
};
