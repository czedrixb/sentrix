<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Adding a branch must never require a migration, a new role, or a code change.
 * These tests pin that guarantee down.
 */
class BranchExtensibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_branch_receives_a_stock_row_for_every_existing_product(): void
    {
        Product::factory()->count(3)->create();

        $branch = Branch::create([
            'name' => 'Bacolod City',
            'address' => 'Bacolod City, Negros Occidental',
        ]);

        $this->assertSame(
            3,
            DB::table('branch_product')->where('branch_id', $branch->id)->count()
        );
    }

    public function test_a_new_product_receives_a_stock_row_at_every_branch(): void
    {
        Branch::factory()->count(4)->create();

        $product = Product::factory()->create();

        $this->assertSame(
            4,
            DB::table('branch_product')->where('product_id', $product->id)->count()
        );
    }

    public function test_branch_slug_is_derived_from_the_name_when_omitted(): void
    {
        $branch = Branch::create(['name' => 'General Santos City']);

        $this->assertSame('general-santos-city', $branch->slug);
    }

    public function test_stock_is_tracked_per_branch_rather_than_per_column(): void
    {
        $product = Product::factory()->create();
        $davao = Branch::factory()->create(['name' => 'Davao']);
        $cebu = Branch::factory()->create(['name' => 'Cebu']);

        $product->branches()->syncWithoutDetaching([
            $davao->id => ['quantity' => 12],
            $cebu->id => ['quantity' => 0],
        ]);

        $product->load('branches');

        $this->assertSame(12, $product->stockAt($davao->id));
        $this->assertSame(0, $product->stockAt($cebu->id));
    }

    public function test_available_at_scope_only_returns_products_in_stock_at_that_branch(): void
    {
        $davao = Branch::factory()->create();
        $cebu = Branch::factory()->create();

        $inStock = Product::factory()->create();
        $outOfStock = Product::factory()->create();

        $inStock->branches()->syncWithoutDetaching([$davao->id => ['quantity' => 5]]);
        $outOfStock->branches()->syncWithoutDetaching([$davao->id => ['quantity' => 0]]);

        $results = Product::query()->availableAt($davao->id)->pluck('id');

        $this->assertTrue($results->contains($inStock->id));
        $this->assertFalse($results->contains($outOfStock->id));
        $this->assertCount(0, Product::query()->availableAt($cebu->id)->get());
    }
}
