# SIMOVA FARMA — Sistema de Inteligência e Monitoramento do Varejo Farmacêutico

> Monitoramento inteligente do mercado farmacêutico para ajudar na tomada de decisões, gerar demanda e lucro no PDV.

---

## Visão Geral

O **SIMOVA FARMA** é uma aplicação Laravel 11 que:

- Coleta e processa notícias/artigos de fontes (RSS, APIs públicas)
- Aplica score de relevância e gera insights via IA
- Entrega conteúdos via **bot Telegram** para assinantes
- Gerencia assinaturas via **Mercado Pago** (recorrente)
- Disponibiliza painel **Filament Admin** para CRM e gestão editorial

---

## Stack Técnica

- **PHP 8.3** + **Laravel 11**
- **Filament 3.x** — painel admin/CRM
- **MySQL** (Hostinger) em produção / SQLite em desenvolvimento
- **Fila (Queue)** com driver database para envios assíncronos
- **Scheduler** Laravel com cron Hostinger

---

## Pré-requisitos

- PHP 8.3+
- Composer 2.x
- Node.js 20+ / npm
- MySQL 8.x (ou SQLite para dev)

---

## Instalação Local

```bash
# 1. Clone o repositório
git clone https://github.com/solozabal/simova-farma-php-webscraping.git
cd simova-farma-php-webscraping

# 2. Instale dependências PHP
composer install

# 3. Copie e configure o .env
cp .env.example .env
# Edite .env com seus valores locais

# 4. Gere a chave da aplicação
php artisan key:generate

# 5. Execute as migrations
php artisan migrate

# 6. Instale dependências JS e compile assets
npm install && npm run build

# 7. Crie o primeiro usuário admin
php artisan make:filament-user

# 8. Inicie o servidor de desenvolvimento
php artisan serve
```

Acesse o painel admin em: http://localhost:8000/admin

---

## Variáveis de Ambiente Necessárias

Copie `.env.example` para `.env` e preencha:

| Variável | Descrição |
|----------|-----------|
| `TELEGRAM_BOT_TOKEN` | Token do bot (obtenha em @BotFather) |
| `TELEGRAM_BOT_USERNAME` | Username do bot (sem @) |
| `MERCADO_PAGO_ACCESS_TOKEN` | Access Token de produção do MP |
| `MERCADO_PAGO_PUBLIC_KEY` | Public Key do MP |
| `MERCADO_PAGO_WEBHOOK_SECRET` | Segredo HMAC do webhook MP |
| `MERCADO_PAGO_PLAN_ID` | ID do plano de assinatura recorrente |
| `GNEWS_API_KEY` | Chave da API GNews (opcional) |
| `AI_API_KEY` | Chave da IA (DeepSeek ou OpenAI) |
| `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Banco de dados |

> ⚠️ **NUNCA commite o arquivo `.env` com valores reais!**

---

## Deploy no Hostinger (Compartilhado)

### Estrutura de Diretórios

```
/home/u804007826/
├── simova-farma/          ← projeto Laravel completo
│   ├── app/
│   ├── config/
│   ├── ...
│   └── public/            ← NÃO use diretamente
└── domains/seudominio.com.br/
    └── public_html/
        └── simova/        ← aponta para public/ do Laravel
            ├── index.php  ← ajuste os paths (ver abaixo)
            ├── .htaccess
            └── build/
```

### Passo a Passo

**1. Upload dos arquivos via FTP/SSH**
```bash
# Faça upload de tudo EXCETO /vendor e /node_modules
# No Hostinger, use o File Manager ou FTP
```

**2. Instale as dependências via SSH do Hostinger**
```bash
cd ~/simova-farma
composer install --no-dev --optimize-autoloader
```

**3. Configure o public/index.php**

Edite `public_html/simova/index.php` para apontar para o diretório correto:
```php
<?php
define('LARAVEL_START', microtime(true));

// Ajuste os caminhos para o Hostinger
require __DIR__.'/../../simova-farma/vendor/autoload.php';

$app = require_once __DIR__.'/../../simova-farma/bootstrap/app.php';
// ...
```

**4. Configure o .env em produção**
```bash
cd ~/simova-farma
cp .env.example .env
nano .env  # Preencha todas as variáveis
php artisan key:generate
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**5. Execute as migrations**
```bash
php artisan migrate --force
```

**6. Registre o webhook do Telegram**
```bash
php artisan telegram:set-webhook
```

**7. Crie o usuário admin**
```bash
php artisan make:filament-user
```

### Configuração do Cron (Scheduler)

No **hPanel do Hostinger** → **Avançado** → **Cron Jobs**, adicione:

```
* * * * * /usr/bin/php8.3 /home/u804007826/simova-farma/artisan schedule:run >> /dev/null 2>&1
```

> **Importante:** Use `php8.3` (ou o binário correto da sua versão).
> O scheduler executa as seguintes tarefas:
> - **09:00** — Digest diário para assinantes
> - **A cada 5 min** — Verifica e envia posts editoriais (slots: 09:10 e 17:40)
> - **Domingos 23:00** — Limpeza de logs antigos (> 90 dias)

### Configuração do Queue Worker

Para processar os jobs da fila (envios Telegram), adicione outro cron:

```
* * * * * /usr/bin/php8.3 /home/u804007826/simova-farma/artisan queue:work --max-time=55 --stop-when-empty 2>&1
```

> Em hostings compartilhados, use `queue:work --stop-when-empty` em vez de `queue:work` (que ficaria rodando continuamente).

### Configuração do Webhook Mercado Pago

1. Acesse: https://www.mercadopago.com.br/developers/panel
2. Vá em **Webhooks** e configure:
   - **URL de produção:** `https://seudominio.com.br/webhooks/mercadopago`
   - **Eventos:** `subscription_preapproval` e `payment`
3. Copie o **Secret** gerado e coloque em `MERCADO_PAGO_WEBHOOK_SECRET` no `.env`

---

## Comandos Artisan Disponíveis

```bash
# Envia o digest diário manualmente
php artisan simova:daily-digest

# Verifica e envia posts editoriais pendentes
php artisan simova:editorial-posts

# Registra o webhook do Telegram
php artisan telegram:set-webhook

# Remove o webhook do Telegram
php artisan telegram:set-webhook --delete

# Cria usuário admin do Filament
php artisan make:filament-user
```

---

## Painel Admin (Filament)

Acesse: `https://seudominio.com.br/admin`

### Módulos disponíveis:

| Módulo | Descrição |
|--------|-----------|
| **Assinantes** | CRM de usuários. Gerencie status, gere links Telegram |
| **Artigos** | Artigos coletados. Revise score e insights |
| **Calendário Editorial** | Crie e agende posts para 09:10 e 17:40 |
| **Blog** | Posts do blog (aprovação manual obrigatória) |
| **Logs do Sistema** | Auditoria de envios, webhooks, erros |

### Status de Assinantes:

| Status | Descrição |
|--------|-----------|
| `lead` | Interessado, ainda não pagou |
| `pending` | Pagamento iniciado |
| `active` | Assinatura ativa — recebe conteúdos |
| `cancelled` | Assinatura cancelada |
| `test` | Usuário de teste interno |

---

## Fluxo do Telegram

1. Usuário assina no site → redireciona para Mercado Pago
2. MP confirma pagamento → webhook ativa o usuário
3. Admin gera link de vinculação no painel (ou é automático pós-pagamento)
4. Usuário clica: `https://t.me/BotNome?start=TOKEN`
5. Bot vincula o `telegram_id` à conta e ativa o recebimento

---

## Higiene de Segredos

- ✅ Nenhum token/chave no código
- ✅ `.env` no `.gitignore`
- ✅ `.env.example` com placeholders comentados
- ✅ Webhook MP valida assinatura HMAC
- ✅ Token Telegram sem fallback hardcoded
- ✅ Envios via fila (não síncronos)

---

## Licença

Propriedade de **SIMOVA FARMA**. Todos os direitos reservados.
