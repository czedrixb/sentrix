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
        'Brother', 'Canon', 'Duplo', 'Epson', 'Fuji', 'HP', 'Konica',
        'Kyocera', 'OKI', 'Pantum', 'Ricoh', 'Riso', 'Samsung', 'Xerox',
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
