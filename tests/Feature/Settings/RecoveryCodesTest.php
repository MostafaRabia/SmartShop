<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Livewire\Settings\TwoFactor\RecoveryCodes;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class RecoveryCodesTest extends TestCase
{
    use RefreshDatabase;

    public function test_recovery_codes_are_loaded_when_enabled(): void
    {
        $user = User::factory()->create([
            'two_factor_secret' => encrypt('secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['code-one', 'code-two'])),
        ]);

        $this->actingAs($user);

        $component = Livewire::test(RecoveryCodes::class);

        $component->assertSet('recoveryCodes', ['code-one', 'code-two']);
    }

    public function test_invalid_codes_add_error_and_reset(): void
    {
        $user = User::factory()->create([
            'two_factor_secret' => encrypt('secret'),
            'two_factor_recovery_codes' => 'not-encrypted',
        ]);

        $this->actingAs($user);

        $component = Livewire::test(RecoveryCodes::class);

        $component->assertHasErrors(['recoveryCodes']);
        $component->assertSet('recoveryCodes', []);
    }

    public function test_recovery_codes_can_be_regenerated(): void
    {
        $user = User::factory()->create([
            'two_factor_secret' => encrypt('secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['old-code'])),
        ]);

        $this->actingAs($user);

        $generator = Mockery::mock(GenerateNewRecoveryCodes::class);
        $generator->shouldReceive('__invoke')
            ->once()
            ->andReturnUsing(function (User $model): void {
                $model->forceFill([
                    'two_factor_recovery_codes' => encrypt(json_encode(['new-one', 'new-two'])),
                ])->save();
            });

        $this->app->instance(GenerateNewRecoveryCodes::class, $generator);

        $component = Livewire::test(RecoveryCodes::class);

        $component->call('regenerateRecoveryCodes');

        $component->assertSet('recoveryCodes', ['new-one', 'new-two']);
    }
}
