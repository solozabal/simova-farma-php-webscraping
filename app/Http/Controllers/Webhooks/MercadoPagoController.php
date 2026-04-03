<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\SystemLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;

class MercadoPagoController extends Controller
{
    /**
     * Handle MercadoPago subscription webhook.
     *
     * Required MercadoPago settings:
     * - MERCADOPAGO_ACCESS_TOKEN: your production/sandbox access token
     * - MERCADOPAGO_WEBHOOK_SECRET: the secret configured in MercadoPago dashboard
     *   (Developers > Webhooks > secret key)
     * - Configure the webhook URL in MercadoPago dashboard:
     *   URL: https://yourdomain.com/webhooks/mercadopago
     *   Events: subscription_preapproval (recurring payments)
     */
    public function handle(Request $request): Response
    {
        if (!$this->validateSignature($request)) {
            SystemLog::log('payment', 'Invalid webhook signature', [], 'warning');
            return response('Forbidden', 403);
        }

        $data = $request->all();
        $type = $data['type'] ?? $data['action'] ?? null;
        $resourceId = $data['data']['id'] ?? null;

        SystemLog::log('payment', 'Webhook received', [
            'type' => $type,
            'resource_id' => $resourceId,
        ]);

        // subscription_preapproval = recurring subscription events
        if (in_array($type, ['subscription_preapproval', 'payment'])) {
            $this->processSubscriptionEvent($type, $resourceId, $data);
        }

        return response('ok', 200);
    }

    private function validateSignature(Request $request): bool
    {
        $secret = config('services.mercadopago.webhook_secret');

        if (empty($secret)) {
            // If no secret configured, skip validation (not recommended for production)
            return true;
        }

        // MercadoPago sends x-signature header: ts=<timestamp>,v1=<hash>
        $signature = $request->header('x-signature', '');
        $requestId = $request->header('x-request-id', '');

        if (empty($signature)) {
            return false;
        }

        // Parse ts and v1 from signature header
        $parts = [];
        foreach (explode(',', $signature) as $part) {
            [$key, $value] = array_pad(explode('=', $part, 2), 2, '');
            $parts[$key] = $value;
        }

        $ts = $parts['ts'] ?? '';
        $v1 = $parts['v1'] ?? '';

        if (empty($ts) || empty($v1)) {
            return false;
        }

        $dataId = $request->input('data.id', '');
        $manifest = "id:{$dataId};request-id:{$requestId};ts:{$ts};";
        $expected = hash_hmac('sha256', $manifest, $secret);

        return hash_equals($expected, $v1);
    }

    private function processSubscriptionEvent(string $type, ?string $resourceId, array $data): void
    {
        if (!$resourceId) {
            return;
        }

        try {
            $subscription = $this->fetchSubscription($resourceId);

            if (!$subscription) {
                SystemLog::log('payment', 'Could not fetch subscription', [
                    'resource_id' => $resourceId,
                ], 'warning');
                return;
            }

            $subscriptionId = $subscription['id'] ?? $resourceId;
            $status = $subscription['status'] ?? null;
            $payerEmail = $subscription['payer_email'] ?? ($subscription['payer']['email'] ?? null);
            $customerId = $subscription['payer_id'] ?? null;

            $user = $this->findOrCreateUser($payerEmail, $subscriptionId, $customerId);

            if (!$user) {
                SystemLog::log('payment', 'No user found for subscription', [
                    'subscription_id' => $subscriptionId,
                    'email' => $payerEmail,
                ], 'warning');
                return;
            }

            $this->updateUserStatus($user, $status, $subscriptionId);

        } catch (\Throwable $e) {
            SystemLog::log('payment', 'Error processing webhook', [
                'resource_id' => $resourceId,
                'error' => $e->getMessage(),
            ], 'error');
        }
    }

    private function fetchSubscription(string $id): ?array
    {
        $token = config('services.mercadopago.access_token');

        if (empty($token)) {
            return null;
        }

        $response = Http::withToken($token)
            ->timeout(10)
            ->get("https://api.mercadopago.com/preapproval/{$id}");

        if ($response->successful()) {
            return $response->json();
        }

        // Try payment endpoint if preapproval fails
        $response = Http::withToken($token)
            ->timeout(10)
            ->get("https://api.mercadopago.com/v1/payments/{$id}");

        return $response->successful() ? $response->json() : null;
    }

    private function findOrCreateUser(?string $email, string $subscriptionId, ?string $customerId): ?User
    {
        if (empty($email)) {
            return User::where('payment_subscription_id', $subscriptionId)->first();
        }

        return User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $email,
                'password' => bcrypt(str()->random(32)),
                'status' => 'pending',
                'payment_provider' => 'mercadopago',
                'payment_subscription_id' => $subscriptionId,
                'payment_customer_id' => $customerId,
            ]
        );
    }

    private function updateUserStatus(User $user, ?string $mpStatus, string $subscriptionId): void
    {
        $newStatus = match ($mpStatus) {
            'authorized', 'active' => 'active',
            'cancelled', 'paused', 'pending' => 'cancelled',
            default => $user->status,
        };

        $updates = [
            'payment_subscription_id' => $subscriptionId,
            'status' => $newStatus,
        ];

        if ($newStatus === 'active' && $user->activated_at === null) {
            $updates['activated_at'] = now();
        } elseif ($newStatus === 'cancelled') {
            $updates['cancelled_at'] = now();
        }

        $user->update($updates);

        SystemLog::log('payment', "User status updated to {$newStatus}", [
            'user_id' => $user->id,
            'mp_status' => $mpStatus,
            'subscription_id' => $subscriptionId,
        ]);
    }
}
