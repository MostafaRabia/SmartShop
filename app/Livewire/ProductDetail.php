<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Product;
use App\Services\GeminiService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layout')]
class ProductDetail extends Component
{
    public Product $product;

    public function mount(int $id): void
    {
        $this->product = Product::findOrFail($id);

        // Track viewed product in session
        $viewedProducts = session()->get('viewed_products', []);

        if (! is_array($viewedProducts)) {
            $viewedProducts = [];
        }
        $viewedProducts = array_values(array_map(static fn ($value): int => is_numeric($value) ? (int) $value : 0, $viewedProducts));

        // Remove if already exists and add to end
        $viewedProducts = array_diff($viewedProducts, [$this->product->id]);
        $viewedProducts[] = $this->product->id;

        // Keep only last 10 viewed products
        if (count($viewedProducts) > 10) {
            $viewedProducts = array_slice($viewedProducts, -10);
        }

        session()->put('viewed_products', $viewedProducts);
    }

    public function addToCart(int $quantity = 1): void
    {
        $cart = session()->get('cart', []);

        if (! is_array($cart)) {
            $cart = [];
        }

        $entry = [
            'id' => $this->product->id,
            'name' => $this->product->name,
            'price' => $this->product->price,
            'image' => $this->product->image,
            'quantity' => $quantity,
        ];

        $existing = $cart[$this->product->id] ?? null;

        if (is_array($existing) && isset($existing['quantity']) && is_numeric($existing['quantity'])) {
            $entry['quantity'] += (int) $existing['quantity'];
        }

        $cart[$this->product->id] = $entry;

        session()->put('cart', $cart);

        $this->dispatch('cart-updated', count: count($cart));
        $this->dispatch('notify', message: 'Product added to cart!', type: 'success');
    }

    public function render(): View|Factory|Application
    {
        $recommendations = $this->getRelatedProducts();

        return view('livewire.product-detail', [
            'recommendations' => $recommendations,
        ]);
    }

    /**
     * @return Collection<int, Product>
     */
    protected function getRelatedProducts(): Collection
    {
        // Try Gemini first if available, catalog-aware and excluding current
        $service = app(GeminiService::class);
        if ($service->isConfigured()) {
            $payload = [[
                'name' => $this->product->name,
                'description' => $this->product->description,
                'price' => $this->product->price,
            ]];
            $catalog = $service->getCatalogProjection();
            $ids = $service->recommendProductIdsFromCatalog($payload, $catalog, 3, [$this->product->id]);
            if (! empty($ids)) {
                $positions = array_flip(array_map('intval', $ids));

                return Product::whereIn('id', $ids)
                    ->get()
                    ->sortBy(fn ($item) => $positions[(int) $item->id] ?? PHP_INT_MAX)
                    ->values();
            }
        }

        // Fallback: similar price range
        $minPrice = $this->product->price * 0.7;
        $maxPrice = $this->product->price * 1.3;

        return Product::where('id', '!=', $this->product->id)
            ->whereBetween('price', [$minPrice, $maxPrice])
            ->inRandomOrder()
            ->take(3)
            ->get();
    }
}
