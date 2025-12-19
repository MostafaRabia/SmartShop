<?php

declare(strict_types=1);

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layout')]
#[Title('Shopping Cart - SmartShop')]
class CartPage extends Component
{
    public bool $showSuccess = false;

    /**
     * @return array<int, array{id:int,name:string,price:float|int,image:bool|string|null,quantity:int}>
     */
    #[Computed]
    public function cartItems(): array
    {
        $cart = session()->get('cart', []);

        return is_array($cart)
            ? $this->normalizeCartItems($cart)
            : [];
    }

    #[Computed]
    public function totalPrice(): float
    {
        $total = 0.0;
        $items = $this->cartItems();
        foreach ($items as $item) {
            $total += (float) $item['price'] * (int) $item['quantity'];
        }

        return $total;
    }

    public function updateQuantity(int $productId, int $quantity): void
    {
        if ($quantity <= 0) {
            $this->removeFromCart($productId);

            return;
        }

        $cart = session()->get('cart', []);

        if (! is_array($cart)) {
            return;
        }

        $existing = $cart[$productId] ?? null;
        if (! is_array($existing)) {
            return;
        }

        $existing['quantity'] = (int) $quantity;
        $cart[$productId] = $existing;
        session()->put('cart', $cart);
    }

    public function removeFromCart(int $productId): void
    {
        $cart = session()->get('cart', []);
        if (! is_array($cart)) {
            $cart = [];
        }
        unset($cart[$productId]);
        session()->put('cart', $cart);

        $this->dispatch('cart-updated', count: count($cart));
        $this->dispatch('notify', message: 'Product removed', type: 'info');
    }

    public function render(): View
    {
        return view('livewire.cart-page');
    }

    public function checkout(): void
    {
        if (empty($this->cartItems())) {

            $this->dispatch('notify', message: 'Your cart is empty', type: 'warning');

            return;
        }

        // Simulate payment processing
        sleep(1);

        // Clear cart
        session()->forget('cart');
        $this->dispatch('cart-updated', count: 0);

        $this->showSuccess = true;
    }

    // Backward-compatible method used by tests
    public function getTotal(): float
    {
        return $this->totalPrice();
    }

    /**
     * @param  array<mixed>  $cart
     * @return array<int, array{id:int,name:string,price:float|int,image:bool|string|null,quantity:int}>
     */
    private function normalizeCartItems(array $cart): array
    {
        $normalized = [];
        foreach ($cart as $key => $item) {
            $normalizedItem = $this->normalizeCartItem($key, $item);
            if ($normalizedItem !== null) {
                $normalized[$normalizedItem['id']] = $normalizedItem;
            }
        }

        return $normalized;
    }

    /**
     * @return array{id:int,name:string,price:float|int,image:bool|string|null,quantity:int}|null
     */
    private function normalizeCartItem(int|string $key, mixed $item): ?array
    {
        if (! is_array($item)) {
            return null;
        }

        return [
            'id' => $this->intFrom($item['id'] ?? $key, (int) $key),
            'name' => isset($item['name']) && is_string($item['name']) ? $item['name'] : '',
            'price' => $this->floatFrom($item['price'] ?? null, 0.0),
            'image' => isset($item['image']) ? $this->normalizeImageValue($item['image']) : null,
            'quantity' => $this->intFrom($item['quantity'] ?? null, 1),
        ];
    }

    private function intFrom(mixed $value, int $default): int
    {
        return is_numeric($value) ? (int) $value : $default;
    }

    private function floatFrom(mixed $value, float $default): float
    {
        return is_numeric($value) ? (float) $value : $default;
    }

    private function normalizeImageValue(mixed $value): bool|string|null
    {
        return (is_bool($value) || is_string($value)) ? $value : null;
    }
}
