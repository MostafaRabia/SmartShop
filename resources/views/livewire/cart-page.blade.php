<div>
    <div class="container mx-auto px-4 py-8">
        <flux:heading size="xl" class="mb-8">
            🛒 Shopping Cart
        </flux:heading>

        @if ($showSuccess)
            <div class="bg-green-50 border border-green-200 rounded-xl p-8 text-center mb-8">
                <div class="text-green-600 mb-4">
                    <svg class="w-20 h-20 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <flux:heading size="lg" class="text-green-900 mb-2">
                    Order Confirmed! 🎉
                </flux:heading>
                <p class="text-green-700 mb-6">
                    Your order has been successfully placed. Thank you for shopping with us!
                </p>
                <flux:button href="{{ route('home') }}" wire:navigate>
                    Continue Shopping
                </flux:button>
            </div>
        @elseif(empty($this->cartItems))
            <div class="text-center py-16 bg-white rounded-2xl shadow-md">
                <div class="text-gray-400 mb-6">
                    <svg class="w-24 h-24 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <flux:heading size="lg" class="text-gray-600 mb-4">
                    Your cart is empty
                </flux:heading>
                <p class="text-gray-500 mb-6">
                    Start adding some products to your cart!
                </p>
                <flux:button href="{{ route('home') }}" wire:navigate class="h-12">
                    Browse Products
                </flux:button>
            </div>
        @else
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Cart Items -->
                <div class="lg:col-span-2 space-y-4">
                    @foreach ($this->cartItems as $productId => $item)
                        <div wire:key="item-{{ $item['id'] }}-{{ $item['quantity'] }}"
                            class="bg-white rounded-xl shadow-md p-6" x-data="{ quantity: {{ $item['quantity'] }} }">
                            <div class="flex gap-6">
                                <!-- Product Image -->
                                <img src="{{ $item['image'] }}" alt="{{ $item['name'] }}"
                                    class="w-24 h-24 object-cover rounded-lg">

                                <!-- Product Info -->
                                <div class="flex-1">
                                    <h3 class="font-semibold text-lg text-gray-900 mb-2">
                                        {{ $item['name'] }}
                                    </h3>
                                    <p class="text-gray-600 mb-3">
                                        ${{ number_format($item['price'], 2) }} each
                                    </p>

                                    <!-- Quantity Controls with Alpine.js -->
                                    <div class="flex items-center gap-3">
                                        <flux:button variant="outline" size="sm" icon="minus"
                                            wire:click="updateQuantity({{ $item['id'] }}, {{ $item['quantity'] - 1 }})"
                                            wire:loading.attr="disabled"
                                            wire:target="updateQuantity({{ $item['id'] }}, {{ $item['quantity'] - 1 }})"
                                            :disabled="$item['quantity'] <= 1">
                                        </flux:button>

                                        <span class="text-lg font-semibold w-8 text-center">
                                            {{ $item['quantity'] }}
                                        </span>

                                        <flux:button variant="outline" size="sm" icon="plus"
                                            wire:click="updateQuantity({{ $item['id'] }}, {{ $item['quantity'] + 1 }})"
                                            wire:loading.attr="disabled"
                                            wire:target="updateQuantity({{ $item['id'] }}, {{ $item['quantity'] + 1 }})">
                                        </flux:button>

                                        <flux:button variant="ghost" color="red" size="sm" icon="trash"
                                            wire:click="removeFromCart({{ $item['id'] }})"
                                            wire:confirm="Are you sure?" class="ml-auto">
                                            Remove
                                        </flux:button>
                                    </div>
                                </div>

                                <!-- Subtotal -->
                                <div class="text-right">
                                    <p class="text-sm text-gray-500 mb-1">Subtotal</p>
                                    <p class="text-xl font-bold text-gray-900">
                                        $<span
                                            x-text="({{ $item['price'] }} * quantity).toFixed(2)">{{ number_format($item['price'] * $item['quantity'], 2) }}</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Order Summary -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-xl shadow-md p-6 sticky top-24">
                        <flux:heading size="lg" class="mb-6">
                            Order Summary
                        </flux:heading>

                        <div class="space-y-3 mb-6 pb-6 border-b border-gray-200">
                            <div class="flex justify-between text-gray-600">
                                <span>Subtotal</span>
                                <span>${{ number_format($this->totalPrice, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-gray-600">
                                <span>Shipping</span>
                                <span class="text-green-600 font-semibold">FREE</span>
                            </div>
                            <div class="flex justify-between text-gray-600">
                                <span>Tax (0%)</span>
                                <span>$0.00</span>
                            </div>
                        </div>

                        <div class="flex justify-between text-xl font-bold text-gray-900 mb-6">
                            <span>Total</span>
                            <span class="text-blue-600">${{ number_format($this->totalPrice, 2) }}</span>
                        </div>

                        <div x-data="{ isProcessing: false }">
                            <flux:button class="w-full h-12" wire:click="checkout" @click="isProcessing = true"
                                x-bind:disabled="isProcessing" icon="credit-card">
                                <span x-show="!isProcessing">Proceed to Checkout</span>
                                <span x-show="isProcessing">Processing...</span>
                            </flux:button>
                        </div>

                        <p class="text-xs text-gray-500 text-center mt-4">
                            🔒 Secure checkout - Your payment is safe with us
                        </p>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
