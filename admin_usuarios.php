<?php
// Gestão de usuários (somente role Admin): busca, paginação e alteração de perfil/status.
require_once __DIR__ . '/includes/init.php';
$admin = require_admin();

const TIPOS   = ['Morador', 'Sindico', 'Admin'];
const STATUS  = ['Ativo', 'Inativo', 'Pendente', 'Excluido'];
const POR_PAG = 15;

$busca  = trim((string) ($_GET['q'] ?? ''));
$pagina = max(1, (int) ($_GET['p'] ?? 1));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idAlvo = (int) ($_POST['id'] ?? 0);
    $tipo   = (string) ($_POST['tipo'] ?? '');
    $sts    = (string) ($_POST['sts'] ?? '');
    $volta  = 'admin_usuarios.php?' . http_build_query(['q' => $busca, 'p' => $pagina]);

    if (!csrf_ok()) {
        flash_set('erro', 'Sessão expirada. Tente novamente.');
    } elseif ($idAlvo === (int) $admin['id']) {
        flash_set('erro', 'Você não pode alterar a sua própria conta por aqui.');
    } elseif (!in_array($tipo, TIPOS, true) || !in_array($sts, STATUS, true)) {
        flash_set('erro', 'Valores inválidos.');
    } else {
        $q = db()->prepare('SELECT tipo FROM usuarios WHERE id = ?');
        $q->execute([$idAlvo]);
        $atual = $q->fetchColumn();
        $admins = (int) db()->query("SELECT COUNT(*) FROM usuarios WHERE tipo = 'Admin' AND sts = 'Ativo'")->fetchColumn();

        if ($atual === false) {
            flash_set('erro', 'Usuário não encontrado.');
        } elseif ($atual === 'Admin' && ($tipo !== 'Admin' || $sts !== 'Ativo') && $admins <= 1) {
            flash_set('erro', 'É preciso manter pelo menos um administrador ativo.');
        } else {
            db()->prepare('UPDATE usuarios SET tipo = ?, sts = ? WHERE id = ?')->execute([$tipo, $sts, $idAlvo]);
            flash_set('sucesso', 'Usuário atualizado.');
        }
    }
    redirect($volta);
}

$where = '';
$params = [];
if ($busca !== '') {
    $where = 'WHERE nome LIKE ? OR email LIKE ?';
    $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $busca) . '%';
    $params = [$like, $like];
}

$q = db()->prepare("SELECT COUNT(*) FROM usuarios $where");
$q->execute($params);
$total = (int) $q->fetchColumn();
$paginas = max(1, (int) ceil($total / POR_PAG));
$pagina = min($pagina, $paginas);

$q = db()->prepare("SELECT id, nome, email, tipo, sts, criado_em FROM usuarios $where ORDER BY id DESC LIMIT " . POR_PAG . ' OFFSET ' . (($pagina - 1) * POR_PAG));
$q->execute($params);
$usuarios = $q->fetchAll();

$titulo = 'Usuários';
require __DIR__ . '/includes/header.php';
?>

<section class="pagina-interna pagina-larga">
    <header class="pagina-topo">
        <h1>Gerenciar <span>usuários</span></h1>
        <p><?= $total ?> usuário(s) encontrado(s). <a href="admin.php">Voltar ao painel</a></p>
    </header>

    <form class="busca-admin" method="GET" action="admin_usuarios.php">
        <input type="search" name="q" value="<?= e($busca) ?>" placeholder="Buscar por nome ou e-mail" aria-label="Buscar usuários">
        <button type="submit" class="btn-azul">Buscar</button>
    </form>

    <div class="card-painel">
        <div class="tabela-wrap">
            <table class="tabela tabela-resp">
                <thead><tr><th>Nome</th><th>E-mail</th><th>Perfil</th><th>Status</th><th>Ação</th></tr></thead>
                <tbody>
<?php foreach ($usuarios as $u): $proprio = (int) $u['id'] === (int) $admin['id']; ?>
                    <tr>
                        <td data-label="Nome">
                            <form method="POST" action="admin_usuarios.php?<?= e(http_build_query(['q' => $busca, 'p' => $pagina])) ?>" id="f<?= (int) $u['id'] ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                            </form>
                            <?= e($u['nome']) ?><?= $proprio ? ' <em>(você)</em>' : '' ?>
                        </td>
                        <td data-label="E-mail"><?= e($u['email']) ?></td>
                        <td data-label="Perfil">
                            <select name="tipo" form="f<?= (int) $u['id'] ?>" <?= $proprio ? 'disabled' : '' ?> aria-label="Perfil de <?= e($u['nome']) ?>">
<?php foreach (TIPOS as $t): ?>
                                <option value="<?= e($t) ?>" <?= $u['tipo'] === $t ? 'selected' : '' ?>><?= e($t) ?></option>
<?php endforeach; ?>
                            </select>
                        </td>
                        <td data-label="Status">
                            <select name="sts" form="f<?= (int) $u['id'] ?>" <?= $proprio ? 'disabled' : '' ?> aria-label="Status de <?= e($u['nome']) ?>">
<?php foreach (STATUS as $s): ?>
                                <option value="<?= e($s) ?>" <?= $u['sts'] === $s ? 'selected' : '' ?>><?= e($s) ?></option>
<?php endforeach; ?>
                            </select>
                        </td>
                        <td data-label="Ação"><button type="submit" form="f<?= (int) $u['id'] ?>" class="btn-azul btn-pequeno" <?= $proprio ? 'disabled' : '' ?>>Salvar</button></td>
                    </tr>
<?php endforeach; ?>
<?php if (!$usuarios): ?>
                    <tr><td colspan="5">Nenhum usuário encontrado.</td></tr>
<?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php if ($paginas > 1): ?>
    <nav class="paginacao" aria-label="Paginação">
<?php if ($pagina > 1): ?><a href="?<?= e(http_build_query(['q' => $busca, 'p' => $pagina - 1])) ?>">&laquo; Anterior</a><?php endif; ?>
        <span>Página <?= $pagina ?> de <?= $paginas ?></span>
<?php if ($pagina < $paginas): ?><a href="?<?= e(http_build_query(['q' => $busca, 'p' => $pagina + 1])) ?>">Próxima &raquo;</a><?php endif; ?>
    </nav>
<?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php';
