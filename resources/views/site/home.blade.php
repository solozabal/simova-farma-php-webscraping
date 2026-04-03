@extends('layouts.site')
@section('title', 'SIMOVA FARMA — Inteligência do Varejo Farmacêutico')
@section('description', 'Receba alertas regulatórios, insights de varejo e resumos diários do mercado farmacêutico diretamente no seu Telegram.')

@section('content')
<!-- Hero -->
<section class="bg-gradient-to-br from-indigo-700 to-indigo-900 text-white py-20 px-4">
    <div class="max-w-4xl mx-auto text-center">
        <h1 class="text-4xl md:text-5xl font-extrabold leading-tight mb-4">
            Inteligência Farmacêutica<br>na Palma da Sua Mão
        </h1>
        <p class="text-lg md:text-xl text-indigo-200 mb-8 max-w-2xl mx-auto">
            Monitoramento inteligente do mercado farmacêutico: alertas regulatórios, insights de varejo e resumos diários entregues no Telegram.
        </p>
        <a href="{{ route('assinar') }}"
           class="inline-block bg-white text-indigo-700 font-bold text-lg px-8 py-3 rounded-full shadow-lg hover:bg-indigo-50 transition">
            Começar Agora →
        </a>
        <p class="text-indigo-300 text-sm mt-4">Assinatura recorrente via Mercado Pago · Cancele quando quiser</p>
    </div>
</section>

<!-- Features -->
<section class="py-16 px-4 bg-gray-50">
    <div class="max-w-5xl mx-auto">
        <h2 class="text-3xl font-bold text-center mb-12 text-gray-800">O que você recebe</h2>
        <div class="grid md:grid-cols-3 gap-8">
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                <div class="text-3xl mb-3">🚀</div>
                <h3 class="font-bold text-lg mb-2">Alertas Imediatos</h3>
                <p class="text-gray-600 text-sm">Notícias de alto impacto (score ≥ 8) enviadas instantaneamente: ANVISA, CMED, regulatório.</p>
            </div>
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                <div class="text-3xl mb-3">📦</div>
                <h3 class="font-bold text-lg mb-2">Digest Diário (09h)</h3>
                <p class="text-gray-600 text-sm">Top 5 notícias do dia com insights acionáveis para tomada de decisão no PDV.</p>
            </div>
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                <div class="text-3xl mb-3">🗓️</div>
                <h3 class="font-bold text-lg mb-2">Calendário Editorial</h3>
                <p class="text-gray-600 text-sm">Pautas fixas diárias: radar regulatório às 09h10 e boletim da tarde às 17h40.</p>
            </div>
        </div>
    </div>
</section>

<!-- How it works -->
<section class="py-16 px-4">
    <div class="max-w-4xl mx-auto">
        <h2 class="text-3xl font-bold text-center mb-12">Como funciona</h2>
        <div class="grid md:grid-cols-4 gap-6 text-center">
            @foreach([
                ['1', 'Assine', 'Escolha o plano e assine com Mercado Pago em segundos.'],
                ['2', 'Vincule', 'Siga o bot no Telegram e vincule sua conta com um clique.'],
                ['3', 'Receba', 'Alertas, digests e insights chegam automaticamente.'],
                ['4', 'Decida', 'Tome decisões embasadas em dados do mercado farmacêutico.'],
            ] as [$n, $title, $desc])
            <div>
                <div class="w-12 h-12 rounded-full bg-indigo-600 text-white font-bold text-xl flex items-center justify-center mx-auto mb-3">{{ $n }}</div>
                <h3 class="font-semibold mb-1">{{ $title }}</h3>
                <p class="text-gray-500 text-sm">{{ $desc }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

<!-- CTA -->
<section class="bg-indigo-700 text-white py-16 px-4 text-center">
    <h2 class="text-3xl font-bold mb-4">Pronto para monitorar o mercado farmacêutico?</h2>
    <p class="text-indigo-200 mb-8">Junte-se aos profissionais que já tomam decisões mais rápidas e precisas.</p>
    <a href="{{ route('assinar') }}"
       class="inline-block bg-white text-indigo-700 font-bold px-10 py-3 rounded-full text-lg hover:bg-indigo-50 transition">
        Assinar Agora
    </a>
</section>

@if($posts->isNotEmpty())
<!-- Recent blog posts -->
<section class="py-16 px-4 bg-gray-50">
    <div class="max-w-5xl mx-auto">
        <h2 class="text-2xl font-bold mb-8">Últimos do Blog</h2>
        <div class="grid md:grid-cols-3 gap-6">
            @foreach($posts as $post)
            <article class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
                <h3 class="font-semibold mb-2"><a href="{{ route('blog.post', $post->slug) }}" class="hover:text-indigo-600">{{ $post->title }}</a></h3>
                <p class="text-gray-500 text-sm">{{ Str::limit($post->excerpt, 100) }}</p>
                <p class="text-xs text-gray-400 mt-3">{{ $post->publish_at?->format('d/m/Y') }}</p>
            </article>
            @endforeach
        </div>
        <div class="text-center mt-8">
            <a href="{{ route('blog') }}" class="text-indigo-600 font-semibold hover:underline">Ver todos os posts →</a>
        </div>
    </div>
</section>
@endif
@endsection
