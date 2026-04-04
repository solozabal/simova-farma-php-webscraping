<?php

namespace App\Http\Controllers;

use App\Models\SystemLog;
use App\Services\MercadoPagoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controlador do webhook do Mercado Pago.
 *
 * Endpoint: POST /webhooks/mercadopago
 *
 * Configuração no painel do Mercado Pago:
 *   1. Acesse: https://www.mercadopago.com.br/developers/panel/app
 *   2. Vá em "Webhooks" e configure:
 *      URL: https://seusite.com/webhooks/mercadopago
 *      Eventos: subscription_preapproval, payment
 *   3. Copie o "Secret" gerado para MERCADO_PAGO_WEBHOOK_SECRET no .env
 *
 * Segurança:
 *   - A assinatura HMAC do header x-signature é validada antes de processar
 *   - Requisições sem assinatura válida são rejeitadas com 401
 */
class MercadoPagoWebhookController extends Controller
{
    public function __construct(private readonly MercadoPagoService $mercadoPago)
    {
    }

    /**
     * Recebe e processa um evento do Mercado Pago.
     */
    public function handle(Request $request): JsonResponse
    {
        $rawBody    = $request->getContent();
        $xSignature = $request->header('x-signature', '');
        $xRequestId = $request->header('x-request-id', '');
        // PHP's parse_str converts dots to underscores in query param names,
        // so ?data.id=... is accessible as data_id (not data.id).
        $dataId     = $request->query('data_id', '');

        // Valida a assinatura HMAC (rejeita se inválida)
        if (! $this->mercadoPago->validateWebhookSignature($xSignature, $xRequestId, $dataId, $rawBody)) {
            Log::warning('MercadoPago webhook: assinatura inválida.', [
                'ip'           => $request->ip(),
                'x-signature'  => $xSignature,
                'x-request-id' => $xRequestId,
            ]);

            SystemLog::warning('mercadopago', 'Webhook com assinatura inválida rejeitado.', [
                'ip'          => $request->ip(),
                'data_id'     => $dataId,
            ]);

            return response()->json(['error' => 'Assinatura inválida'], 401);
        }

        $payload = $request->json()->all();

        try {
            $this->mercadoPago->processWebhook($payload);
        } catch (\Throwable $e) {
            Log::error('MercadoPago webhook processing error: ' . $e->getMessage(), [
                'payload' => $payload,
            ]);

            SystemLog::error('mercadopago', 'Erro ao processar webhook: ' . $e->getMessage(), [
                'payload' => $payload,
                'error'   => $e->getMessage(),
            ]);

            // Retorna 200 para evitar reenvio em loop pelo MP
            return response()->json(['ok' => false, 'message' => 'Erro interno'], 200);
        }

        return response()->json(['ok' => true]);
    }
}
