<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\MercadoPagoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador das páginas de assinatura.
 *
 * Rotas:
 *   GET /assinar    → redireciona para o checkout do Mercado Pago
 *   GET /obrigado   → página de sucesso após o pagamento
 */
class SubscriptionController extends Controller
{
    public function __construct(private readonly MercadoPagoService $mercadoPago)
    {
    }

    /**
     * Redireciona o usuário para o checkout de assinatura no Mercado Pago.
     *
     * Se o usuário estiver autenticado, pré-preenche o e-mail no checkout.
     * Se não estiver autenticado, redireciona para o checkout genérico.
     */
    public function assinar(Request $request): RedirectResponse
    {
        /** @var User|null $user */
        $user       = $request->user();
        $checkoutUrl = $this->mercadoPago->getCheckoutUrl($user);

        return redirect()->away($checkoutUrl);
    }

    /**
     * Página de agradecimento exibida após o pagamento no Mercado Pago.
     *
     * Orienta o usuário a iniciar o bot do Telegram para vincular a conta
     * e começar a receber os conteúdos.
     */
    public function obrigado(Request $request): View
    {
        $botUsername = config('services.telegram.bot_username');

        return view('subscription.obrigado', [
            'botUsername' => $botUsername,
            'botUrl'      => $botUsername ? "https://t.me/{$botUsername}" : null,
        ]);
    }
}
