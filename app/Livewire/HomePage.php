<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Product;
use App\Services\GeminiService;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layout')]
#[Title('SmartShop - Home')]
class HomePage extends Component
{
    #[Url]
    public string $search = '';

    public function render(): View|Factory|Application
    {
        $query = Product::query();

        if ($this->search) {
            $query->where('name', 'like', '%'.$this->search.'%')
                ->orWhere('description', 'like', '%'.$this->search.'%');
        }

        $products = $query->latest()->paginate(12);

        // Get AI recommendations
        $recommendations = $this->getRecommendations();

        return view('livewire.home-page', [
            'products' => $products,
            'recommendations' => $recommendations,
        ]);
    }

    /**
     * @return Collection<int, Product>
     */
    protected function getRecommendations(): Collection
    {
        $viewedProducts = session()->get('viewed_products', []);
        $viewedProducts = is_array($viewedProducts) ? $viewedProducts : [];
        $viewedProducts = array_values(array_map(static fn ($value): int => is_numeric($value) ? (int) $value : 0, $viewedProducts));

        $lastThreeViewed = array_slice($viewedProducts, -3, 3);

        if (count($lastThreeViewed) >= 2) {
            // Get products that were viewed
            $viewedProductModels = Product::whereIn('id', $lastThreeViewed)->get();

            // Try to get AI recommendations
            try {
                $aiRecommendations = $this->getAIRecommendations($viewedProductModels);
                if ($aiRecommendations->isNotEmpty()) {
                    return $aiRecommendations->take(3);
                }
            } catch (Exception $e) {
                // Fallback to random products
            }
        }

        // Fallback: return random products
        return Product::inRandomOrder()->take(3)->get();
    }

    /**
     * @param  Collection<int, Product>  $viewedProducts
     * @return Collection<int, Product>
     */
    protected function getAIRecommendations(Collection $viewedProducts): Collection
    {
        if ($viewedProducts->isEmpty()) {
            return new Collection;
        }

        $service = app(GeminiService::class);

        $gemini = $this->recommendFromGemini($service, $viewedProducts);
        if ($gemini) {
            return $gemini;
        }

        return $this->heuristicRecommendations($viewedProducts);
    }

    /**
     * @param  Collection<int, Product>  $viewedProducts
     * @return Collection<int, Product>|null
     */
    private function recommendFromGemini(GeminiService $service, Collection $viewedProducts): ?Collection
    {
        $payload = $viewedProducts->map(fn ($p) => [
            'name' => $p->name,
            'description' => $p->description,
            'price' => $p->price,
        ])->values()->all();

        $catalog = $service->getCatalogProjection();
        $excludeIds = $viewedProducts->pluck('id')->map(fn ($id): int => is_numeric($id) ? (int) $id : 0)->all();
        /** @var array<int,int> $excludeIds */
        $ids = $service->recommendProductIdsFromCatalog($payload, $catalog, 3, $excludeIds);

        if (empty($ids)) {
            return null;
        }

        $positions = array_flip(array_map('intval', $ids));

        $ordered = Product::whereIn('id', $ids)
            ->get()
            ->sortBy(fn ($product) => $positions[(int) $product->id] ?? PHP_INT_MAX)
            ->values();

        return $ordered->isNotEmpty() ? $ordered : null;
    }

    /**
     * @param  Collection<int, Product>  $viewedProducts
     * @return Collection<int, Product>
     */
    private function heuristicRecommendations(Collection $viewedProducts): Collection
    {
        $viewedIds = $viewedProducts->pluck('id')->map(fn ($id): int => is_numeric($id) ? (int) $id : 0)->toArray();
        $avgPrice = $viewedProducts->avg('price');
        if ($avgPrice === null) {
            return Product::whereNotIn('id', $viewedIds)
                ->inRandomOrder()
                ->take(3)
                ->get();
        }

        $minPrice = $avgPrice * 0.7;
        $maxPrice = $avgPrice * 1.3;

        return Product::whereNotIn('id', $viewedIds)
            ->whereBetween('price', [$minPrice, $maxPrice])
            ->inRandomOrder()
            ->take(3)
            ->get();
    }
}
