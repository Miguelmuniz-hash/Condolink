<?php
$titulo = 'Planos';
require_once __DIR__ . '/includes/planos.php';
$planos = listar_planos();
require __DIR__ . '/includes/header.php';

$icone = 'https://www.image2url.com/r2/default/images/1789666697739-1e8600a5-2ea4-4919-a2ea-89941384d684.png';
?>

<div class="banner_plano">
    <h1>Escolha o plano ideal para o seu <span>condomínio</span></h1>
    <p>Do pequeno condomínio às grandes administradoras: reservas de áreas comuns, comunicados e gestão em um só lugar.</p>
</div>

<section class="assinaturas_palno">
<?php foreach ($planos as $plano):
    $destaque = !empty($plano['destaque']);
    $cardClasse = $destaque ? 'div2_plano' : 'div1_plano';
    $listaClasse = $destaque ? 'topicos_azul' : 'topicos_planos';
?>
    <div class="<?= $cardClasse ?>">
<?php if ($destaque): ?>
        <span class="selo-popular">Mais escolhido</span>
<?php endif; ?>
        <h2><span><?= e($plano['nome']) ?></span> <br><span><?= e(moeda($plano['preco_mensal'])) ?> </span><span>/mês</span></h2>
        <p><?= e($plano['descricao']) ?></p>

        <div class="<?= $listaClasse ?>">
            <ul>
<?php foreach (itens_do_plano($plano) as $item): ?>
                <li><img src="<?= e($icone) ?>" alt="" /><?= e($item) ?></li>
<?php endforeach; ?>
            </ul>
        </div>

        <a href="confirmar_email.php?plano=<?= e(urlencode($plano['slug'])) ?>">Assinar <?= e($plano['nome']) ?></a>
    </div>
<?php endforeach; ?>
</section>

<p class="plano-rodape">Pague no PIX e ganhe <?= e((int) DESCONTO_PIX) ?>% de desconto. Tem dúvidas? Veja as <a href="faq.php">perguntas frequentes</a>.</p>

<?php require __DIR__ . '/includes/footer.php';
