<?php

namespace Tests\Feature;

use App\Http\Controllers\SubscriptionController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Tests for SubscriptionController
 *
 * GET /assinar  → redirects to Mercado Pago checkout URL
 * GET /obrigado → shows the thank-you page with bot link
 */
class SubscriptionControllerTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // /assinar
    // -------------------------------------------------------------------------

    public function test_assinar_redirects_to_mercadopago_checkout(): void
    {
        $response = $this->get('/assinar');

        $response->assertRedirect();
        $this->assertStringContainsString(
            'mercadopago.com.br',
            $response->headers->get('Location', '')
        );
    }

    public function test_assinar_includes_plan_id_in_redirect_url(): void
    {
        $response = $this->get('/assinar');

        $location = $response->headers->get('Location', '');
        $this->assertStringContainsString('test-plan-id', $location);
    }

    public function test_assinar_includes_user_email_when_authenticated(): void
    {
        $user = User::factory()->active()->create(['email' => 'assinante@farmacia.com']);

        $response = $this->actingAs($user)->get('/assinar');

        $location = $response->headers->get('Location', '');
        $this->assertStringContainsString(urlencode('assinante@farmacia.com'), $location);
    }

    public function test_assinar_without_email_when_guest(): void
    {
        $response = $this->get('/assinar');

        $location = $response->headers->get('Location', '');
        $this->assertStringNotContainsString('payer_email', $location);
    }

    // -------------------------------------------------------------------------
    // /obrigado
    // -------------------------------------------------------------------------

    public function test_obrigado_returns_200(): void
    {
        $response = $this->get('/obrigado');

        $response->assertStatus(200);
    }

    public function test_obrigado_view_is_rendered(): void
    {
        $response = $this->get('/obrigado');

        $response->assertViewIs('subscription.obrigado');
    }

    public function test_obrigado_passes_bot_username_to_view(): void
    {
        config(['services.telegram.bot_username' => 'SimovaFarmaTestBot']);

        $response = $this->get('/obrigado');

        $response->assertViewHas('botUsername', 'SimovaFarmaTestBot');
    }

    public function test_obrigado_passes_bot_url_when_username_is_configured(): void
    {
        config(['services.telegram.bot_username' => 'SimovaFarmaTestBot']);

        $response = $this->get('/obrigado');

        $response->assertViewHas('botUrl', 'https://t.me/SimovaFarmaTestBot');
    }

    public function test_obrigado_passes_null_bot_url_when_username_not_configured(): void
    {
        config(['services.telegram.bot_username' => null]);

        $response = $this->get('/obrigado');

        $response->assertViewHas('botUrl', null);
    }
}
