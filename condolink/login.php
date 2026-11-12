<?php
require_once __DIR__ . '/includes/auth.php';

if (usuario_logado()) {
    redirect(usuario_admin() ? 'admin.php' : 'painel.php');
}

$erro  = '';
$email = '';
$next  = destino_seguro($_GET['next'] ?? ($_POST['next'] ?? null), '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
    $senha = (string) ($_POST['senha'] ?? '');

    if (!csrf_ok()) {
        $erro = 'Sessão expirada. Recarregue a página e tente novamente.';
    } elseif ($email === '' || $senha === '') {
        $erro = 'Informe e-mail e senha.';
    } elseif (login_bloqueado($email)) {
        $erro = 'Muitas tentativas incorretas. Aguarde ' . LOGIN_JANELA_MIN . ' minutos ou use "Esqueci minha senha".';
    } else {
        $q = db()->prepare('SELECT id, nome, email, senha_hash, tipo, sts FROM usuarios WHERE email = ? LIMIT 1');
        $q->execute([$email]);
        $usuario = $q->fetch();

        // password_verify roda sempre (mesmo sem usuário) para não revelar se o e-mail existe pelo tempo de resposta
        $hash  = $usuario['senha_hash'] ?? password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
        $certa = password_verify($senha, $hash) && $usuario;

        if ($certa && $usuario['sts'] === 'Ativo') {
            login_registrar($email, true);
            if (password_needs_rehash($usuario['senha_hash'], PASSWORD_DEFAULT)) {
                db()->prepare('UPDATE usuarios SET senha_hash = ? WHERE id = ?')
                    ->execute([password_hash($senha, PASSWORD_DEFAULT), $usuario['id']]);
            }
            iniciar_sessao($usuario);
            if ($next !== '') {
                redirect($next);
            }
            redirect($usuario['tipo'] === 'Admin' ? 'admin.php' : 'painel.php');
        }

        login_registrar($email, false);
        $erro = $certa ? 'Sua conta não está ativa. Entre em contato com o suporte.' : 'E-mail ou senha incorretos.';
    }
}

$titulo = 'Login';
$particles = true;
require __DIR__ . '/includes/header.php';
?>

<section>
  <form action="login.php" method="POST" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="next" value="<?= e($next) ?>">
    <div class="login_fiv">
        <img src="https://www.image2url.com/r2/default/images/1793729008704-cca144e8-6c45-4656-b8cb-d4772507c1a5.png" alt="CondoLink" />
        <h1>Login</h1>

<?php if ($erro): ?>
        <div class="alerta alerta-erro" role="alert"><?= e($erro) ?></div>
<?php endif; ?>

        <label for="email">E-mail</label>
        <input type="email" id="email" name="email" placeholder="Digite seu E-mail" value="<?= e($email) ?>" autocomplete="email" required autofocus>

        <label for="senha">Senha</label>
        <input type="password" id="senha" name="senha" placeholder="Digite sua senha" autocomplete="current-password" required>

        <a class="link-esqueci" href="esqueci_senha.php">Esqueci minha senha</a>

        <button type="submit">Entrar</button>

        <div class="petgunta_login">
            <p>Não tem Login?</p><a href="cadastro.php">Cadastro</a>
        </div>
    </div>
  </form>
</section>

<?php
$jsExtra = ['https://cdn.jsdelivr.net/npm/tsparticles-slim@2.12.0/tsparticles.slim.bundle.min.js', 'js/particles-config.js'];
require __DIR__ . '/includes/footer.php';
