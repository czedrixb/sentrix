<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Reference data and the trading catalogue always run: they are the content
     * the site launches with, in production as much as locally. Sample orders,
     * customers and enquiries are illustrative activity, so they stop at the
     * production boundary.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            BranchSeeder::class,
            CategorySeeder::class,
            BrandSeeder::class,
            UserSeeder::class,
            CatalogSeeder::class,
            ContentSeeder::class,
            VoucherSeeder::class,
        ]);

        if (! app()->environment('production')) {
            $this->call(SalesSeeder::class);
        }
    }
}
