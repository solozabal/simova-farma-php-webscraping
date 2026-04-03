@extends('layouts.site')
@section('title', 'Política de Privacidade — SIMOVA FARMA')
@section('content')
<div class="max-w-3xl mx-auto py-12 px-4 prose text-gray-700">
    <h1>Política de Privacidade (LGPD)</h1>
    <p><em>Última atualização: {{ date('d/m/Y') }}</em></p>

    <h2>1. Dados Coletados</h2>
    <ul>
        <li>E-mail (para identificação e comunicação)</li>
        <li>Telegram ID e username (para entrega de mensagens)</li>
        <li>Dados de pagamento processados pelo Mercado Pago (não armazenamos dados de cartão)</li>
        <li>Logs de uso do sistema (para diagnóstico e melhoria)</li>
    </ul>

    <h2>2. Base Legal</h2>
    <p>O tratamento dos dados é baseado na execução do contrato de assinatura (Art. 7º, V, LGPD).</p>

    <h2>3. Retenção</h2>
    <p>Logs de sistema: 90 dias. Dados de assinante: enquanto ativa, mais 12 meses após cancelamento para obrigações legais.</p>

    <h2>4. Seus Direitos (LGPD)</h2>
    <p>Você tem direito a acessar, corrigir, portabilizar ou solicitar exclusão dos seus dados. Envie solicitação para suporte@simovafarma.com.br.</p>

    <h2>5. Segurança</h2>
    <p>Adotamos medidas técnicas e organizacionais para proteger seus dados: criptografia em trânsito, acesso restrito e backups regulares.</p>

    <h2>6. Contato DPO</h2>
    <p>Para questões de privacidade: suporte@simovafarma.com.br</p>
</div>
@endsection
