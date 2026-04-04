<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // canReceiveTelegram
    // -------------------------------------------------------------------------

    public function test_active_user_with_telegram_id_can_receive_telegram(): void
    {
        $user = User::factory()->active()->withTelegram()->create();

        $this->assertTrue($user->canReceiveTelegram());
    }

    public function test_test_user_with_telegram_id_can_receive_telegram(): void
    {
        $user = User::factory()->test()->withTelegram()->create();

        $this->assertTrue($user->canReceiveTelegram());
    }

    public function test_active_user_without_telegram_id_cannot_receive_telegram(): void
    {
        $user = User::factory()->active()->create(['telegram_id' => null]);

        $this->assertFalse($user->canReceiveTelegram());
    }

    public function test_cancelled_user_with_telegram_id_cannot_receive_telegram(): void
    {
        $user = User::factory()->cancelled()->withTelegram()->create();

        $this->assertFalse($user->canReceiveTelegram());
    }

    public function test_pending_user_with_telegram_id_cannot_receive_telegram(): void
    {
        $user = User::factory()->pending()->withTelegram()->create();

        $this->assertFalse($user->canReceiveTelegram());
    }

    public function test_lead_user_cannot_receive_telegram(): void
    {
        $user = User::factory()->create(['status' => 'lead']);

        $this->assertFalse($user->canReceiveTelegram());
    }

    // -------------------------------------------------------------------------
    // activate / cancel
    // -------------------------------------------------------------------------

    public function test_activate_sets_status_to_active_and_records_timestamp(): void
    {
        $user = User::factory()->pending()->create();

        $user->activate();

        $user->refresh();
        $this->assertSame('active', $user->status);
        $this->assertNotNull($user->activated_at);
        $this->assertNull($user->cancelled_at);
    }

    public function test_cancel_sets_status_to_cancelled_and_records_timestamp(): void
    {
        $user = User::factory()->active()->create();

        $user->cancel();

        $user->refresh();
        $this->assertSame('cancelled', $user->status);
        $this->assertNotNull($user->cancelled_at);
    }

    // -------------------------------------------------------------------------
    // generateTelegramLinkToken
    // -------------------------------------------------------------------------

    public function test_generate_telegram_link_token_creates_token_and_expiry(): void
    {
        $user  = User::factory()->create();
        $token = $user->generateTelegramLinkToken();

        $user->refresh();
        $this->assertNotEmpty($token);
        $this->assertSame($token, $user->telegram_link_token);
        $this->assertNotNull($user->telegram_link_token_expires_at);
        $this->assertTrue($user->telegram_link_token_expires_at->isFuture());
    }

    public function test_generate_telegram_link_token_returns_40_char_string(): void
    {
        $user  = User::factory()->create();
        $token = $user->generateTelegramLinkToken();

        $this->assertSame(40, strlen($token));
    }

    // -------------------------------------------------------------------------
    // linkTelegram
    // -------------------------------------------------------------------------

    public function test_link_telegram_stores_telegram_id_and_clears_token(): void
    {
        $user = User::factory()->create();
        $user->generateTelegramLinkToken();

        $user->linkTelegram('987654321', 'testuser');

        $user->refresh();
        $this->assertSame('987654321', $user->telegram_id);
        $this->assertSame('testuser', $user->telegram_username);
        $this->assertNotNull($user->telegram_linked_at);
        $this->assertNull($user->telegram_link_token);
        $this->assertNull($user->telegram_link_token_expires_at);
    }

    // -------------------------------------------------------------------------
    // canAccessPanel
    // -------------------------------------------------------------------------

    public function test_admin_can_access_filament_panel(): void
    {
        $admin = User::factory()->admin()->create();
        $panel = app(\Filament\Panel::class);

        $this->assertTrue($admin->canAccessPanel($panel));
    }

    public function test_subscriber_cannot_access_filament_panel(): void
    {
        $subscriber = User::factory()->active()->create();
        $panel      = app(\Filament\Panel::class);

        $this->assertFalse($subscriber->canAccessPanel($panel));
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function test_scope_receiving_telegram_returns_active_users_with_telegram_id(): void
    {
        User::factory()->active()->withTelegram()->count(3)->create();
        User::factory()->active()->create(['telegram_id' => null]);
        User::factory()->cancelled()->withTelegram()->create();
        User::factory()->test()->withTelegram()->create();

        $eligible = User::receivingTelegram()->get();

        // 3 active + 1 test = 4 (all have telegram_id)
        $this->assertCount(4, $eligible);
    }

    public function test_scope_active_returns_only_active_users(): void
    {
        User::factory()->active()->count(2)->create();
        User::factory()->cancelled()->create();
        User::factory()->pending()->create();

        $active = User::active()->get();

        $this->assertCount(2, $active);
    }

    public function test_scope_admin_returns_only_admin_users(): void
    {
        User::factory()->admin()->count(2)->create();
        User::factory()->active()->count(3)->create();

        $admins = User::admin()->get();

        $this->assertCount(2, $admins);
    }

    // -------------------------------------------------------------------------
    // Unique constraints
    // -------------------------------------------------------------------------

    public function test_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'test@example.com']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        User::factory()->create(['email' => 'test@example.com']);
    }

    public function test_telegram_id_must_be_unique(): void
    {
        User::factory()->withTelegram('111222333')->create();

        $this->expectException(\Illuminate\Database\QueryException::class);
        User::factory()->withTelegram('111222333')->create();
    }

    public function test_telegram_link_token_must_be_unique(): void
    {
        $token = Str::random(40);
        User::factory()->create(['telegram_link_token' => $token]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        User::factory()->create(['telegram_link_token' => $token]);
    }
}
