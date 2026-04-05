<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIMOVA FARMA — Inteligência Farmacêutica no Telegram</title>
    <meta name="description" content="Receba alertas de notícias, análises editoriais e digest diário do mercado farmacêutico direto no seu Telegram.">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f0f4f8;
            color: #2d3748;
        }

        /* ── Hero ── */
        .hero {
            background: linear-gradient(135deg, #1a365d 0%, #2b6cb0 100%);
            color: #fff;
            text-align: center;
            padding: 4rem 2rem 5rem;
        }
        .hero .brand {
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            opacity: 0.85;
            margin-bottom: 1.25rem;
        }
        .hero h1 {
            font-size: 2.4rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1rem;
        }
        .hero p {
            font-size: 1.15rem;
            opacity: 0.9;
            max-width: 560px;
            margin: 0 auto 2rem;
            line-height: 1.65;
        }
        .btn-primary {
            display: inline-block;
            background: #fff;
            color: #1a365d;
            padding: 1rem 2.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-size: 1.05rem;
            font-weight: 700;
            transition: transform 0.15s, box-shadow 0.15s;
            box-shadow: 0 4px 14px rgba(0,0,0,0.2);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.25);
        }
        .btn-secondary {
            display: inline-block;
            background: transparent;
            color: #fff;
            padding: 1rem 2.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-size: 1.05rem;
            font-weight: 600;
            border: 2px solid rgba(255,255,255,0.7);
            margin-left: 1rem;
            transition: background 0.15s, border-color 0.15s;
        }
        .btn-secondary:hover {
            background: rgba(255,255,255,0.1);
            border-color: #fff;
        }

        /* ── Features ── */
        .features {
            max-width: 900px;
            margin: -2.5rem auto 0;
            padding: 0 1.5rem 3rem;
        }
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.25rem;
        }
        .feature-card {
            background: #fff;
            border-radius: 12px;
            padding: 1.75rem 1.5rem;
            box-shadow: 0 2px 16px rgba(0,0,0,0.07);
        }
        .feature-card .icon { font-size: 2rem; margin-bottom: 0.75rem; }
        .feature-card h3 { font-size: 1rem; font-weight: 700; color: #1a365d; margin-bottom: 0.5rem; }
        .feature-card p  { font-size: 0.9rem; color: #4a5568; line-height: 1.55; }

        /* ── How it works ── */
        .how {
            background: #fff;
            padding: 3rem 1.5rem;
            text-align: center;
        }
        .how h2 { font-size: 1.6rem; color: #1a365d; margin-bottom: 0.5rem; }
        .how .subtitle { color: #718096; margin-bottom: 2.5rem; }
        .steps {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 2rem;
            max-width: 820px;
            margin: 0 auto;
        }
        .step {
            flex: 1 1 200px;
            max-width: 220px;
        }
        .step-num {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            background: #2b6cb0;
            color: #fff;
            border-radius: 50%;
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 0.75rem;
        }
        .step h4 { font-size: 0.95rem; font-weight: 700; color: #2d3748; margin-bottom: 0.4rem; }
        .step p  { font-size: 0.875rem; color: #718096; line-height: 1.55; }

        /* ── CTA ── */
        .cta {
            background: linear-gradient(135deg, #2b6cb0 0%, #1a365d 100%);
            color: #fff;
            text-align: center;
            padding: 4rem 2rem;
        }
        .cta h2 { font-size: 1.8rem; font-weight: 800; margin-bottom: 0.75rem; }
        .cta p  { opacity: 0.9; margin-bottom: 2rem; font-size: 1.05rem; }

        /* ── Footer ── */
        footer {
            background: #1a365d;
            color: rgba(255,255,255,0.6);
            text-align: center;
            padding: 1.5rem;
            font-size: 0.85rem;
        }
        footer a { color: rgba(255,255,255,0.75); text-decoration: none; }
        footer a:hover { color: #fff; }

        @media (max-width: 600px) {
            .hero h1 { font-size: 1.8rem; }
            .btn-secondary { margin-left: 0; margin-top: 0.75rem; }
        }
    </style>
</head>
<body>

    {{-- ═══════════════════════════════════ HERO ═══════════════════════════════════ --}}
    <section class="hero">
        <p class="brand">📰 SIMOVA FARMA</p>
        <h1>Inteligência Farmacêutica<br>Direto no seu Telegram</h1>
        <p>
            Receba alertas de notícias de alto impacto, análises editoriais e um
            digest diário com o que realmente importa para o mercado farmacêutico —
            tudo curado por IA e entregue no seu Telegram.
        </p>
        <a href="{{ route('assinar') }}" class="btn-primary">🚀 Assinar Agora</a>
        <a href="{{ url('/admin') }}" class="btn-secondary">Área Admin</a>
    </section>

    {{-- ═══════════════════════════════════ FEATURES ═══════════════════════════════════ --}}
    <section class="features">
        <div class="features-grid">
            <div class="feature-card">
                <div class="icon">🚨</div>
                <h3>Alertas Imediatos</h3>
                <p>Notícias com pontuação ≥ 8 chegam instantaneamente, minutos após a publicação.</p>
            </div>
            <div class="feature-card">
                <div class="icon">📋</div>
                <h3>Digest Diário</h3>
                <p>Todo dia às <strong>09:00</strong> um resumo das principais notícias do setor — sem ruído.</p>
            </div>
            <div class="feature-card">
                <div class="icon">✍️</div>
                <h3>Posts Editoriais</h3>
                <p>Análises e conteúdos exclusivos entregues às <strong>09:10</strong> e <strong>17:40</strong>.</p>
            </div>
            <div class="feature-card">
                <div class="icon">🤖</div>
                <h3>Curadoria por IA</h3>
                <p>Artigos coletados, pontuados e resumidos automaticamente para você não perder tempo.</p>
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════ HOW IT WORKS ═══════════════════════════════════ --}}
    <section class="how">
        <h2>Como funciona?</h2>
        <p class="subtitle">Três passos simples para começar a receber conteúdo premium.</p>
        <div class="steps">
            <div class="step">
                <div class="step-num">1</div>
                <h4>Assine o plano</h4>
                <p>Clique em "Assinar Agora" e conclua o pagamento seguro via Mercado Pago.</p>
            </div>
            <div class="step">
                <div class="step-num">2</div>
                <h4>Abra o Telegram</h4>
                <p>Após o pagamento, acesse o link e inicie o bot no Telegram.</p>
            </div>
            <div class="step">
                <div class="step-num">3</div>
                <h4>Receba conteúdo</h4>
                <p>Sua conta é vinculada automaticamente e você começa a receber os conteúdos.</p>
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════ CTA ═══════════════════════════════════ --}}
    <section class="cta">
        <h2>Pronto para começar?</h2>
        <p>Junte-se a profissionais do setor farmacêutico que usam o SIMOVA FARMA.</p>
        <a href="{{ route('assinar') }}" class="btn-primary">🚀 Quero Assinar</a>
    </section>

    {{-- ═══════════════════════════════════ FOOTER ═══════════════════════════════════ --}}
    <footer>
        <p>&copy; {{ date('Y') }} SIMOVA FARMA — Todos os direitos reservados.</p>
    </footer>

</body>
</html>
