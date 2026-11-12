<?php
// Gateway de pagamento. ATENÇÃO: este arquivo é um STUB.
//
// Com PAGAMENTO_SIMULADO=1 (padrão) nenhuma cobrança real acontece: a transação é
// aprovada na hora para você poder testar o fluxo completo.
// Para cobrar de verdade, troque o corpo de processar_pagamento() pela integração do seu
// provedor (Mercado Pago, Stripe, Pagar.me, Asaas...). Dados de cartão NUNCA devem passar
// pelo seu servidor: use o checkout/tokenização do provedor e guarde apenas o token/ID.
require_once __DIR__ . '/config.php';

/**
 * @return array{status:string,referencia:string}  status: aprovada | pendente | recusada
 */
function processar_pagamento(string $metodo, int $totalCentavos, int $idTransacao): array
{
    if (PAGAMENTO_SIMULADO === '1') {
        return ['status' => 'aprovada', 'referencia' => 'SIM-' . $idTransacao . '-' . bin2hex(random_bytes(4))];
    }
    // TODO: integrar o provedor real aqui. Enquanto isso, a cobrança fica pendente.
    return ['status' => 'pendente', 'referencia' => ''];
}
