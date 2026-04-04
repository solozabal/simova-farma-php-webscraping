<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Support\Str;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Casts
    // -------------------------------------------------------------------------

    public function test_datetime_columns_are_cast_correctly(): void
    {
        $user = User::factory()->active()->withTelegram()->create();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $user->activated_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $user->telegram_linked_at);
    }

    public function test_password_is_hashed(): void
    {
        $user = User::factory()->create(['password' => 'plain-password']);

        $this->assertNotEquals('plain-password', $user->password);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('plain-password', $user->password));
    }

    // -------------------------------------------------------------------------
    // Status transitions
    // -------------------------------------------------------------------------

    public function test_activate_sets_status_active_and_activated_at(): void
    {
        $user = User::factory()->pending()->create();

        $user->activate();

        $this->assertEquals('active', $user->fresh()->status);
        $this->assertNotNull($user->fresh()->activated_at);
        $this->assertNull($user->fresh()->cancelled_at);
    }

    public function test_cancel_sets_status_cancelled_and_cancelled_at(): void
    {
        $user = User::factory()->active()->create();

        $user->cancel();

        $this->assertEquals('cancelled', $user->fresh()->status);
        $this->assertNotNull($user->fresh()->cancelled_at);
    }

    // -------------------------------------------------------------------------
    // Telegram linking
    // -------------------------------------------------------------------------

    public function test_link_telegram_persists_id_and_username(): void
    {
        $user = User::factory()->create();

        $user->linkTelegram('987654321', 'farmaciajoao');

        $fresh = $user->fresh();
        $this->assertEquals('987654321', $fresh->telegram_id);
        $this->assertEquals('farmaciajoao', $fresh->telegram_username);
        $this->assertNotNull($fresh->telegram_linked_at);
        $this->assertNull($fresh->telegram_link_token);
        $this->assertNull($fresh->telegram_link_token_expires_at);
    }

    public function test_generate_telegram_link_token_returns_40_chars_and_persists(): void
    {
        $user  = User::factory()->create();
        $token = $user->generateTelegramLinkToken();

        $this->assertEquals(40, strlen($token));
        $this->assertEquals($token, $user->fresh()->telegram_link_token);
        $this->assertTrue($user->fresh()->telegram_link_token_expires_at->isFuture());
    }

    // -------------------------------------------------------------------------
    // canReceiveTelegram
    // -------------------------------------------------------------------------

    public function test_can_receive_telegram_true_for_active_with_telegram_id(): void
    {
        $user = User::factory()->active()->withTelegram()->create();

        $this->assertTrue($user->canReceiveTelegram());
    }

    public function test_can_receive_telegram_true_for_test_status(): void
    {
        $user = User::factory()->test()->withTelegram()->create();

        $this->assertTrue($user->canReceiveTelegram());
    }

    public function test_can_receive_telegram_false_without_telegram_id(): void
    {
        $user = User::factory()->active()->create(['telegram_id' => null]);

        $this->assertFalse($user->canReceiveTelegram());
    }

    public function test_can_receive_telegram_false_for_cancelled_user(): void
    {
        $user = User::factory()->cancelled()->withTelegram()->create();

        $this->assertFalse($user->canReceiveTelegram());
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function test_scope_receiving_telegram_returns_eligible_users(): void
    {
        User::factory()->active()->withTelegram()->count(3)->create();
        User::factory()->test()->withTelegram()->count(2)->create();
        User::factory()->cancelled()->withTelegram()->create();
        User::factory()->active()->create(['telegram_id' => null]);

        $this->assertEquals(5, User::receivingTelegram()->count());
    }

    public function test_scope_active_returns_only_active_users(): void
    {
        User::factory()->active()->count(4)->create();
        User::factory()->pending()->count(2)->create();
        User::factory()->cancelled()->create();

        $this->assertEquals(4, User::active()->count());
    }

    public function test_scope_admin_returns_only_admins(): void
    {
        User::factory()->admin()->count(2)->create();
        User::factory()->active()->count(3)->create();

        $this->assertEquals(2, User::admin()->count());
    }

    // -------------------------------------------------------------------------
    // Filament
    // -------------------------------------------------------------------------

    public function test_can_access_panel_true_only_for_admins(): void
    {
        $admin      = User::factory()->admin()->create();
        $subscriber = User::factory()->active()->create();

        $panel = app(\Filament\Panel::class);

        $this->assertTrue($admin->canAccessPanel($panel));
        $this->assertFalse($subscriber->canAccessPanel($panel));
    }
}
