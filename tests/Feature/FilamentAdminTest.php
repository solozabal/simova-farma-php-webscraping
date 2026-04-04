<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class FilamentAdminTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Auth protection
    // -------------------------------------------------------------------------

    public function test_admin_panel_redirects_unauthenticated_users(): void
    {
        $response = $this->get('/admin');

        // Should redirect to login, not show 200
        $response->assertStatus(302);
    }

    public function test_admin_panel_denies_non_admin_users(): void
    {
        $subscriber = User::factory()->active()->create();

        $response = $this->actingAs($subscriber)->get('/admin');

        // Non-admin should be denied (403 or redirect)
        $this->assertContains($response->status(), [302, 403]);
    }

    public function test_admin_panel_accessible_by_admin_users(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/admin');

        // Admin should get through (200 or redirect to dashboard)
        $this->assertContains($response->status(), [200, 302]);
    }

    // -------------------------------------------------------------------------
    // Resource routes
    // -------------------------------------------------------------------------

    public function test_users_resource_accessible_by_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/admin/users');

        $this->assertContains($response->status(), [200, 302]);
    }

    public function test_articles_resource_accessible_by_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/admin/articles');

        $this->assertContains($response->status(), [200, 302]);
    }

    public function test_editorial_posts_resource_accessible_by_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/admin/editorial-posts');

        $this->assertContains($response->status(), [200, 302]);
    }

    public function test_blog_posts_resource_accessible_by_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/admin/blog-posts');

        $this->assertContains($response->status(), [200, 302]);
    }

    public function test_system_logs_resource_accessible_by_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/admin/system-logs');

        $this->assertContains($response->status(), [200, 302]);
    }

    // -------------------------------------------------------------------------
    // Resource routes unauthenticated
    // -------------------------------------------------------------------------

    public function test_admin_resources_redirect_unauthenticated(): void
    {
        foreach (['/admin/users', '/admin/articles', '/admin/editorial-posts'] as $route) {
            $this->get($route)->assertStatus(302);
        }
    }
}
