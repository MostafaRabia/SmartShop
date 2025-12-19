<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\HomePage;
use App\Livewire\ProductDetail;
use App\Models\Product;
use App\Services\GeminiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class RecommendationConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_and_product_pages_respect_gemini_ordering(): void
    {
        $primary = Product::factory()->create(['price' => 100]);
        $first = Product::factory()->create(['price' => 90]);
        $second = Product::factory()->create(['price' => 110]);
        $third = Product::factory()->create(['price' => 120]);

        session()->put('viewed_products', [$first->id, $second->id]);

        $catalog = collect([$first, $second, $third])->map(static function ($p) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'description' => (string) $p->description,
                'price' => (float) $p->price,
            ];
        })->all();

        $expectedOrder = [$third->id, $first->id, $second->id];

        $mock = Mockery::mock(GeminiService::class);
        $mock->shouldReceive('isConfigured')->andReturn(true);
        $mock->shouldReceive('getCatalogProjection')->twice()->andReturn($catalog);
        $mock->shouldReceive('recommendProductIdsFromCatalog')->twice()->andReturn($expectedOrder);

        $this->app->instance(GeminiService::class, $mock);

        $homeIds = Livewire::test(HomePage::class)
            ->viewData('recommendations')
            ->pluck('id')
            ->all();

        $productIds = Livewire::test(ProductDetail::class, ['id' => $primary->id])
            ->viewData('recommendations')
            ->pluck('id')
            ->all();

        $this->assertSame($expectedOrder, $homeIds);
        $this->assertSame($expectedOrder, $productIds);
    }
}
