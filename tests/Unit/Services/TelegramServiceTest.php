<?php

namespace Tests\Unit\Services;

use App\Services\TelegramService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramServiceTest extends TestCase
{
    use RefreshDatabase;
    private TelegramService $telegram;

    protected function setUp(): void
    {
        parent::setUp();
        $this->telegram = new TelegramService();
    }

    // -------------------------------------------------------------------------
    // sendMessage
    // -------------------------------------------------------------------------

    public function test_send_message_returns_true_on_success(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 1]], 200),
        ]);

        $result = $this->telegram->sendMessage('123456789', 'Olá, teste!');

        $this->assertTrue($result);
    }

    public function test_send_message_returns_false_on_api_error(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'Bad Request'], 400),
        ]);

        $result = $this->telegram->sendMessage('123456789', 'Mensagem falha');

        $this->assertFalse($result);
    }

    public function test_send_message_sends_html_parse_mode_by_default(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $this->telegram->sendMessage('123456789', '<b>Negrito</b>');

        Http::assertSent(function ($request) {
            $body = $request->data();
            return $body['parse_mode'] === 'HTML'
                && $body['text'] === '<b>Negrito</b>';
        });
    }

    public function test_send_message_with_custom_parse_mode(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $this->telegram->sendMessage('123456789', 'Texto', 'MarkdownV2');

        Http::assertSent(function ($request) {
            return $request->data()['parse_mode'] === 'MarkdownV2';
        });
    }

    // -------------------------------------------------------------------------
    // sendMessageWithButtons
    // -------------------------------------------------------------------------

    public function test_send_message_with_buttons_returns_true_on_success(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $buttons = [[['text' => 'Clique aqui', 'url' => 'https://example.com']]];
        $result  = $this->telegram->sendMessageWithButtons('123456789', 'Texto', $buttons);

        $this->assertTrue($result);
    }

    // -------------------------------------------------------------------------
    // setWebhook / deleteWebhook
    // -------------------------------------------------------------------------

    public function test_set_webhook_returns_true_on_success(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $result = $this->telegram->setWebhook('https://example.com/webhooks/telegram');

        $this->assertTrue($result);
    }

    public function test_delete_webhook_returns_true_on_success(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $result = $this->telegram->deleteWebhook();

        $this->assertTrue($result);
    }

    // -------------------------------------------------------------------------
    // formatAlertMessage
    // -------------------------------------------------------------------------

    public function test_format_alert_message_contains_required_elements(): void
    {
        $message = $this->telegram->formatAlertMessage(
            title      : 'ANVISA proíbe produto X',
            impactLabel: 'REGULATÓRIO',
            insight    : 'Impacto direto nas farmácias independentes.',
            url        : 'https://exemplo.com/artigo',
            sourceLabel: 'ANVISA',
            publishedAt: '04/04/2026',
        );

        $this->assertStringContainsString('ALERTA SIMOVA FARMA', $message);
        $this->assertStringContainsString('ANVISA proíbe produto X', $message);
        $this->assertStringContainsString('REGULATÓRIO', $message);
        $this->assertStringContainsString('Impacto direto nas farmácias independentes.', $message);
        $this->assertStringContainsString('https://exemplo.com/artigo', $message);
        $this->assertStringContainsString('ANVISA', $message);
        $this->assertStringContainsString('04/04/2026', $message);
    }

    public function test_format_alert_message_without_optional_params(): void
    {
        $message = $this->telegram->formatAlertMessage(
            title      : 'Título',
            impactLabel: 'ALTO IMPACTO',
            insight    : 'Insight',
            url        : 'https://exemplo.com',
        );

        $this->assertStringContainsString('ALERTA SIMOVA FARMA', $message);
        $this->assertStringContainsString('Hoje', $message);
    }

    // -------------------------------------------------------------------------
    // formatDigestItem
    // -------------------------------------------------------------------------

    public function test_format_digest_item_contains_title_and_insight(): void
    {
        $item = $this->telegram->formatDigestItem(
            title      : 'Artigo sobre margem bruta',
            impactLabel: 'VAREJO',
            insight    : 'Aumento de 15% no faturamento.',
            url        : 'https://exemplo.com/artigo',
        );

        $this->assertStringContainsString('Artigo sobre margem bruta', $item);
        $this->assertStringContainsString('VAREJO', $item);
        $this->assertStringContainsString('Aumento de 15% no faturamento.', $item);
        $this->assertStringContainsString('https://exemplo.com/artigo', $item);
    }

    // -------------------------------------------------------------------------
    // formatDailyDigest
    // -------------------------------------------------------------------------

    public function test_format_daily_digest_includes_all_items_and_date(): void
    {
        $items = [
            'Item 1 — Insight 1',
            'Item 2 — Insight 2',
        ];

        $digest = $this->telegram->formatDailyDigest($items, '04/04/2026');

        $this->assertStringContainsString('DIGEST SIMOVA FARMA', $digest);
        $this->assertStringContainsString('04/04/2026', $digest);
        $this->assertStringContainsString('Item 1 — Insight 1', $digest);
        $this->assertStringContainsString('Item 2 — Insight 2', $digest);
        $this->assertStringContainsString('2 destaque', $digest);
    }

    // -------------------------------------------------------------------------
    // getMe / getWebhookInfo
    // -------------------------------------------------------------------------

    public function test_get_me_returns_bot_info(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response([
                'ok'     => true,
                'result' => ['id' => 1234567890, 'username' => 'SimovaFarmaBot'],
            ], 200),
        ]);

        $info = $this->telegram->getMe();

        $this->assertArrayHasKey('result', $info);
        $this->assertSame('SimovaFarmaBot', $info['result']['username']);
    }

    // -------------------------------------------------------------------------
    // Constructor throws without token
    // -------------------------------------------------------------------------

    public function test_constructor_throws_when_token_is_not_configured(): void
    {
        config(['services.telegram.token' => null]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/TELEGRAM_BOT_TOKEN/');

        new TelegramService();
    }
}
