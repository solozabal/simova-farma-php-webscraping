@extends('layouts.site')
@section('title', 'Termos de Uso — SIMOVA FARMA')
@section('content')
<div class="max-w-3xl mx-auto py-12 px-4 prose text-gray-700">
    <h1>Termos de Uso</h1>
    <p><em>Última atualização: {{ date('d/m/Y') }}</em></p>

    <h2>1. Aceitação dos Termos</h2>
    <p>Ao assinar e utilizar o SIMOVA FARMA, você concorda com estes Termos de Uso. Se não concordar, não utilize o serviço.</p>

    <h2>2. Descrição do Serviço</h2>
    <p>O SIMOVA FARMA é um serviço de inteligência de mercado que coleta, processa e entrega informações sobre o varejo farmacêutico brasileiro via bot Telegram. O serviço <strong>não constitui aconselhamento médico, farmacêutico, jurídico ou financeiro</strong>.</p>

    <h2>3. Assinatura e Pagamento</h2>
    <p>A assinatura é recorrente, cobrada mensalmente via Mercado Pago. O valor e a periodicidade estão indicados no momento da compra.</p>

    <h2>4. Uso Permitido</h2>
    <p>O conteúdo é para uso pessoal e profissional do assinante. É proibida a redistribuição, revenda ou compartilhamento não autorizado.</p>

    <h2>5. Limitação de Responsabilidade</h2>
    <p>O SIMOVA FARMA não se responsabiliza por decisões tomadas com base nas informações fornecidas. As informações são coletadas de fontes públicas e podem conter imprecisões.</p>

    <h2>6. Contato</h2>
    <p>Dúvidas: <a href="{{ route('suporte') }}">suporte</a> ou suporte@simovafarma.com.br</p>
</div>
@endsection
