<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/planos.php';
require_once __DIR__ . '/includes/gateway.php';
$usuario = require_login();

// Precisa ter escolhido um plano e preenchido os dados do cliente
$plano = isset($_SESSION['checkout_plano']) ? buscar_plano($_SESSION['checkout_plano']) : null;
if (!$plano) {
    flash_set('info', 'Escolha um plano para continuar.');
    redirect('plano.php');
}
$q = db()->prepare('SELECT * FROM clientes WHERE id_usuario = ?');
$q->execute([$usuario['id']]);
$cliente = $q->fetch();
if (!$cliente) {
    flash_set('info', 'Preencha seus dados para continuar.');
    redirect('confirmar_email.php');
}

$erro   = '';
$ciclo  = in_array($_POST['ciclo'] ?? 'mensal', ['mensal', 'anual'], true) ? ($_POST['ciclo'] ?? 'mensal') : 'mensal';
$metodo = in_array($_POST['metodo'] ?? 'cartao', ['cartao', 'pix'], true) ? ($_POST['metodo'] ?? 'cartao') : 'cartao';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) {
        $erro = 'Sessão expirada. Recarregue a página e tente novamente.';
    } elseif (!($plano['id'] > 0)) {
        $erro = 'Os planos ainda não foram criados no banco. Rode database/migracao_v2.sql.';
    } else {
        $v   = calcular_valores($plano, $ciclo, $metodo);
        $pdo = db();
        try {
            $pdo->beginTransaction();

            $ini = date('Y-m-d');
            $fim = date('Y-m-d', strtotime($ciclo === 'anual' ? '+1 year' : '+1 month'));

            $pdo->prepare(
                "INSERT INTO assinaturas (id_cliente, id_plano, status, ciclo, valor_mensal, inicio, fim)
                 VALUES (?, ?, 'pendente', ?, ?, ?, ?)"
            )->execute([$cliente['id'], $plano['id'], $ciclo, $plano['preco_mensal'], $ini, $fim]);
            $idAssinatura = (int) $pdo->lastInsertId();

            $pdo->prepare(
                "INSERT INTO transacoes (id_assinatura, metodo, valor_bruto, desconto, valor_total, status)
                 VALUES (?, ?, ?, ?, ?, 'pendente')"
            )->execute([$idAssinatura, $metodo, $v['base'] / 100, $v['desc_pix'] / 100, $v['total'] / 100]);
            $idTransacao = (int) $pdo->lastInsertId();

            $resultado = processar_pagamento($metodo, $v['total'], $idTransacao);

            $pdo->prepare('UPDATE transacoes SET status = ?, referencia = ? WHERE id = ?')
                ->execute([$resultado['status'], $resultado['referencia'], $idTransacao]);

            if ($resultado['status'] === 'aprovada') {
                // Só uma assinatura ativa por cliente: as anteriores são encerradas
                $pdo->prepare("UPDATE assinaturas SET status = 'cancelada' WHERE id_cliente = ? AND status = 'ativa' AND id <> ?")
                    ->execute([$cliente['id'], $idAssinatura]);
                $pdo->prepare("UPDATE assinaturas SET status = 'ativa' WHERE id = ?")->execute([$idAssinatura]);
            } elseif ($resultado['status'] === 'recusada') {
                $pdo->prepare("UPDATE assinaturas SET status = 'cancelada' WHERE id = ?")->execute([$idAssinatura]);
            }
            $pdo->commit();

            unset($_SESSION['checkout_plano']);
            if ($resultado['status'] === 'aprovada') {
                flash_set('sucesso', 'Pagamento aprovado! Seu plano ' . $plano['nome'] . ' está ativo.');
                redirect('painel.php');
            } elseif ($resultado['status'] === 'pendente') {
                flash_set('info', 'Recebemos seu pedido. A assinatura será ativada assim que o pagamento for confirmado.');
                redirect('painel.php');
            }
            $erro = 'O pagamento foi recusado. Confira os dados ou tente outro método.';
        } catch (Throwable $falha) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('pagamento: ' . $falha->getMessage());
            $erro = 'Não foi possível processar o pagamento agora. Tente novamente.';
        }
    }
}

$vCartao = calcular_valores($plano, $ciclo, 'cartao');
$vPix    = calcular_valores($plano, $ciclo, 'pix');
$mensal  = (int) round(((float) $plano['preco_mensal']) * 100);
$anual   = (int) round($mensal * 12 * (1 - ((float) DESCONTO_ANUAL) / 100));

$titulo = 'Pagamento';
require __DIR__ . '/includes/header.php';
$check = 'https://www.image2url.com/r2/default/images/1789341600421-2ab05415-cdd6-4e92-a1e8-d4e193e1e8d6.png';
?>

<section class="checkout" data-mensal="<?= $mensal ?>" data-anual="<?= $anual ?>" data-pix="<?= (float) DESCONTO_PIX ?>">
    <header class="checkout-topo">
        <h1>Finalizar Compra</h1>
        <p>Complete os dados abaixo para garantir o plano <strong><?= e($plano['nome']) ?></strong>.</p>
    </header>

<?php if (PAGAMENTO_SIMULADO === '1'): ?>
    <div class="alerta alerta-info">Ambiente de demonstração: nenhuma cobrança real é feita e nenhum dado de cartão é enviado ao servidor.</div>
<?php endif; ?>
<?php if ($erro): ?>
    <div class="alerta alerta-erro" role="alert"><?= e($erro) ?></div>
<?php endif; ?>

    <form class="checkout-grid" action="pagamento.php" method="POST" id="form-pagamento" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="metodo" id="metodo" value="<?= e($metodo) ?>">

        <div class="checkout-card">
            <fieldset class="ciclo">
                <legend>Ciclo de cobrança</legend>
                <label class="opcao"><input type="radio" name="ciclo" value="mensal" <?= $ciclo === 'mensal' ? 'checked' : '' ?>><span>Mensal</span></label>
                <label class="opcao"><input type="radio" name="ciclo" value="anual" <?= $ciclo === 'anual' ? 'checked' : '' ?>><span>Anual</span></label>
            </fieldset>

            <div class="checkout-abas" role="tablist">
                <button type="button" role="tab" class="aba <?= $metodo === 'cartao' ? 'ativa' : '' ?>" data-metodo="cartao" aria-selected="<?= $metodo === 'cartao' ? 'true' : 'false' ?>">Cartão</button>
                <button type="button" role="tab" class="aba <?= $metodo === 'pix' ? 'ativa' : '' ?>" data-metodo="pix" aria-selected="<?= $metodo === 'pix' ? 'true' : 'false' ?>">PIX <small>-<?= e((int) DESCONTO_PIX) ?>%</small></button>
            </div>

            <div id="area_cartao" class="<?= $metodo === 'cartao' ? '' : 'oculto' ?>">
                <h2 class="checkout-titulo">Pagamento via Cartão</h2>
                <div class="grid-campos">
                    <div class="campo-ck campo-largo">
                        <label for="cc-nome">Nome no cartão</label>
                        <input type="text" id="cc-nome" placeholder="Como impresso no cartão" autocomplete="cc-name">
                    </div>
                    <div class="campo-ck campo-largo">
                        <label for="cc-numero">Número do cartão</label>
                        <input type="text" id="cc-numero" placeholder="0000 0000 0000 0000" inputmode="numeric" maxlength="19" autocomplete="cc-number">
                    </div>
                    <div class="campo-ck">
                        <label for="cc-validade">Validade (MM/AA)</label>
                        <input type="text" id="cc-validade" placeholder="MM/AA" inputmode="numeric" maxlength="5" autocomplete="cc-exp">
                    </div>
                    <div class="campo-ck">
                        <label for="cc-cvv">CVV</label>
                        <input type="password" id="cc-cvv" placeholder="123" inputmode="numeric" maxlength="4" autocomplete="cc-csc">
                    </div>
                </div>
                <p class="erro-cartao campo-erro" id="erro-cartao" hidden></p>
            </div>

            <div id="area_PIX" class="<?= $metodo === 'pix' ? '' : 'oculto' ?>">
                <h2 class="checkout-titulo">Pagamento via PIX</h2>
                <div class="qr_code">
                    <img src="https://www.image2url.com/r2/default/images/1789408915246-0bf0a8e3-be44-4b6a-a05a-274ca83733e6.png" alt="QR Code PIX de exemplo">
                    <p>Abra o aplicativo do seu banco, escolha pagar com PIX e aponte a câmera para o código.</p>
                </div>
            </div>

            <button type="submit" class="btn-finalizar">Finalizar Pagamento</button>
            <p class="seguro">Pagamento seguro e criptografado</p>
        </div>

        <aside class="checkout-card checkout-resumo">
            <h2 class="checkout-titulo">Resumo do pedido</h2>
            <p class="resumo-plano"><?= e($plano['nome']) ?></p>
            <p class="resumo-ciclo" id="resumo-ciclo"><?= $ciclo === 'anual' ? 'Faturamento anual' : 'Faturamento mensal' ?></p>
            <ul class="resumo-itens">
<?php foreach (array_slice(itens_do_plano($plano), 0, 4) as $item): ?>
                <li><img src="<?= e($check) ?>" alt=""><?= e($item) ?></li>
<?php endforeach; ?>
            </ul>
            <dl class="resumo-linhas">
                <div><dt>Subtotal</dt><dd id="r-subtotal"><?= e(centavos($vCartao['base'])) ?></dd></div>
                <div id="linha-pix" <?= $metodo === 'pix' ? '' : 'hidden' ?>><dt>Desconto PIX (<?= e((int) DESCONTO_PIX) ?>%)</dt><dd id="r-desconto">-<?= e(centavos($vPix['desc_pix'])) ?></dd></div>
                <div class="resumo-total"><dt>Total a pagar</dt><dd id="r-total"><?= e(centavos($metodo === 'pix' ? $vPix['total'] : $vCartao['total'])) ?></dd></div>
            </dl>
        </aside>
    </form>
</section>

<?php
$jsExtra = ['js/pagamento.js'];
require __DIR__ . '/includes/footer.php';
