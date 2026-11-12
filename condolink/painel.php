<?php
// Área logada (aplicação principal após login/cadastro).
require_once __DIR__ . '/includes/init.php';
$usuario = require_login();

$assinatura = null;
try {
    $q = db()->prepare(
        'SELECT a.status, a.ciclo, a.inicio, a.fim, p.nome AS plano
           FROM assinaturas a
           JOIN clientes c ON c.id = a.id_cliente
           JOIN planos p ON p.id = a.id_plano
          WHERE c.id_usuario = ?
          ORDER BY a.id DESC LIMIT 1'
    );
    $q->execute([$usuario['id']]);
    $assinatura = $q->fetch() ?: null;
} catch (Throwable $erro) {
    error_log('painel: ' . $erro->getMessage());
}

$titulo = 'Minha conta';
require __DIR__ . '/includes/header.php';

$primeiroNome = explode(' ', trim($usuario['nome']))[0];
$rotulos = ['ativa' => 'Ativa', 'pendente' => 'Aguardando pagamento', 'cancelada' => 'Cancelada', 'expirada' => 'Expirada'];
?>

<section class="pagina-interna">
    <header class="pagina-topo">
        <h1>Olá, <span><?= e($primeiroNome) ?></span>!</h1>
        <p>Esta é a sua área na CondoLink.</p>
    </header>

    <div class="painel-grid">
        <article class="card-painel">
            <h2>Minha assinatura</h2>
<?php if ($assinatura): ?>
            <p class="card-destaque"><?= e($assinatura['plano']) ?></p>
            <p><span class="selo selo-<?= e($assinatura['status']) ?>"><?= e($rotulos[$assinatura['status']] ?? $assinatura['status']) ?></span>
               · ciclo <?= e($assinatura['ciclo']) ?></p>
<?php if ($assinatura['status'] === 'ativa' && $assinatura['fim']): ?>
            <p>Válida até <?= e(date('d/m/Y', strtotime($assinatura['fim']))) ?></p>
<?php endif; ?>
            <a class="btn-azul" href="plano.php">Ver outros planos</a>
<?php else: ?>
            <p>Você ainda não contratou um plano.</p>
            <a class="btn-azul" href="plano.php">Conhecer os planos</a>
<?php endif; ?>
        </article>

        <article class="card-painel">
            <h2>Meus dados</h2>
            <p><strong><?= e($usuario['nome']) ?></strong></p>
            <p><?= e($usuario['email']) ?></p>
            <p>Perfil: <?= e($usuario['tipo']) ?></p>
        </article>

        <article class="card-painel">
            <h2>Precisa de ajuda?</h2>
            <p>Veja respostas rápidas sobre reservas, planos, pagamentos e segurança dos seus dados.</p>
            <a class="btn-azul" href="faq.php">Abrir o FAQ</a>
        </article>

<?php if ($usuario['tipo'] === 'Admin'): ?>
        <article class="card-painel card-painel-admin">
            <h2>Administração</h2>
            <p>Acompanhe usuários, assinaturas e pagamentos.</p>
            <a class="btn-azul" href="admin.php">Ir para o painel admin</a>
        </article>
<?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php';
