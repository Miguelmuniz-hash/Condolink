<?php
// Dashboard administrativa (somente role Admin).
require_once __DIR__ . '/includes/init.php';
$admin = require_admin();

/** Executa uma consulta que devolve um valor; em caso de erro (ex.: migração pendente) devolve null. */
function valor(string $sql, array $params = [])
{
    try {
        $q = db()->prepare($sql);
        $q->execute($params);
        $v = $q->fetchColumn();
        return $v === false ? 0 : $v;
    } catch (Throwable $erro) {
        error_log('admin: ' . $erro->getMessage());
        return null;
    }
}

function linhas(string $sql): array
{
    try {
        return db()->query($sql)->fetchAll();
    } catch (Throwable $erro) {
        error_log('admin: ' . $erro->getMessage());
        return [];
    }
}

function num($v): string
{
    return $v === null ? '—' : number_format((float) $v, 0, ',', '.');
}

$totalUsuarios  = valor("SELECT COUNT(*) FROM usuarios WHERE sts <> 'Excluido'");
$novos7         = valor("SELECT COUNT(*) FROM usuarios WHERE criado_em >= (NOW() - INTERVAL 7 DAY)");
$assAtivas      = valor("SELECT COUNT(*) FROM assinaturas WHERE status = 'ativa'");
$assPendentes   = valor("SELECT COUNT(*) FROM assinaturas WHERE status = 'pendente'");
$mrr            = valor("SELECT COALESCE(SUM(valor_mensal),0) FROM assinaturas WHERE status = 'ativa'");
$receita30      = valor("SELECT COALESCE(SUM(valor_total),0) FROM transacoes WHERE status = 'aprovada' AND criado_em >= (NOW() - INTERVAL 30 DAY)");
$condominios    = valor('SELECT COUNT(*) FROM condominios');
$unidades       = valor('SELECT COUNT(*) FROM unidades');
$areas          = valor('SELECT COUNT(*) FROM areas_comuns');
$comunicados    = valor('SELECT COUNT(*) FROM comunicados');

$reservas = ['pendente' => 0, 'confirmada' => 0, 'cancelada' => 0];
foreach (linhas('SELECT status_reserva AS s, COUNT(*) AS n FROM reservas GROUP BY status_reserva') as $r) {
    if (isset($reservas[$r['s']])) {
        $reservas[$r['s']] = (int) $r['n'];
    }
}

$porPlano = linhas(
    "SELECT p.nome, COUNT(a.id) AS n
       FROM planos p LEFT JOIN assinaturas a ON a.id_plano = p.id AND a.status = 'ativa'
      GROUP BY p.id, p.nome ORDER BY p.preco_mensal"
);
$maxPlano = max(1, ...array_map(fn($p) => (int) $p['n'], $porPlano ?: [['n' => 1]]));

$ultimosUsuarios = linhas('SELECT nome, email, tipo, criado_em FROM usuarios ORDER BY id DESC LIMIT 5');
$ultimasTransacoes = linhas(
    "SELECT t.id, t.metodo, t.valor_total, t.status, t.criado_em, c.nome AS cliente, p.nome AS plano
       FROM transacoes t
       JOIN assinaturas a ON a.id = t.id_assinatura
       JOIN clientes c ON c.id = a.id_cliente
       JOIN planos p ON p.id = a.id_plano
      ORDER BY t.id DESC LIMIT 8"
);

$titulo = 'Painel administrativo';
require __DIR__ . '/includes/header.php';
?>

<section class="pagina-interna pagina-larga">
    <header class="pagina-topo">
        <h1>Painel <span>administrativo</span></h1>
        <p>Visão geral da plataforma CondoLink.</p>
    </header>

<?php if ($totalUsuarios === null || $assAtivas === null): ?>
    <div class="alerta alerta-erro">Parte dos dados não pôde ser lida. Confirme se o script <code>database/migracao_v2.sql</code> foi executado no banco.</div>
<?php endif; ?>

    <nav class="admin-atalhos" aria-label="Atalhos do admin">
        <a class="btn-azul" href="admin_usuarios.php">Gerenciar usuários</a>
    </nav>

    <div class="cards-metricas">
        <article class="metrica"><span>Usuários</span><strong><?= num($totalUsuarios) ?></strong><small>+<?= num($novos7) ?> nos últimos 7 dias</small></article>
        <article class="metrica"><span>Assinaturas ativas</span><strong><?= num($assAtivas) ?></strong><small><?= num($assPendentes) ?> aguardando pagamento</small></article>
        <article class="metrica"><span>Receita mensal recorrente</span><strong><?= $mrr === null ? '—' : e(moeda($mrr)) ?></strong><small>soma dos planos ativos</small></article>
        <article class="metrica"><span>Receita aprovada (30 dias)</span><strong><?= $receita30 === null ? '—' : e(moeda($receita30)) ?></strong><small>transações aprovadas</small></article>
        <article class="metrica"><span>Condomínios</span><strong><?= num($condominios) ?></strong><small><?= num($unidades) ?> unidades</small></article>
        <article class="metrica"><span>Áreas comuns</span><strong><?= num($areas) ?></strong><small><?= num($comunicados) ?> comunicados enviados</small></article>
    </div>

    <div class="admin-duas-colunas">
        <article class="card-painel">
            <h2>Reservas por status</h2>
            <ul class="lista-barras">
<?php $maxRes = max(1, ...array_values($reservas)); foreach ($reservas as $status => $n): ?>
                <li><span class="rot"><?= e(ucfirst($status)) ?></span>
                    <span class="barra"><i style="width: <?= (int) round($n / $maxRes * 100) ?>%"></i></span>
                    <span class="val"><?= (int) $n ?></span></li>
<?php endforeach; ?>
            </ul>
        </article>

        <article class="card-painel">
            <h2>Assinaturas ativas por plano</h2>
<?php if ($porPlano): ?>
            <ul class="lista-barras">
<?php foreach ($porPlano as $p): ?>
                <li><span class="rot"><?= e($p['nome']) ?></span>
                    <span class="barra"><i style="width: <?= (int) round($p['n'] / $maxPlano * 100) ?>%"></i></span>
                    <span class="val"><?= (int) $p['n'] ?></span></li>
<?php endforeach; ?>
            </ul>
<?php else: ?>
            <p>Sem dados de planos ainda.</p>
<?php endif; ?>
        </article>
    </div>

    <article class="card-painel">
        <h2>Últimas transações</h2>
        <div class="tabela-wrap">
            <table class="tabela tabela-resp">
                <thead><tr><th>#</th><th>Cliente</th><th>Plano</th><th>Método</th><th>Valor</th><th>Status</th><th>Data</th></tr></thead>
                <tbody>
<?php foreach ($ultimasTransacoes as $t): ?>
                    <tr>
                        <td data-label="#"><?= (int) $t['id'] ?></td>
                        <td data-label="Cliente"><?= e($t['cliente']) ?></td>
                        <td data-label="Plano"><?= e($t['plano']) ?></td>
                        <td data-label="Método"><?= e(strtoupper($t['metodo'])) ?></td>
                        <td data-label="Valor"><?= e(moeda($t['valor_total'])) ?></td>
                        <td data-label="Status"><span class="selo selo-<?= e($t['status']) ?>"><?= e(ucfirst($t['status'])) ?></span></td>
                        <td data-label="Data"><?= e(date('d/m/Y H:i', strtotime($t['criado_em']))) ?></td>
                    </tr>
<?php endforeach; ?>
<?php if (!$ultimasTransacoes): ?>
                    <tr><td colspan="7">Nenhuma transação ainda.</td></tr>
<?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>

    <article class="card-painel">
        <h2>Últimos cadastros</h2>
        <div class="tabela-wrap">
            <table class="tabela tabela-resp">
                <thead><tr><th>Nome</th><th>E-mail</th><th>Perfil</th><th>Cadastro</th></tr></thead>
                <tbody>
<?php foreach ($ultimosUsuarios as $u): ?>
                    <tr>
                        <td data-label="Nome"><?= e($u['nome']) ?></td>
                        <td data-label="E-mail"><?= e($u['email']) ?></td>
                        <td data-label="Perfil"><?= e($u['tipo']) ?></td>
                        <td data-label="Cadastro"><?= $u['criado_em'] ? e(date('d/m/Y H:i', strtotime($u['criado_em']))) : '—' ?></td>
                    </tr>
<?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>

<?php require __DIR__ . '/includes/footer.php';
