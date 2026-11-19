<?php
require_once __DIR__ . '/includes/auth.php';

$token = (string) ($_GET['token'] ?? ($_POST['token'] ?? ''));
$linha = reset_buscar_token($token);
$erro  = '';

if ($linha && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha     = (string) ($_POST['senha'] ?? '');
    $confirmar = (string) ($_POST['senha2'] ?? '');

    if (!csrf_ok()) {
        $erro = 'Sessão expirada. Recarregue a página e tente novamente.';
    } elseif ($msg = validar_senha($senha)) {
        $erro = $msg;
    } elseif ($senha !== $confirmar) {
        $erro = 'As senhas não conferem.';
    } else {
        $pdo = db();
        $pdo->beginTransaction();
        $pdo->prepare('UPDATE usuarios SET senha_hash = ? WHERE id = ?')
            ->execute([password_hash($senha, PASSWORD_DEFAULT), $linha['id_usuario']]);
        $pdo->prepare('UPDATE password_resets SET usado_em = NOW() WHERE id_usuario = ? AND usado_em IS NULL')
            ->execute([$linha['id_usuario']]);
        $pdo->prepare('DELETE FROM login_tentativas WHERE email = (SELECT email FROM usuarios WHERE id = ?)')
            ->execute([$linha['id_usuario']]);
        $pdo->commit();

        flash_set('sucesso', 'Senha alterada com sucesso. Entre com a nova senha.');
        redirect('login.php');
    }
}

$titulo = 'Nova senha';
$particles = true;
require __DIR__ . '/includes/header.php';
?>

<section>
  <form action="redefinir_senha.php" method="POST" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="token" value="<?= e($token) ?>">
    <div class="login_fiv">
        <img src="https://www.image2url.com/r2/default/images/1793729008704-cca144e8-6c45-4656-b8cb-d4772507c1a5.png" alt="CondoLink" />
        <h1>Nova senha</h1>

<?php if (!$linha): ?>
        <div class="alerta alerta-erro" role="alert">Este link é inválido ou expirou. Peça um novo para continuar.</div>
        <div class="petgunta_login"><a href="esqueci_senha.php">Pedir novo link</a></div>
<?php else: ?>
<?php if ($erro): ?>
        <div class="alerta alerta-erro" role="alert"><?= e($erro) ?></div>
<?php endif; ?>
        <label for="senha">Nova senha</label>
        <input type="password" id="senha" name="senha" placeholder="Mínimo 8 caracteres, com letras e números" autocomplete="new-password" minlength="8" maxlength="72" required autofocus>

        <label for="senha2">Confirmar nova senha</label>
        <input type="password" id="senha2" name="senha2" placeholder="Repita a senha" autocomplete="new-password" required>

        <button type="submit">Salvar nova senha</button>
<?php endif; ?>
    </div>
  </form>
</section>

<?php
$jsExtra = ['https://cdn.jsdelivr.net/npm/tsparticles-slim@2.12.0/tsparticles.slim.bundle.min.js', 'js/particles-config.js'];
require __DIR__ . '/includes/footer.php';
