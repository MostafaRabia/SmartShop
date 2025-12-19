<div>
    <div class="container mx-auto px-4 py-8">
        <!-- Hero Section -->
        <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-2xl p-12 mb-12 text-center">
            <h1 class="text-5xl font-bold mb-4">Welcome to SmartShop</h1>
            <p class="text-xl mb-8">Discover amazing products with AI-powered recommendations</p>

            <!-- Search Bar with Alpine.js -->
            <div class="max-w-2xl mx-auto" x-data="{ searchQuery: '{{ $search }}' }">
                <flux:input wire:model.live.debounce.500ms="search" x-model="searchQuery"
                    placeholder="Search for products..." class="w-full" icon="magnifying-glass">
                    <x-slot name="iconTrailing" x-show="searchQuery.length > 0">
                        <button @click="searchQuery = ''; $wire.set('search', '')"
                            class="text-gray-400 hover:text-gray-600">
                            <flux:icon.x-mark class="w-5 h-5" />
                        </button>
                    </x-slot>
                </flux:input>
            </div>
        </div>

        <!-- Recommended for You Section -->
        @if ($recommendations && $recommendations->count() > 0)
            <div class="mb-12">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-3xl font-bold text-gray-900">
                        ✨ Recommended for You
                    </h2>
                    <span class="text-sm text-gray-500">Based on your browsing history</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    @foreach ($recommendations as $product)
                        <div
                            class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition-shadow duration-300">
                            <a href="{{ route('product.show', $product->id) }}" wire:navigate>
                                <img src="{{ $product->image }}" alt="{{ $product->name }}"
                                    class="w-full h-48 object-cover">
                                <div class="p-4">
                                    <h3 class="font-semibold text-lg text-gray-900 mb-2 line-clamp-2">
                                        {{ $product->name }}
                                    </h3>
                                    <p class="text-gray-600 text-sm mb-3 line-clamp-2">
                                        {{ $product->description }}
                                    </p>
                                    <div class="flex items-center justify-between">
                                        <span class="text-2xl font-bold text-blue-600">
                                            ${{ number_format($product->price, 2) }}
                                        </span>
                                        <flux:badge color="yellow" size="sm">
                                            <flux:icon.sparkles class="w-3 h-3" />
                                            AI Pick
                                        </flux:badge>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Products Grid -->
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-900 mb-6">All Products</h2>

            @if ($products->count() > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    @foreach ($products as $product)
                        <div
                            class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition-shadow duration-300">
                            <a href="{{ route('product.show', $product->id) }}" wire:navigate>
                                <img src="{{ $product->image }}" alt="{{ $product->name }}"
                                    class="w-full h-48 object-cover">
                                <div class="p-4">
                                    <h3 class="font-semibold text-lg text-gray-900 mb-2 line-clamp-2">
                                        {{ $product->name }}
                                    </h3>
                                    <p class="text-gray-600 text-sm mb-3 line-clamp-2">
                                        {{ $product->description }}
                                    </p>
                                    <div class="flex items-center justify-between">
                                        <span class="text-xl font-bold text-blue-600">
                                            ${{ number_format($product->price, 2) }}
                                        </span>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-8">
                    {{ $products->links() }}
                </div>
            @else
                <div class="text-center py-12">
                    <flux:icon.magnifying-glass class="w-16 h-16 mx-auto text-gray-400 mb-4" />
                    <p class="text-xl text-gray-600">No products found matching your search.</p>
                </div>
            @endif
        </div>
    </div>
</div>
