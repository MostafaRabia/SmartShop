<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'SmartShop' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-50 min-h-screen" x-data="{
    cartCount: {{ count(session()->get('cart', [])) }},
    showNotification: false,
    notificationMessage: '',
    notificationType: 'success'
}" @cart-updated.window="cartCount = $event.detail.count">
    <!-- Header -->
    <header class="bg-white shadow-sm sticky top-0 z-50">
        <div class="container mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <!-- Logo -->
                <a href="{{ route('home') }}" class="text-2xl font-bold text-gray-900">
                    🛍️ SmartShop
                </a>

                <!-- Cart -->
                <a href="{{ route('cart') }}"
                    class="relative inline-flex items-center gap-2 px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 transition"
                    @cart-updated.window="cartCount = $event.detail?.count ?? Object.keys($store.cart || {}).length">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <span x-text="cartCount"></span>
                </a>
            </div>
        </div>
    </header>

    <!-- Notification -->
    <div x-show="showNotification" x-transition
        @notify.window="
            notificationMessage = $event.detail.message;
            notificationType = $event.detail.type || 'success';
            showNotification = true;
            setTimeout(() => showNotification = false, 3000)
         "
        class="fixed top-20 right-4 z-50 px-6 py-3 rounded-lg shadow-lg text-white"
        :class="{
            'bg-green-500': notificationType === 'success',
            'bg-blue-500': notificationType === 'info',
            'bg-yellow-500': notificationType === 'warning',
            'bg-red-500': notificationType === 'error'
        }"
        style="display: none;">
        <p x-text="notificationMessage"></p>
    </div>

    <!-- Main Content -->
    <main>
        {{ $slot }}
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white mt-12">
        <div class="container mx-auto px-4 py-6 text-center">
            <p>&copy; 2025 SmartShop. Made with ❤️ using Laravel & Livewire</p>
        </div>
    </footer>

    @fluxScripts
</body>

</html>
