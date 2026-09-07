<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Seed categories. These are starting values only: the catalogue is not
     * camera-specific and categories are managed entirely from the admin.
     *
     * @var array<string, array{description: string, children: array<string, string>}>
     */
    private const TREE = [
        'Cameras' => [
            'description' => 'Fixed, dome and motorised cameras for the shop front, the warehouse and the perimeter.',
            'children' => [
                'IP Cameras' => 'Network cameras from 4MP to 8MP, powered and recorded over a single Ethernet run.',
                'Analogue Cameras' => 'HD-TVI and HD-CVI cameras on coaxial cable, for extending a system you already own.',
                'PTZ and Speed Domes' => 'Motorised pan, tilt and zoom cameras for covering a yard or a car park from one pole.',
            ],
        ],
        'Recorders and Storage' => [
            'description' => 'The recorders that hold the footage and the drives written to continuously for years.',
            'children' => [
                'Network Video Recorders' => '4 to 32 channel NVRs with built-in PoE switching for IP camera systems.',
                'Digital Video Recorders' => 'Coax DVRs for analogue systems, most of them able to take IP cameras alongside.',
                'Surveillance Drives' => 'Drives rated for continuous write. A desktop drive in a recorder is a failure waiting to happen.',
            ],
        ],
        'Alarms and Detection' => [
            'description' => 'Intruder panels, motion detectors, door contacts, sirens and smoke detection.',
            'children' => [],
        ],
        'Access Control' => [
            'description' => 'Fingerprint and card terminals, electric locks, exit buttons and video door intercoms.',
            'children' => [],
        ],
        'Cabling and Power' => [
            'description' => 'Cable, connectors, PoE injectors, power supplies and the surge protection that saves a recorder.',
            'children' => [],
        ],
        'Bundles' => [
            'description' => 'Cameras, recorder, drive and cable packaged together at a lower price than buying separately.',
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
