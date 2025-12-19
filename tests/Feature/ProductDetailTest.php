<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\ProductDetail;
use App\Models\Product;
use App\Services\GeminiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class ProductDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_mount_tracks_recently_viewed_products(): void
    {
        $product = Product::factory()->create();

        session()->put('viewed_products', range(1, 12));

        Livewire::test(ProductDetail::class, ['id' => $product->id]);

        $viewed = session('viewed_products', []);

        $this->assertCount(10, $viewed);
        $this->assertSame($product->id, $viewed[array_key_last($viewed)]);
        $this->assertSame(array_unique($viewed), $viewed, 'View list should not contain duplicates');
    }

    public function test_add_to_cart_merges_quantity_and_dispatches_events(): void
    {
        $product = Product::factory()->create(['price' => 9.99]);

        session()->put('cart', [
            $product->id => [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'image' => $product->image,
                'quantity' => 2,
            ],
        ]);

        $component = Livewire::test(ProductDetail::class, ['id' => $product->id]);

        $component->call('addToCart', 3)
            ->assertDispatched('cart-updated')
            ->assertDispatched('notify');

        $this->assertSame(5, session('cart')[$product->id]['quantity']);
    }

    public function test_related_products_uses_gemini_when_available(): void
    {
        $product = Product::factory()->create(['price' => 100]);
        $first = Product::factory()->create(['price' => 90]);
        $second = Product::factory()->create(['price' => 110]);
        $third = Product::factory()->create(['price' => 120]);

        $catalog = [
            ['id' => $first->id, 'name' => $first->name, 'description' => $first->description, 'price' => (float) $first->price],
            ['id' => $second->id, 'name' => $second->name, 'description' => $second->description, 'price' => (float) $second->price],
            ['id' => $third->id, 'name' => $third->name, 'description' => $third->description, 'price' => (float) $third->price],
        ];

        $expectedOrder = [$second->id, $first->id, $third->id];

        $mock = Mockery::mock(GeminiService::class);
        $mock->shouldReceive('isConfigured')->once()->andReturn(true);
        $mock->shouldReceive('getCatalogProjection')->once()->andReturn($catalog);
        $mock->shouldReceive('recommendProductIdsFromCatalog')->once()->andReturn($expectedOrder);

        $this->app->instance(GeminiService::class, $mock);

        $component = Livewire::test(ProductDetail::class, ['id' => $product->id]);

        $recommendations = $component->viewData('recommendations');

        $this->assertSame($expectedOrder, $recommendations->pluck('id')->all());
    }

    public function test_related_products_fall_back_to_price_range_when_gemini_disabled(): void
    {
        $product = Product::factory()->create(['price' => 100]);
        $inRange = Product::factory()->count(2)->create(['price' => 105]);
        $outOfRange = Product::factory()->create(['price' => 300]);

        $mock = Mockery::mock(GeminiService::class);
        $mock->shouldReceive('isConfigured')->once()->andReturn(false);
        $mock->shouldReceive('getCatalogProjection')->never();

        $this->app->instance(GeminiService::class, $mock);

        $component = Livewire::test(ProductDetail::class, ['id' => $product->id]);

        $ids = $component->viewData('recommendations')->pluck('id')->all();

        sort($ids);
        $expected = $inRange->pluck('id')->sort()->values()->all();

        $this->assertSame($expected, $ids);
        $this->assertNotContains($outOfRange->id, $ids);
    }
}
