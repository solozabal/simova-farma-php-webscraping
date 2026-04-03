@extends('layouts.site')
@section('title', 'FAQ — SIMOVA FARMA')
@section('content')
<div class="max-w-3xl mx-auto py-12 px-4">
    <h1 class="text-3xl font-bold mb-8">Perguntas Frequentes</h1>
    <div class="space-y-6">
        @foreach([
            ['Como funciona a assinatura?', 'A assinatura é recorrente, cobrada mensalmente via Mercado Pago. Você pode cancelar a qualquer momento sem multa.'],
            ['Como recebo os alertas?', 'Todos os alertas e digests são enviados diretamente para o seu Telegram após vincular sua conta.'],
            ['O que é o digest diário?', 'Todo dia às 09h00 (horário de Brasília), você recebe as top notícias do mercado farmacêutico com insights acionáveis.'],
            ['Como vincular meu Telegram?', 'Após assinar, siga as instruções na página de obrigado para localizar o bot e enviar /start.'],
            ['Posso cancelar minha assinatura?', 'Sim. Acesse o Mercado Pago ou entre em contato com nosso suporte. O acesso é cancelado ao final do período já pago.'],
            ['Os dados são seguros?', 'Sim. Coletamos apenas os dados necessários (e-mail e Telegram ID) conforme nossa Política de Privacidade (LGPD).'],
        ] as [$q, $a])
        <div class="border-b border-gray-100 pb-5">
            <h3 class="font-semibold text-gray-800 mb-2">{{ $q }}</h3>
            <p class="text-gray-600 text-sm">{{ $a }}</p>
        </div>
        @endforeach
    </div>
</div>
@endsection
