<?php

namespace App\Services;

use App\Models\SystemLog;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Serviço de integração com o Mercado Pago.
 *
 * Responsabilidades:
 *  - Redirecionar usuário para checkout de assinatura recorrente
 *  - Processar webhooks de confirmação/cancelamento
 *  - Ativar/cancelar usuários com base nos eventos
 *
 * Configuração necessária no Mercado Pago:
 *  1. Acesse: https://www.mercadopago.com.br/developers/panel
 *  2. Em "Webhooks", configure a URL: https://seusite.com/webhooks/mercadopago
 *  3. Ative os eventos: subscription_preapproval e payment
 *  4. Copie o "Secret" do webhook para MERCADO_PAGO_WEBHOOK_SECRET no .env
 *
 * Variáveis de ambiente necessárias:
 *  MERCADO_PAGO_ACCESS_TOKEN — Access Token de produção
 *  MERCADO_PAGO_PUBLIC_KEY   — Public Key de produção
 *  MERCADO_PAGO_WEBHOOK_SECRET — Segredo para validar assinatura HMAC
 *  MERCADO_PAGO_PLAN_ID      — ID do plano de assinatura recorrente
 */
class MercadoPagoService
{
    private const API_BASE = 'https://api.mercadopago.com';

    private string $accessToken;
    private string $planId;

    public function __construct()
    {
        $token = config('services.mercadopago.access_token');

        if (empty($token)) {
            throw new \RuntimeException(
                'MERCADO_PAGO_ACCESS_TOKEN não está configurado no .env.'
            );
        }

        $this->accessToken = $token;
        $this->planId      = config('services.mercadopago.plan_id', '');
    }

    // -------------------------------------------------------------------------
    // Checkout
    // -------------------------------------------------------------------------

    /**
     * Retorna a URL do checkout de assinatura recorrente.
     * O usuário é redirecionado para esta URL ao clicar em "Assinar".
     *
     * @param  User|null  $user  Se informado, pré-preenche e-mail no checkout
     */
    public function getCheckoutUrl(?User $user = null): string
    {
        // URL base da página de assinatura com o plano configurado
        $url = "https://www.mercadopago.com.br/subscriptions/checkout?preapproval_plan_id={$this->planId}";

        // Adiciona parâmetros de rastreamento (opcional)
        if ($user) {
            // O Mercado Pago permite pré-preencher o e-mail via query string
            $url .= '&payer_email=' . urlencode($user->email);
        }

        return $url;
    }

    // -------------------------------------------------------------------------
    // Webhook
    // -------------------------------------------------------------------------

    /**
     * Valida a assinatura HMAC do webhook.
     * O Mercado Pago envia o header x-signature com timestamp e hash v1.
     *
     * @param  string  $xSignature   Valor do header x-signature
     * @param  string  $xRequestId   Valor do header x-request-id
     * @param  string  $dataId       ID do recurso (da query string ?data.id=...)
     * @param  string  $rawBody      Corpo bruto da requisição (não decodificado)
     * @return bool
     */
    public function validateWebhookSignature(
        string $xSignature,
        string $xRequestId,
        string $dataId,
        string $rawBody
    ): bool {
        $secret = config('services.mercadopago.webhook_secret');

        // Se o segredo não está configurado, log de aviso e nega
        if (empty($secret)) {
            Log::warning('MercadoPago: MERCADO_PAGO_WEBHOOK_SECRET não configurado — webhook rejeitado.');
            return false;
        }

        // Extrai ts e v1 do header x-signature (formato: ts=...;v1=...)
        $parts = [];
        foreach (explode(';', $xSignature) as $part) {
            [$key, $value] = array_pad(explode('=', $part, 2), 2, '');
            $parts[trim($key)] = trim($value);
        }

        if (empty($parts['ts']) || empty($parts['v1'])) {
            return false;
        }

        // Monta o template de assinatura conforme documentação do MP
        $signedTemplate = "id:{$dataId};request-id:{$xRequestId};ts:{$parts['ts']};";
        $expectedHash   = hash_hmac('sha256', $signedTemplate, $secret);

        return hash_equals($expectedHash, $parts['v1']);
    }

    /**
     * Processa o payload do webhook e executa a ação correspondente.
     *
     * @param  array  $payload  Payload JSON decodificado do webhook
     */
    public function processWebhook(array $payload): void
    {
        $type   = $payload['type'] ?? '';
        $action = $payload['action'] ?? '';
        $dataId = $payload['data']['id'] ?? null;

        SystemLog::info('mercadopago', "Webhook recebido: type={$type} action={$action}", $payload);

        match ($type) {
            'subscription_preapproval' => $this->handleSubscriptionEvent($action, $dataId),
            'payment'                  => $this->handlePaymentEvent($action, $dataId),
            default                    => Log::info("MercadoPago: tipo de evento ignorado: {$type}"),
        };
    }

    // -------------------------------------------------------------------------
    // Handlers de eventos
    // -------------------------------------------------------------------------

    private function handleSubscriptionEvent(string $action, ?string $subscriptionId): void
    {
        if (! $subscriptionId) {
            return;
        }

        // Busca os detalhes da assinatura na API do MP
        $subscription = $this->getSubscription($subscriptionId);
        if (! $subscription) {
            return;
        }

        $status  = $subscription['status'] ?? '';
        $payerId = $subscription['payer_id'] ?? null;
        $email   = $subscription['payer_email'] ?? null;

        // Tenta localizar o usuário pelo customer_id ou e-mail
        $user = User::where('payment_customer_id', (string) $payerId)->first()
             ?? ($email ? User::where('email', $email)->first() : null);

        if (! $user) {
            SystemLog::warning('mercadopago', "Usuário não encontrado para subscription {$subscriptionId}", [
                'payer_id'    => $payerId,
                'email'       => $email,
                'status'      => $status,
            ]);
            return;
        }

        // Atualiza os identificadores de pagamento
        $user->update([
            'payment_provider'       => 'mercadopago',
            'payment_customer_id'    => (string) $payerId,
            'payment_subscription_id'=> $subscriptionId,
        ]);

        // Aplica transição de status
        match ($status) {
            'authorized' => $user->activate(),
            'cancelled', 'paused' => $user->cancel(),
            default => null,
        };

        SystemLog::info('mercadopago', "Assinatura {$subscriptionId}: status={$status} → user #{$user->id}", [
            'user_id'         => $user->id,
            'subscription_id' => $subscriptionId,
            'mp_status'       => $status,
        ]);
    }

    private function handlePaymentEvent(string $action, ?string $paymentId): void
    {
        if (! $paymentId) {
            return;
        }

        $payment = $this->getPayment($paymentId);
        if (! $payment) {
            return;
        }

        $status  = $payment['status'] ?? '';
        $payerId = $payment['payer']['id'] ?? null;

        SystemLog::info('mercadopago', "Pagamento {$paymentId}: status={$status}", [
            'payment_id' => $paymentId,
            'payer_id'   => $payerId,
            'status'     => $status,
        ]);

        // Se pagamento aprovado, garante que o usuário está ativo
        if ($status === 'approved' && $payerId) {
            $user = User::where('payment_customer_id', (string) $payerId)->first();
            if ($user && $user->status !== 'active') {
                $user->activate();
            }
        }
    }

    // -------------------------------------------------------------------------
    // API do Mercado Pago
    // -------------------------------------------------------------------------

    private function getSubscription(string $subscriptionId): ?array
    {
        try {
            $response = Http::withToken($this->accessToken)
                ->timeout(15)
                ->get(self::API_BASE . "/preapproval/{$subscriptionId}");

            return $response->successful() ? $response->json() : null;
        } catch (\Exception $e) {
            Log::error("MercadoPago: erro ao buscar assinatura {$subscriptionId}: " . $e->getMessage());
            return null;
        }
    }

    private function getPayment(string $paymentId): ?array
    {
        try {
            $response = Http::withToken($this->accessToken)
                ->timeout(15)
                ->get(self::API_BASE . "/v1/payments/{$paymentId}");

            return $response->successful() ? $response->json() : null;
        } catch (\Exception $e) {
            Log::error("MercadoPago: erro ao buscar pagamento {$paymentId}: " . $e->getMessage());
            return null;
        }
    }
}
