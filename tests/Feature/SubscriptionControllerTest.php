<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\MercadoPagoService;
use Tests\TestCase;

class SubscriptionControllerTest extends TestCase
{
    // -------------------------------------------------------------------------
    // GET /assinar
    // -------------------------------------------------------------------------

    public function test_assinar_redirects_to_mercadopago_checkout(): void
    {
        $this->mock(MercadoPagoService::class)
             ->shouldReceive('getCheckoutUrl')
             ->once()
             ->with(null)
             ->andReturn('https://www.mercadopago.com.br/subscriptions/checkout?preapproval_plan_id=plan-test');

        $response = $this->get('/assinar');

        $response->assertRedirect();
        $this->assertStringContainsString('mercadopago.com.br', $response->headers->get('Location'));
    }

    public function test_assinar_passes_authenticated_user_to_checkout(): void
    {
        $user = User::factory()->active()->create();

        $this->mock(MercadoPagoService::class)
             ->shouldReceive('getCheckoutUrl')
             ->once()
             ->with(\Mockery::on(fn ($arg) => $arg instanceof User && $arg->id === $user->id))
             ->andReturn('https://www.mercadopago.com.br/subscriptions/checkout?preapproval_plan_id=plan-test&payer_email=' . urlencode($user->email));

        $response = $this->actingAs($user)->get('/assinar');

        $response->assertRedirect();
    }

    // -------------------------------------------------------------------------
    // GET /obrigado
    // -------------------------------------------------------------------------

    public function test_obrigado_returns_200_and_contains_expected_content(): void
    {
        config(['services.telegram.bot_username' => 'SimovaFarmaBot']);

        $response = $this->get('/obrigado');

        $response->assertStatus(200);
        $response->assertSee('Obrigado');
        $response->assertSee('Telegram');
    }

    public function test_obrigado_includes_telegram_bot_link_when_configured(): void
    {
        config(['services.telegram.bot_username' => 'SimovaFarmaBot']);

        $response = $this->get('/obrigado');

        $response->assertStatus(200);
        $response->assertSee('t.me/SimovaFarmaBot');
    }

    public function test_obrigado_works_without_bot_username_configured(): void
    {
        config(['services.telegram.bot_username' => null]);

        $response = $this->get('/obrigado');

        $response->assertStatus(200);
        $response->assertDontSee('t.me/');
    }
}
