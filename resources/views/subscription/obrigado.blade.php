<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Obrigado! — SIMOVA FARMA</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f0f4f8;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .card {
            background: #fff;
            border-radius: 12px;
            padding: 3rem 2.5rem;
            max-width: 560px;
            width: 100%;
            box-shadow: 0 4px 24px rgba(0,0,0,0.1);
            text-align: center;
        }
        .emoji { font-size: 4rem; margin-bottom: 1rem; }
        h1 { color: #1a365d; font-size: 1.8rem; margin-bottom: 0.75rem; }
        .subtitle { color: #4a5568; font-size: 1.1rem; margin-bottom: 2rem; }
        .steps { text-align: left; background: #ebf8ff; border-radius: 8px; padding: 1.5rem; margin-bottom: 2rem; }
        .steps h2 { color: #2b6cb0; font-size: 1rem; margin-bottom: 1rem; text-transform: uppercase; letter-spacing: 0.05em; }
        .step { display: flex; align-items: flex-start; margin-bottom: 1rem; gap: 0.75rem; }
        .step-num { background: #2b6cb0; color: #fff; border-radius: 50%; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; font-weight: bold; flex-shrink: 0; }
        .step-text { color: #2d3748; line-height: 1.5; }
        .btn {
            display: inline-block;
            background: #2b6cb0;
            color: #fff;
            padding: 0.875rem 2rem;
            border-radius: 8px;
            text-decoration: none;
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 1rem;
            transition: background 0.2s;
        }
        .btn:hover { background: #2c5282; }
        .note { color: #718096; font-size: 0.9rem; }
        .brand { color: #2b6cb0; font-weight: 700; }
    </style>
</head>
<body>
    <div class="card">
        <div class="emoji">🎉</div>
        <h1>Assinatura Confirmada!</h1>
        <p class="subtitle">
            Bem-vindo(a) ao <span class="brand">SIMOVA FARMA</span>!<br>
            Agora siga os passos abaixo para começar a receber os conteúdos.
        </p>

        <div class="steps">
            <h2>📲 Próximos Passos</h2>

            <div class="step">
                <div class="step-num">1</div>
                <div class="step-text">
                    Clique no botão abaixo para abrir o bot no Telegram.
                </div>
            </div>

            <div class="step">
                <div class="step-num">2</div>
                <div class="step-text">
                    No Telegram, clique em <strong>"Iniciar"</strong> ou envie o comando <strong>/start</strong>.
                </div>
            </div>

            <div class="step">
                <div class="step-num">3</div>
                <div class="step-text">
                    Sua conta será vinculada automaticamente e você começará a receber:
                    <br>• Digest diário às <strong>09:00</strong>
                    <br>• Posts editoriais às <strong>09:10</strong> e <strong>17:40</strong>
                    <br>• Alertas imediatos para notícias de alto impacto 🚨
                </div>
            </div>
        </div>

        @if($botUrl)
            <a href="{{ $botUrl }}" class="btn" target="_blank">
                📱 Abrir o Bot no Telegram
            </a>
            <br>
        @endif

        <p class="note">
            Não tem Telegram? <a href="https://telegram.org/" target="_blank">Baixe gratuitamente aqui</a>.<br>
            Dúvidas? <a href="{{ url('/contato') }}">Entre em contato</a>.
        </p>
    </div>
</body>
</html>
