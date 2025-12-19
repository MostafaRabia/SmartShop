<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Livewire\Settings\TwoFactor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Features;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class TwoFactorComponentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => false,
        ]);
    }

    public function test_enable_without_confirmation_shows_modal_and_keys(): void
    {
        Features::twoFactorAuthentication([
            'confirm' => false,
            'confirmPassword' => false,
        ]);

        $user = User::factory()->create();
        $this->actingAs($user);

        $enable = Mockery::mock(EnableTwoFactorAuthentication::class);
        $enable->shouldReceive('__invoke')
            ->once()
            ->andReturnUsing(function (User $model): void {
                $model->forceFill([
                    'two_factor_secret' => encrypt('secret-value'),
                    'two_factor_recovery_codes' => encrypt(json_encode(['one', 'two'])),
                ])->save();
            });

        $this->app->instance(EnableTwoFactorAuthentication::class, $enable);

        $component = Livewire::test(TwoFactor::class);

        $component->call('enable');

        $component->assertSet('showModal', true);
        $component->assertSet('twoFactorEnabled', true);
        $this->assertNotSame('', $component->get('manualSetupKey'));
        $this->assertNotSame('', $component->get('qrCodeSvg'));
    }

    public function test_show_verification_step_when_confirmation_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Livewire::test(TwoFactor::class);

        $component->call('showVerificationIfNecessary')
            ->assertSet('showVerificationStep', true);
    }

    public function test_confirm_two_factor_sets_flag_and_closes_modal(): void
    {
        $user = User::factory()->create([
            'two_factor_secret' => encrypt('secret-value'),
            'two_factor_recovery_codes' => encrypt(json_encode(['old'])),
        ]);

        $this->actingAs($user);

        $confirm = Mockery::mock(ConfirmTwoFactorAuthentication::class);
        $confirm->shouldReceive('__invoke')
            ->once()
            ->with(Mockery::type(User::class), '123456');

        $this->app->instance(ConfirmTwoFactorAuthentication::class, $confirm);

        $component = Livewire::test(TwoFactor::class);

        $component->set('code', '123456')
            ->call('confirmTwoFactor')
            ->assertSet('twoFactorEnabled', true)
            ->assertSet('showModal', false)
            ->assertSet('showVerificationStep', false);
    }

    public function test_reset_verification_clears_code_and_errors(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $component = Livewire::test(TwoFactor::class);

        $component->set('code', '999999');
        $component->call('resetVerification');

        $component->assertSet('code', '');
        $component->assertSet('showVerificationStep', false);
    }

    public function test_disable_turns_off_two_factor(): void
    {
        $user = User::factory()->create([
            'two_factor_secret' => encrypt('secret-value'),
            'two_factor_recovery_codes' => encrypt(json_encode(['one'])),
        ]);

        $this->actingAs($user);

        $disable = Mockery::mock(DisableTwoFactorAuthentication::class);
        $disable->shouldReceive('__invoke')
            ->once()
            ->andReturnUsing(function (User $model): void {
                $model->forceFill([
                    'two_factor_secret' => null,
                    'two_factor_recovery_codes' => null,
                ])->save();
            });

        $this->app->instance(DisableTwoFactorAuthentication::class, $disable);

        $component = Livewire::test(TwoFactor::class);

        $component->call('disable');

        $component->assertSet('twoFactorEnabled', false);
    }

    public function test_close_modal_resets_state_when_confirmation_not_required(): void
    {
        Features::twoFactorAuthentication([
            'confirm' => false,
            'confirmPassword' => false,
        ]);

        $user = User::factory()->create([
            'two_factor_secret' => encrypt('secret-value'),
            'two_factor_recovery_codes' => encrypt(json_encode(['one'])),
            'two_factor_confirmed_at' => now(),
        ]);

        $this->actingAs($user);

        $component = Livewire::test(TwoFactor::class);

        $component->set('code', '123456');

        $component->call('closeModal')
            ->assertSet('code', '')
            ->assertSet('showModal', false)
            ->assertSet('twoFactorEnabled', true);
    }

    public function test_loading_setup_data_failure_adds_error(): void
    {
        Features::twoFactorAuthentication([
            'confirm' => false,
            'confirmPassword' => false,
        ]);

        $user = User::factory()->create([
            'two_factor_secret' => 'invalid-secret',
            'two_factor_recovery_codes' => encrypt(json_encode(['one'])),
        ]);

        $this->actingAs($user);

        $enable = Mockery::mock(EnableTwoFactorAuthentication::class);
        $enable->shouldReceive('__invoke')
            ->once()
            ->andReturnUsing(function (User $model): void {
                $model->forceFill([
                    'two_factor_secret' => 'invalid-secret',
                    'two_factor_recovery_codes' => encrypt(json_encode(['one'])),
                ])->save();
            });

        $this->app->instance(EnableTwoFactorAuthentication::class, $enable);

        $component = Livewire::test(TwoFactor::class);

        $component->call('enable');

        $component->assertHasErrors(['setupData']);
        $component->assertSet('manualSetupKey', '');
        $component->assertSet('qrCodeSvg', '');
    }
}
