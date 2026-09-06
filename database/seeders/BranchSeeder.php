<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BranchSeeder extends Seeder
{
    /**
     * Starting branches. Branches are ordinary rows: adding one later is an
     * admin action, not a schema or code change.
     *
     * Landline numbers carry the real area code for their city so they read and
     * dial correctly; the subscriber digits are placeholders until the client
     * supplies their published lines.
     *
     * @var list<array{name: string, address: string, phone: string, sales_phone: string, support_phone: string, email: string, secondary_email: ?string, map_query: string, is_pickup_location?: bool, position: int}>
     */
    private const BRANCHES = [
        [
            'name' => 'Davao City',
            'address' => 'Km. 5 McArthur Highway, Matina, Davao City, Davao del Sur 8000',
            'phone' => '(082) 297 4180',
            'sales_phone' => '0917 812 4180',
            'support_phone' => '0917 812 4181',
            'email' => 'davao@kompra.ph',
            'secondary_email' => 'davao.service@kompra.ph',
            'map_query' => 'Matina, Davao City, Davao del Sur',
            'position' => 1,
        ],
        [
            'name' => 'Cebu City',
            'address' => 'Ground Floor, A. S. Fortuna Street, Mandaue, Cebu City, Cebu 6000',
            'phone' => '(032) 268 3355',
            'sales_phone' => '0917 703 3355',
            'support_phone' => '0917 703 3356',
            'email' => 'cebu@kompra.ph',
            'secondary_email' => 'cebu.service@kompra.ph',
            'map_query' => 'A. S. Fortuna Street, Mandaue City, Cebu',
            'position' => 2,
        ],
        [
            'name' => 'Iloilo City',
            'address' => 'Diversion Road, Mandurriao, Iloilo City, Iloilo 5000',
            'phone' => '(033) 328 6072',
            'sales_phone' => '0918 604 6072',
            'support_phone' => '0918 604 6073',
            'email' => 'iloilo@kompra.ph',
            'secondary_email' => null,
            'map_query' => 'Diversion Road, Mandurriao, Iloilo City',
            'position' => 3,
        ],
        [
            'name' => 'General Santos City',
            'address' => 'National Highway, Barangay Lagao, General Santos City, South Cotabato 9500',
            'phone' => '(083) 552 1147',
            'sales_phone' => '0917 555 1147',
            'support_phone' => '0917 555 1148',
            'email' => 'gensan@kompra.ph',
            'secondary_email' => null,
            'map_query' => 'Lagao, General Santos City, South Cotabato',
            'position' => 4,
        ],
        [
            'name' => 'Malaybalay City',
            'address' => 'Sayre Highway, Barangay Casisang, Malaybalay City, Bukidnon 8700',
            'phone' => '(088) 813 2260',
            'sales_phone' => '0995 218 2260',
            'support_phone' => '0995 218 2261',
            'email' => 'malaybalay@kompra.ph',
            'secondary_email' => null,
            'map_query' => 'Casisang, Malaybalay City, Bukidnon',
            'position' => 5,
        ],
        [
            'name' => 'Quezon City',
            'address' => 'Quezon Avenue corner Scout Borromeo, Quezon City, Metro Manila 1103',
            'phone' => '(02) 8374 9210',
            'sales_phone' => '0917 890 9210',
            'support_phone' => '0917 890 9211',
            'email' => 'quezoncity@kompra.ph',
            'secondary_email' => 'ncr.service@kompra.ph',
            'map_query' => 'Quezon Avenue, Quezon City, Metro Manila',
            'position' => 6,
        ],
        [
            'name' => 'Tagbilaran City',
            'address' => 'CPG Avenue, Barangay Poblacion II, Tagbilaran City, Bohol 6300',
            'phone' => '(038) 411 3094',
            'sales_phone' => '0918 337 3094',
            'support_phone' => '0918 337 3095',
            'email' => 'tagbilaran@kompra.ph',
            'secondary_email' => null,
            'map_query' => 'CPG Avenue, Tagbilaran City, Bohol',
            'position' => 7,
        ],
        [
            'name' => 'Central Warehouse',
            'address' => 'Bangoy Street, Barangay 8-A, Davao City, Davao del Sur 8000',
            'phone' => '(082) 297 4199',
            'sales_phone' => '0917 812 4199',
            'support_phone' => '0917 812 4198',
            'email' => 'warehouse@kompra.ph',
            'secondary_email' => 'logistics@kompra.ph',
            'map_query' => 'Bangoy Street, Davao City',
            'is_pickup_location' => false,
            'position' => 99,
        ],
    ];

    public function run(): void
    {
        foreach (self::BRANCHES as $branch) {
            Branch::query()->updateOrCreate(
                ['slug' => Str::slug($branch['name'])],
                [
                    'name' => $branch['name'],
                    'address' => $branch['address'],
                    'phone' => $branch['phone'],
                    'sales_phone' => $branch['sales_phone'],
                    'support_phone' => $branch['support_phone'],
                    'email' => $branch['email'],
                    'secondary_email' => $branch['secondary_email'],
                    'map_embed' => $this->mapEmbed($branch['map_query']),
                    'is_pickup_location' => $branch['is_pickup_location'] ?? true,
                    'is_active' => true,
                    'position' => $branch['position'],
                ]
            );
        }
    }

    /**
     * A Google Maps embed for the branch, built from its locality rather than a
     * pasted share link so the markup is uniform and carries no tracking id.
     */
    private function mapEmbed(string $query): string
    {
        return sprintf(
            '<iframe src="https://www.google.com/maps?q=%s&output=embed" width="100%%" height="300" '
            .'style="border:0;" allowfullscreen="" loading="lazy" '
            .'referrerpolicy="no-referrer-when-downgrade" title="%s on Google Maps"></iframe>',
            rawurlencode($query),
            e($query),
        );
    }
}
