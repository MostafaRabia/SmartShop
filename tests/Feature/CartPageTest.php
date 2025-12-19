<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\CartPage;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CartPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_is_loaded_and_total_is_calculated(): void
    {
        $product = Product::factory()->create(['price' => 25.5]);

        $this->withSession([
            'cart' => [
                $product->id => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'image' => $product->image,
                    'quantity' => 2,
                ],
            ],
        ]);

        $component = Livewire::test(CartPage::class);

        $this->assertSame(51.0, $component->instance()->getTotal());
        $this->assertSame(2, session('cart')[$product->id]['quantity']);
    }

    public function test_quantity_can_be_updated_and_removed_when_zero(): void
    {
        $product = Product::factory()->create(['price' => 10]);

        $this->withSession([
            'cart' => [
                $product->id => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'image' => $product->image,
                    'quantity' => 1,
                ],
            ],
        ]);

        $component = Livewire::test(CartPage::class);

        $component->call('updateQuantity', $product->id, 3);

        $this->assertSame(3, session('cart')[$product->id]['quantity']);

        $component->call('updateQuantity', $product->id, 0);

        $this->assertArrayNotHasKey($product->id, session('cart', []));
    }

    public function test_remove_and_checkout_clear_cart_and_dispatch_events(): void
    {
        $product = Product::factory()->create(['price' => 15]);

        $cartEntry = [
            'id' => $product->id,
            'name' => $product->name,
            'price' => $product->price,
            'image' => $product->image,
            'quantity' => 2,
        ];

        $this->withSession(['cart' => [$product->id => $cartEntry]]);

        $component = Livewire::test(CartPage::class);

        $component->call('removeFromCart', $product->id)
            ->assertDispatched('notify')
            ->assertDispatched('cart-updated');

        $this->assertSame([], session('cart', []));

        session()->put('cart', [$product->id => $cartEntry]);

        $component->call('checkout')
            ->assertDispatched('cart-updated');

        $this->assertTrue($component->instance()->showSuccess);
        $this->assertSame([], session('cart', []));
    }
}
