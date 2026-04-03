# SIMOVA FARMA — Sistema de Inteligência e Monitoramento do Varejo Farmacêutico

[![PHP](https://img.shields.io/badge/PHP-8.3-blue)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-11-red)](https://laravel.com)
[![Filament](https://img.shields.io/badge/Filament-3.x-orange)](https://filamentphp.com)

Plataforma de inteligência de mercado para farmácias independentes e distribuidores, entregando alertas regulatórios, insights de varejo e resumos diários via bot Telegram.

---

## Requisitos

- PHP 8.3+
- Composer 2.x
- MySQL 8.0+ (ou SQLite para desenvolvimento)
- Node.js 20+ / npm
- Conta Mercado Pago (com API access)
- Bot Telegram (via @BotFather)

---

## Instalação Local (Desenvolvimento)

```bash
# 1. Clonar o repositório
git clone https://github.com/solozabal/simova-farma-php-webscraping.git
cd simova-farma-php-webscraping

# 2. Instalar dependências PHP
composer install

# 3. Instalar dependências JS e compilar assets
npm install && npm run build

# 4. Configurar ambiente
cp .env.example .env
php artisan key:generate

# 5. Configurar banco de dados no .env, depois migrar
php artisan migrate

# 6. Criar usuário admin para o painel
php artisan make:filament-user

# 7. Subir servidor de desenvolvimento
php artisan serve
```

Acesse:
- Site: http://localhost:8000
- Admin (CRM): http://localhost:8000/admin

---

## Deploy no Hostinger

### Estrutura de Pastas Recomendada

```
/home/u804007826/domains/simovafarma.com.br/
├── simova-farma/                 # Projeto Laravel completo (fora do public_html)
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── resources/
│   ├── routes/
│   ├── storage/
│   └── vendor/
└── public_html/                  # Apenas o conteúdo de public/
    ├── index.php                 # Ajustado (ver abaixo)
    ├── .htaccess
    └── build/                    # Assets compilados
```

### Passos de Deploy

```bash
# 1. Fazer upload do projeto (excluindo vendor/ e node_modules/)
# Use Git ou SFTP para enviar para /home/.../simova-farma/

# 2. Instalar dependências no servidor
cd /home/u804007826/domains/simovafarma.com.br/simova-farma
composer install --optimize-autoloader --no-dev

# 3. Compilar assets localmente e fazer upload do build/
npm run build
# Copiar public/build/ para public_html/build/

# 4. Copiar arquivos públicos para public_html/
cp public/.htaccess ../public_html/
cp public/index.php ../public_html/index.php

# 5. Ajustar caminhos no public_html/index.php
# Editar as linhas require:
```

**`public_html/index.php`** — ajuste os caminhos:
```php
require __DIR__.'/../simova-farma/vendor/autoload.php';
$app = require_once __DIR__.'/../simova-farma/bootstrap/app.php';
```

```bash
# 6. Configurar .env em produção
cp .env.example .env
# Editar .env com credenciais reais
php artisan key:generate

# 7. Rodar migrações
php artisan migrate --force

# 8. Otimizar para produção
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 9. Criar usuário admin
php artisan make:filament-user

# 10. Configurar permissões de storage
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Configurar Cron no Hostinger

No painel do Hostinger, vá em **Avançado > Cron Jobs** e adicione:

```
* * * * * php /home/u804007826/domains/simovafarma.com.br/simova-farma/artisan schedule:run >> /dev/null 2>&1
```

Isso executa o scheduler do Laravel a cada minuto, que por sua vez dispara:
- **09:00** — Digest diário para assinantes Telegram
- **09:10** — Calendário editorial (slot matutino)
- **17:40** — Calendário editorial (slot vespertino)
- **A cada 5 min** — Verificação de posts editoriais agendados
- **A cada 1 min** — Publicação automática de blog posts agendados
- **Domingo 23:00** — Limpeza de logs antigos

---

## Configuração do Bot Telegram

1. Abra o Telegram e fale com **@BotFather**
2. Crie um novo bot: `/newbot`
3. Copie o token para `.env`: `TELEGRAM_BOT_TOKEN=...`
4. Após deploy, registre o webhook:
   ```bash
   php artisan simova:telegram:set-webhook
   ```
5. O webhook será registrado em: `https://yourdomain.com/webhooks/telegram`

---

## Configuração do Mercado Pago

1. Acesse [Mercado Pago Developers](https://www.mercadopago.com.br/developers)
2. Crie/selecione sua aplicação
3. Copie o **Access Token** (produção ou sandbox)
4. Em **Developers > Webhooks**, configure:
   - **URL:** `https://yourdomain.com/webhooks/mercadopago`
   - **Eventos:** `subscription_preapproval`
   - Copie o **secret key** gerado para `.env`: `MERCADOPAGO_WEBHOOK_SECRET=...`
5. Crie um plano de assinatura recorrente e cole o link de checkout em:
   `MERCADOPAGO_CHECKOUT_URL=...`

### Fluxo de Assinatura

```
1. Usuário acessa /assinar → redirecionado para Mercado Pago
2. Pagamento aprovado → MP envia webhook para /webhooks/mercadopago
3. Sistema ativa o usuário (status: active)
4. Usuário vai para /obrigado e vincula Telegram via /start
5. Usuário começa a receber alertas e digests
```

---

## Painel Admin (CRM)

Acesse em `/admin` após autenticação.

| Módulo | Descrição |
|--------|-----------|
| **Usuários** | Gerenciar assinantes, status, whitelist de teste |
| **Artigos** | Curadoria de conteúdo coletado, score, editorial |
| **Posts Editoriais** | Calendário editorial, canais, agendamento |
| **Posts do Blog** | CMS: rascunho, agendamento, publicação |
| **Logs do Sistema** | Monitorar erros e eventos (Telegram, pagamento, editorial) |

---

## Scheduler — Resumo

| Horário | Timezone | Comando |
|---------|----------|---------|
| 09:00 diário | America/Sao_Paulo | `simova:digest` |
| 09:10 diário | America/Sao_Paulo | `simova:editorial:run --slot=morning` |
| 17:40 diário | America/Sao_Paulo | `simova:editorial:run --slot=afternoon` |
| A cada 5 min | America/Sao_Paulo | `simova:editorial:run` |
| A cada 1 min | America/Sao_Paulo | `simova:blog:publish` |
| Domingo 23:00 | America/Sao_Paulo | `simova:cleanup` |

---

## Segurança

- **Nunca** commite tokens reais (Telegram, Mercado Pago, APIs)
- O arquivo `.env` está no `.gitignore`
- O webhook do Mercado Pago valida assinatura HMAC-SHA256
- Use `QUEUE_CONNECTION=database` em produção para jobs assíncronos

---

## Usuários de Teste

Para testar antes de ativar assinaturas pagas:
1. No painel admin, crie um usuário com `status = test`
2. Vincule o Telegram desse usuário manualmente (campo `telegram_id`)
3. Usuários `test` recebem todos os alertas e digests como assinantes `active`
