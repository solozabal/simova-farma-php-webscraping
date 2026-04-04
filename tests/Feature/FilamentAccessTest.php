<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for Filament Admin panel access control.
 *
 * Covers:
 *  - Unauthenticated users are redirected to login
 *  - Admin users can access /admin
 *  - Subscriber users are forbidden from /admin
 *  - canAccessPanel() is respected
 */
class FilamentAccessTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Guest (unauthenticated)
    // -------------------------------------------------------------------------

    public function test_guest_is_redirected_from_admin_dashboard(): void
    {
        $response = $this->get('/admin');

        // Filament redirects guests to the login page
        $response->assertRedirect();
        $this->assertStringContainsString('login', $response->headers->get('Location', ''));
    }

    public function test_guest_can_access_admin_login_page(): void
    {
        $response = $this->get('/admin/login');

        $response->assertStatus(200);
    }

    // -------------------------------------------------------------------------
    // Admin user
    // -------------------------------------------------------------------------

    public function test_admin_user_can_access_admin_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertStatus(200);
    }

    // -------------------------------------------------------------------------
    // Subscriber (non-admin)
    // -------------------------------------------------------------------------

    public function test_subscriber_is_forbidden_from_admin_dashboard(): void
    {
        $subscriber = User::factory()->active()->create(['role' => 'subscriber']);

        $response = $this->actingAs($subscriber)->get('/admin');

        // Filament returns 403 for users who fail canAccessPanel()
        $response->assertStatus(403);
    }

    public function test_cancelled_user_is_forbidden_from_admin_dashboard(): void
    {
        $user = User::factory()->cancelled()->create(['role' => 'subscriber']);

        $response = $this->actingAs($user)->get('/admin');

        $response->assertStatus(403);
    }

    // -------------------------------------------------------------------------
    // canAccessPanel logic
    // -------------------------------------------------------------------------

    public function test_only_admin_role_returns_true_for_can_access_panel(): void
    {
        $panel = app(\Filament\Panel::class);

        $admin      = User::factory()->admin()->make();
        $subscriber = User::factory()->active()->make(['role' => 'subscriber']);
        $test       = User::factory()->test()->make(['role' => 'subscriber']);

        $this->assertTrue($admin->canAccessPanel($panel));
        $this->assertFalse($subscriber->canAccessPanel($panel));
        $this->assertFalse($test->canAccessPanel($panel));
    }
}
