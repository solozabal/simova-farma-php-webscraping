<?php

namespace Tests\Unit;

use App\Services\TelegramService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramServiceTest extends TestCase
{
    private TelegramService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TelegramService();
    }

    // -------------------------------------------------------------------------
    // Message formatting
    // -------------------------------------------------------------------------

    public function test_format_alert_message_contains_title_and_insight(): void
    {
        $message = $this->service->formatAlertMessage(
            title       : 'Resolução ANVISA sobre manipulação',
            impactLabel : 'Alto Impacto',
            insight     : 'Farmácias devem atualizar procedimentos.',
            url         : 'https://anvisa.gov.br/resolucao',
            sourceLabel : 'ANVISA',
            publishedAt : '01/01/2026',
        );

        $this->assertStringContainsString('Resolução ANVISA sobre manipulação', $message);
        $this->assertStringContainsString('Farmácias devem atualizar procedimentos.', $message);
        $this->assertStringContainsString('Alto Impacto', $message);
        $this->assertStringContainsString('https://anvisa.gov.br/resolucao', $message);
        $this->assertStringContainsString('ANVISA', $message);
        $this->assertStringContainsString('01/01/2026', $message);
        $this->assertStringContainsString('ALERTA SIMOVA FARMA', $message);
    }

    public function test_format_alert_message_without_optional_fields(): void
    {
        $message = $this->service->formatAlertMessage(
            title       : 'Título do alerta',
            impactLabel : 'Médio Impacto',
            insight     : 'Insight gerado.',
            url         : 'https://exemplo.com',
        );

        $this->assertStringContainsString('Título do alerta', $message);
        $this->assertStringContainsString('Hoje', $message);
    }

    public function test_format_digest_item_contains_all_parts(): void
    {
        $item = $this->service->formatDigestItem(
            title       : 'Artigo sobre varejo',
            impactLabel : 'Médio Impacto',
            insight     : 'Insight relevante.',
            url         : 'https://g1.com/artigo',
        );

        $this->assertStringContainsString('Artigo sobre varejo', $item);
        $this->assertStringContainsString('Médio Impacto', $item);
        $this->assertStringContainsString('Insight relevante.', $item);
        $this->assertStringContainsString('https://g1.com/artigo', $item);
    }

    public function test_format_daily_digest_contains_header_and_items(): void
    {
        $items = [
            '🔹 <b>Item 1</b>',
            '🔹 <b>Item 2</b>',
            '🔹 <b>Item 3</b>',
        ];

        $digest = $this->service->formatDailyDigest($items, '04/04/2026');

        $this->assertStringContainsString('DIGEST SIMOVA FARMA', $digest);
        $this->assertStringContainsString('04/04/2026', $digest);
        $this->assertStringContainsString('Item 1', $digest);
        $this->assertStringContainsString('Item 2', $digest);
        $this->assertStringContainsString('Item 3', $digest);
        $this->assertStringContainsString('3 destaque', $digest);
    }

    // -------------------------------------------------------------------------
    // HTTP calls (mocked)
    // -------------------------------------------------------------------------

    public function test_send_message_returns_true_on_success(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => []], 200),
        ]);

        $result = $this->service->sendMessage('123456789', 'Olá, assinante!');

        $this->assertTrue($result);
        Http::assertSent(fn ($request) =>
            str_contains($request->url(), 'sendMessage') &&
            $request['chat_id'] === '123456789' &&
            $request['text'] === 'Olá, assinante!' &&
            $request['parse_mode'] === 'HTML'
        );
    }

    public function test_send_message_returns_false_on_api_error(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'Bot was blocked'], 403),
        ]);

        $result = $this->service->sendMessage('123456789', 'Mensagem de teste');

        $this->assertFalse($result);
    }

    public function test_send_message_with_buttons_posts_inline_keyboard(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $buttons = [[['text' => 'Saiba mais', 'url' => 'https://exemplo.com']]];
        $result  = $this->service->sendMessageWithButtons('123456789', 'Texto', $buttons);

        $this->assertTrue($result);
        Http::assertSent(fn ($request) => isset($request['reply_markup']));
    }

    public function test_set_webhook_returns_true_on_success(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $result = $this->service->setWebhook('https://example.com/webhooks/telegram');

        $this->assertTrue($result);
        Http::assertSent(fn ($request) =>
            str_contains($request->url(), 'setWebhook') &&
            $request['url'] === 'https://example.com/webhooks/telegram'
        );
    }

    public function test_constructor_throws_when_token_missing(): void
    {
        config(['services.telegram.token' => null]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('TELEGRAM_BOT_TOKEN');

        new TelegramService();
    }
}
