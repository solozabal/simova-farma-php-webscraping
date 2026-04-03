<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'SIMOVA FARMA') — Inteligência do Varejo Farmacêutico</title>
    <meta name="description" content="@yield('description', 'Monitoramento inteligente do mercado farmacêutico. Alertas regulatórios, insights de varejo e calendário editorial automatizado via Telegram.')">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white text-gray-800 font-sans">

<!-- Navigation -->
<nav class="bg-indigo-700 text-white shadow-md">
    <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
        <a href="{{ route('home') }}" class="text-xl font-bold tracking-tight">SIMOVA FARMA</a>
        <div class="hidden md:flex items-center gap-6 text-sm">
            <a href="{{ route('home') }}" class="hover:text-indigo-200">Início</a>
            <a href="{{ route('blog') }}" class="hover:text-indigo-200">Blog</a>
            <a href="{{ route('sobre') }}" class="hover:text-indigo-200">Sobre</a>
            <a href="{{ route('faq') }}" class="hover:text-indigo-200">FAQ</a>
            <a href="{{ route('assinar') }}" class="bg-white text-indigo-700 font-semibold px-4 py-1.5 rounded-full hover:bg-indigo-50 transition">Assinar</a>
        </div>
    </div>
</nav>

<!-- Content -->
<main>
    @yield('content')
</main>

<!-- Footer -->
<footer class="bg-gray-900 text-gray-400 py-10 mt-16">
    <div class="max-w-6xl mx-auto px-4 grid grid-cols-2 md:grid-cols-4 gap-8 text-sm">
        <div>
            <p class="text-white font-semibold mb-2">SIMOVA FARMA</p>
            <p>Inteligência para o varejo farmacêutico brasileiro.</p>
        </div>
        <div>
            <p class="text-white font-semibold mb-2">Produto</p>
            <ul class="space-y-1">
                <li><a href="{{ route('assinar') }}" class="hover:text-white">Assinar</a></li>
                <li><a href="{{ route('faq') }}" class="hover:text-white">FAQ</a></li>
                <li><a href="{{ route('sobre') }}" class="hover:text-white">Sobre</a></li>
            </ul>
        </div>
        <div>
            <p class="text-white font-semibold mb-2">Suporte</p>
            <ul class="space-y-1">
                <li><a href="{{ route('suporte') }}" class="hover:text-white">Contato</a></li>
                <li><a href="{{ route('blog') }}" class="hover:text-white">Blog</a></li>
            </ul>
        </div>
        <div>
            <p class="text-white font-semibold mb-2">Legal</p>
            <ul class="space-y-1">
                <li><a href="{{ route('termos') }}" class="hover:text-white">Termos de Uso</a></li>
                <li><a href="{{ route('privacidade') }}" class="hover:text-white">Privacidade (LGPD)</a></li>
                <li><a href="{{ route('reembolso') }}" class="hover:text-white">Reembolso</a></li>
            </ul>
        </div>
    </div>
    <div class="max-w-6xl mx-auto px-4 mt-8 text-center text-xs text-gray-600">
        © {{ date('Y') }} SIMOVA FARMA. Todos os direitos reservados. Este serviço fornece inteligência de mercado e não constitui aconselhamento médico ou jurídico.
    </div>
</footer>
</body>
</html>
