@extends('layouts.site')
@section('title', 'Assinar — SIMOVA FARMA')
@section('description', 'Assine o SIMOVA FARMA e receba inteligência do varejo farmacêutico diretamente no Telegram.')
@section('content')
<div class="max-w-lg mx-auto py-20 px-4 text-center">
    <h1 class="text-3xl font-bold mb-4">Assine o SIMOVA FARMA</h1>
    <p class="text-gray-600 mb-8">Assinatura recorrente via Mercado Pago. Cancele quando quiser, sem burocracia.</p>
    <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-8 mb-6">
        <div class="text-5xl font-extrabold text-indigo-700 mb-1">R$ —</div>
        <div class="text-gray-500 mb-6">/mês · Preço em breve</div>
        <ul class="text-left text-sm text-gray-700 space-y-2 mb-8">
            <li>✅ Alertas regulatórios imediatos</li>
            <li>✅ Digest diário às 09h</li>
            <li>✅ Calendário editorial automatizado</li>
            <li>✅ Entrega via Telegram</li>
        </ul>
        <p class="text-gray-400 text-xs">O checkout via Mercado Pago estará disponível em breve.</p>
    </div>
    <a href="{{ route('home') }}" class="text-indigo-600 hover:underline text-sm">← Voltar ao início</a>
</div>
@endsection
