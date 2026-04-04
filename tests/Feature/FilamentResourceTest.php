<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\BlogPost;
use App\Models\EditorialPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for Filament admin resource pages.
 *
 * These tests verify that admin users can access the Filament pages
 * and trigger the resource form/table definitions to improve coverage.
 */
class FilamentResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    // -------------------------------------------------------------------------
    // Articles resource
    // -------------------------------------------------------------------------

    public function test_admin_can_list_articles(): void
    {
        Article::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)->get('/admin/articles');

        $response->assertStatus(200);
    }

    public function test_admin_can_access_create_article_page(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/articles/create');

        $response->assertStatus(200);
    }

    public function test_admin_can_access_edit_article_page(): void
    {
        $article = Article::factory()->create();

        $response = $this->actingAs($this->admin)->get("/admin/articles/{$article->id}/edit");

        $response->assertStatus(200);
    }

    // -------------------------------------------------------------------------
    // Editorial Posts resource
    // -------------------------------------------------------------------------

    public function test_admin_can_list_editorial_posts(): void
    {
        EditorialPost::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)->get('/admin/editorial-posts');

        $response->assertStatus(200);
    }

    public function test_admin_can_access_create_editorial_post_page(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/editorial-posts/create');

        $response->assertStatus(200);
    }

    public function test_admin_can_access_edit_editorial_post_page(): void
    {
        $post = EditorialPost::factory()->create();

        $response = $this->actingAs($this->admin)->get("/admin/editorial-posts/{$post->id}/edit");

        $response->assertStatus(200);
    }

    // -------------------------------------------------------------------------
    // Blog Posts resource
    // -------------------------------------------------------------------------

    public function test_admin_can_list_blog_posts(): void
    {
        BlogPost::factory()->count(2)->create();

        $response = $this->actingAs($this->admin)->get('/admin/blog-posts');

        $response->assertStatus(200);
    }

    public function test_admin_can_access_create_blog_post_page(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/blog-posts/create');

        $response->assertStatus(200);
    }

    public function test_admin_can_access_edit_blog_post_page(): void
    {
        $post = BlogPost::factory()->create();

        $response = $this->actingAs($this->admin)->get("/admin/blog-posts/{$post->id}/edit");

        $response->assertStatus(200);
    }

    // -------------------------------------------------------------------------
    // Users (Subscribers) resource
    // -------------------------------------------------------------------------

    public function test_admin_can_list_users(): void
    {
        User::factory()->active()->count(3)->create();

        $response = $this->actingAs($this->admin)->get('/admin/users');

        $response->assertStatus(200);
    }

    public function test_admin_can_access_create_user_page(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/users/create');

        $response->assertStatus(200);
    }

    public function test_admin_can_access_edit_user_page(): void
    {
        $user = User::factory()->active()->create();

        $response = $this->actingAs($this->admin)->get("/admin/users/{$user->id}/edit");

        $response->assertStatus(200);
    }

    // -------------------------------------------------------------------------
    // System Logs resource (read-only)
    // -------------------------------------------------------------------------

    public function test_admin_can_list_system_logs(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/system-logs');

        $response->assertStatus(200);
    }
}
