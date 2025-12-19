<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\HomePage;
use App\Models\Product;
use App\Services\GeminiService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_filters_products_by_name_or_description(): void
    {
        $match = Product::factory()->create(['name' => 'Alpha Gadget', 'description' => 'Super device']);
        $other = Product::factory()->create(['name' => 'Beta Item', 'description' => 'Plain']);

        $component = Livewire::test(HomePage::class)
            ->set('search', 'Alpha');

        $products = $component->viewData('products');
        $this->assertTrue($products->contains('id', $match->id));
        $this->assertFalse($products->contains('id', $other->id));
    }

    public function test_recommendations_use_gemini_when_available(): void
    {
        $viewed = Product::factory()->count(2)->create();
        $recommended = Product::factory()->count(3)->create();

        session()->put('viewed_products', $viewed->pluck('id')->all());

        $mock = Mockery::mock(GeminiService::class);
        $mock->shouldReceive('isConfigured')->andReturn(true);
        $mock->shouldReceive('getCatalogProjection')->andReturn([]);
        $mock->shouldReceive('recommendProductIdsFromCatalog')->andReturn($recommended->pluck('id')->all());

        $this->app->instance(GeminiService::class, $mock);

        $component = Livewire::test(HomePage::class);

        $ids = $component->viewData('recommendations')->pluck('id')->all();

        $this->assertSame(
            $recommended->pluck('id')->sort()->values()->all(),
            collect($ids)->sort()->values()->all(),
        );
    }

    public function test_heuristic_recommendations_when_service_returns_empty(): void
    {
        $viewed = Product::factory()->count(2)->create(['price' => 100]);
        $inRange = Product::factory()->count(2)->create(['price' => 120]);
        $outOfRange = Product::factory()->create(['price' => 300]);

        session()->put('viewed_products', $viewed->pluck('id')->all());

        $mock = Mockery::mock(GeminiService::class);
        $mock->shouldReceive('isConfigured')->andReturn(true);
        $mock->shouldReceive('getCatalogProjection')->andReturn([]);
        $mock->shouldReceive('recommendProductIdsFromCatalog')->andReturn([]);

        $this->app->instance(GeminiService::class, $mock);

        $component = Livewire::test(HomePage::class);

        $ids = $component->viewData('recommendations')->pluck('id')->all();
        sort($ids);

        $expected = $inRange->pluck('id')->sort()->values()->all();

        $this->assertSame($expected, $ids);
        $this->assertNotContains($outOfRange->id, $ids);
    }

    public function test_recommendations_fall_back_when_gemini_fails(): void
    {
        $viewed = Product::factory()->count(2)->create(['price' => 100]);
        $candidates = Product::factory()->count(3)->create(['price' => 110]);

        session()->put('viewed_products', $viewed->pluck('id')->all());

        $mock = Mockery::mock(GeminiService::class);
        $mock->shouldReceive('isConfigured')->andReturn(true);
        $mock->shouldReceive('getCatalogProjection')->andThrow(new Exception('Boom'));
        $mock->shouldReceive('recommendProductIdsFromCatalog')->never();

        $this->app->instance(GeminiService::class, $mock);

        $component = Livewire::test(HomePage::class);

        $ids = $component->viewData('recommendations')->pluck('id')->all();

        $this->assertCount(3, $ids);
        $this->assertEmpty(array_diff($ids, Product::pluck('id')->all()));
    }

    public function test_recommendations_fallback_when_not_enough_viewed_products(): void
    {
        $products = Product::factory()->count(3)->create(['price' => 50]);

        session()->put('viewed_products', [$products[0]->id]);

        $component = Livewire::test(HomePage::class);

        $ids = $component->viewData('recommendations')->pluck('id')->all();

        sort($ids);
        $expected = $products->pluck('id')->sort()->values()->all();

        $this->assertSame($expected, $ids);
    }
}
