@extends('layouts.site')
@section('title', 'Obrigado — SIMOVA FARMA')
@section('content')
<div class="max-w-2xl mx-auto py-20 px-4 text-center">
    <div class="text-5xl mb-4">🎉</div>
    <h1 class="text-3xl font-bold mb-3">Assinatura confirmada!</h1>
    <p class="text-gray-600 mb-10">Bem-vindo ao SIMOVA FARMA. Siga os passos abaixo para começar a receber seus alertas.</p>

    <div class="text-left space-y-6 mb-10">
        <div class="flex gap-4 items-start bg-gray-50 rounded-xl p-5">
            <span class="w-8 h-8 rounded-full bg-indigo-600 text-white font-bold flex items-center justify-center shrink-0">1</span>
            <div>
                <h3 class="font-semibold mb-1">Abra o Telegram</h3>
                <p class="text-gray-600 text-sm">Acesse o Telegram no celular ou computador.</p>
            </div>
        </div>
        <div class="flex gap-4 items-start bg-gray-50 rounded-xl p-5">
            <span class="w-8 h-8 rounded-full bg-indigo-600 text-white font-bold flex items-center justify-center shrink-0">2</span>
            <div>
                <h3 class="font-semibold mb-1">Procure o bot SIMOVA FARMA</h3>
                <p class="text-gray-600 text-sm">Busque por <strong>@{{ config('services.telegram.bot_username', 'SimovaFarmaBot') }}</strong> no Telegram.</p>
            </div>
        </div>
        <div class="flex gap-4 items-start bg-gray-50 rounded-xl p-5">
            <span class="w-8 h-8 rounded-full bg-indigo-600 text-white font-bold flex items-center justify-center shrink-0">3</span>
            <div>
                <h3 class="font-semibold mb-1">Envie /start para vincular</h3>
                <p class="text-gray-600 text-sm">Clique em <strong>Iniciar</strong> ou envie <code class="bg-gray-200 px-1 rounded">/start</code> para vincular sua conta.</p>
                @if(request()->has('token'))
                <p class="text-sm text-indigo-600 mt-2">Ou use seu <a href="https://t.me/{{ config('services.telegram.bot_username', 'SimovaFarmaBot') }}?start={{ request('token') }}" class="underline font-semibold" target="_blank">link personalizado de vinculação</a>.</p>
                @endif
            </div>
        </div>
        <div class="flex gap-4 items-start bg-gray-50 rounded-xl p-5">
            <span class="w-8 h-8 rounded-full bg-green-600 text-white font-bold flex items-center justify-center shrink-0">✓</span>
            <div>
                <h3 class="font-semibold mb-1">Pronto!</h3>
                <p class="text-gray-600 text-sm">Você receberá alertas e o digest diário automaticamente.</p>
            </div>
        </div>
    </div>

    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-sm text-yellow-800 mb-8">
        💡 <strong>Dica:</strong> O digest diário é enviado todos os dias às 09h00 (horário de Brasília).
    </div>

    <a href="{{ route('suporte') }}" class="text-indigo-600 hover:underline">Precisa de ajuda? Fale conosco →</a>
</div>
@endsection
