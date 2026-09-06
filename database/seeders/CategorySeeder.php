<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Seed categories. These are starting values only: the catalogue is not
     * printer-specific and categories are managed entirely from the admin.
     *
     * @var array<string, array{description: string, children: array<string, string>}>
     */
    private const TREE = [
        'Equipment' => [
            'description' => 'Printers, scanners and copiers for the home office, the school and the production floor.',
            'children' => [
                'Printers' => 'Ink tank, laser and multifunction printers from Epson, Canon, Brother, HP, Pantum and Kyocera.',
                'Scanners' => 'Sheet-fed and flatbed document scanners for records, permits and back-file conversion.',
                'Copiers' => 'Office copiers, production multifunction machines and stencil duplicators for high-volume printing.',
            ],
        ],
        'Consumables' => [
            'description' => 'The ink, toner, paper and media that keep a machine running between service calls.',
            'children' => [
                'Ink and Toner' => 'Genuine ink bottles, cartridges and toner units matched to the machines we carry.',
                'Paper and Media' => 'Bond paper, photo and sticker media, carbonless forms and duplicator masters.',
            ],
        ],
        'Spare Parts' => [
            'description' => 'Fusers, rollers, printheads and drum units for in-warranty and out-of-warranty repair.',
            'children' => [],
        ],
        'Accessories' => [
            'description' => 'Cables, stands, power protection and cleaning kits to finish an installation properly.',
            'children' => [],
        ],
        'Bundles' => [
            'description' => 'Machine, consumables and paper packaged together at a lower price than buying separately.',
            'children' => [],
        ],
    ];

    public function run(): void
    {
        $position = 0;

        foreach (self::TREE as $parentName => $parentData) {
            $position++;

            $parent = Category::query()->updateOrCreate(
                ['slug' => Str::slug($parentName)],
                [
                    'name' => $parentName,
                    'parent_id' => null,
                    'description' => $parentData['description'],
                    'is_active' => true,
                    'position' => $position,
                ]
            );

            $childPosition = 0;

            foreach ($parentData['children'] as $childName => $childDescription) {
                $childPosition++;

                Category::query()->updateOrCreate(
                    ['slug' => Str::slug($childName)],
                    [
                        'name' => $childName,
                        'parent_id' => $parent->id,
                        'description' => $childDescription,
                        'is_active' => true,
                        'position' => $childPosition,
                    ]
                );
            }
        }
    }
}
