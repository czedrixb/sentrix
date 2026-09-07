<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BrandSeeder extends Seeder
{
    /**
     * Starting brands. Fully managed from the admin thereafter.
     *
     * @var list<string>
     */
    private const BRANDS = [
        'Axis', 'Bosch', 'Dahua', 'Ezviz', 'Hanwha Vision', 'Hikvision', 'Imou',
        'Paradox', 'Reolink', 'Seagate', 'TP-Link VIGI', 'Uniview',
        'Western Digital', 'ZKTeco',
    ];

    public function run(): void
    {
        foreach (self::BRANDS as $index => $name) {
            Brand::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'is_active' => true, 'position' => $index + 1]
            );
        }
    }
}
