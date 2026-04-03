@extends('layouts.site')
@section('title', 'Suporte — SIMOVA FARMA')
@section('content')
<div class="max-w-2xl mx-auto py-12 px-4">
    <h1 class="text-3xl font-bold mb-6">Suporte e Contato</h1>
    <p class="text-gray-600 mb-6">Tem dúvidas, problemas ou sugestões? Entre em contato:</p>
    <ul class="space-y-3 text-gray-700">
        <li>📧 <strong>E-mail:</strong> <a href="mailto:suporte@simovafarma.com.br" class="text-indigo-600 hover:underline">suporte@simovafarma.com.br</a></li>
        <li>📋 <strong>FAQ:</strong> <a href="{{ route('faq') }}" class="text-indigo-600 hover:underline">Perguntas Frequentes</a></li>
    </ul>
</div>
@endsection
