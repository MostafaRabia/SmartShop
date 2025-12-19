<?php

declare(strict_types=1);

use App\Livewire\CartPage;
use App\Livewire\HomePage;
use App\Livewire\ProductDetail;
use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\TwoFactor;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

// Shop Routes
Route::get('/', HomePage::class)->name('home');
Route::get('/product/{id}', ProductDetail::class)->name('product.show');
Route::get('/cart', CartPage::class)->name('cart');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', Profile::class)->name('profile.edit');
    Route::get('settings/password', Password::class)->name('user-password.edit');
    Route::get('settings/appearance', Appearance::class)->name('appearance.edit');

    $twoFactorMiddleware = Features::canManageTwoFactorAuthentication()
        && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword')
        ? ['password.confirm']
        : [];

    Route::get('settings/two-factor', TwoFactor::class)
        ->middleware($twoFactorMiddleware)
        ->name('two-factor.show');
});
