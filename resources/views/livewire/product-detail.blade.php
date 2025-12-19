<div>
    <div class="container mx-auto px-4 py-8">
        <!-- Breadcrumb -->
        <div class="mb-6">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item href="{{ route('home') }}" wire:navigate>Home</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ $product->name }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </div>

        <!-- Product Detail -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 mb-12">
            <!-- Product Image -->
            <div class="bg-white rounded-2xl p-8 shadow-lg">
                <img src="{{ $product->image }}" alt="{{ $product->name }}" class="w-full h-auto rounded-xl">
            </div>

            <!-- Product Info -->
            <div class="flex flex-col justify-center">
                <h1 class="text-4xl font-bold text-gray-900 mb-4">
                    {{ $product->name }}
                </h1>

                <p class="text-gray-600 text-lg mb-6 leading-relaxed">
                    {{ $product->description }}
                </p>

                <div class="mb-8">
                    <span class="text-5xl font-bold text-blue-600">
                        ${{ number_format($product->price, 2) }}
                    </span>
                </div>

                <!-- Add to Cart with Alpine.js -->
                <div x-data="{ quantity: 1, isAdding: false }">
                    <div class="flex items-center gap-4 mb-6">
                        <flux:button variant="outline" @click="if(quantity > 1) quantity--" icon="minus">
                        </flux:button>

                        <span class="text-2xl font-semibold w-12 text-center" x-text="quantity"></span>

                        <flux:button variant="outline" @click="quantity++" icon="plus">
                        </flux:button>
                    </div>

                    <flux:button class="w-full h-12" icon="shopping-cart" wire:click="addToCart(quantity)"
                        @click="isAdding = true; setTimeout(() => isAdding = false, 2000)" x-bind:disabled="isAdding">
                        <span x-show="!isAdding">Add to Cart</span>
                        <span x-show="isAdding">Added! ✓</span>
                    </flux:button>
                </div>

                <!-- Product Features -->
                <div class="bg-gray-50 rounded-xl p-6 mt-6">
                    <flux:heading size="lg" class="mb-4">Product Features</flux:heading>
                    <ul class="space-y-2">
                        <li class="flex items-start gap-2">
                            <flux:icon.check-circle class="w-5 h-5 text-green-600 mt-0.5" />
                            <span class="text-gray-700">High Quality Materials</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <flux:icon.check-circle class="w-5 h-5 text-green-600 mt-0.5" />
                            <span class="text-gray-700">Fast Shipping</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <flux:icon.check-circle class="w-5 h-5 text-green-600 mt-0.5" />
                            <span class="text-gray-700">30-Day Return Policy</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <flux:icon.check-circle class="w-5 h-5 text-green-600 mt-0.5" />
                            <span class="text-gray-700">Customer Support 24/7</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- You Might Also Like -->
        @if ($recommendations && $recommendations->count() > 0)
            <div class="mt-16">
                <flux:heading size="xl" class="mb-8">
                    💡 You Might Also Like
                </flux:heading>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    @foreach ($recommendations as $rec)
                        <div
                            class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition-shadow duration-300">
                            <a href="{{ route('product.show', $rec->id) }}" wire:navigate>
                                <img src="{{ $rec->image }}" alt="{{ $rec->name }}"
                                    class="w-full h-48 object-cover">
                                <div class="p-4">
                                    <h3 class="font-semibold text-lg text-gray-900 mb-2 line-clamp-2">
                                        {{ $rec->name }}
                                    </h3>
                                    <p class="text-gray-600 text-sm mb-3 line-clamp-2">
                                        {{ $rec->description }}
                                    </p>
                                    <div class="flex items-center justify-between">
                                        <span class="text-xl font-bold text-blue-600">
                                            ${{ number_format($rec->price, 2) }}
                                        </span>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
