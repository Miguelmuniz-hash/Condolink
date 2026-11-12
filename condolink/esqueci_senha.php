<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/mailer.php';

if (usuario_logado()) {
    redirect('painel.php');
}

$enviado = false;
$erro    = '';
$email   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));

    if (!csrf_ok()) {
        $erro = 'Sessão expirada. Recarregue a página e tente novamente.';
    } elseif ($msg = validar_email($email)) {
        $erro = $msg;
    } else {
        // Resposta sempre igual, exista o e-mail ou não (evita descobrir quem tem conta)
        $q = db()->prepare("SELECT id, nome FROM usuarios WHERE email = ? AND sts = 'Ativo' LIMIT 1");
        $q->execute([$email]);
        $usuario = $q->fetch();

        if ($usuario && !reset_excedeu_limite((int) $usuario['id'])) {
            $token = reset_criar_token((int) $usuario['id']);
            $link  = app_url() . '/redefinir_senha.php?token=' . $token;
            if (!enviar_email_redefinicao($email, $usuario['nome'], $link) && APP_DEBUG === '1') {
                error_log('[CondoLink] Link de redefinição (debug): ' . $link);
            }
        }
        $enviado = true;
    }
}

$titulo = 'Esqueci minha senha';
$particles = true;
require __DIR__ . '/includes/header.php';
?>

<section>
  <form action="esqueci_senha.php" method="POST" novalidate>
    <?= csrf_field() ?>
    <div class="login_fiv">
        <img src="https://www.image2url.com/r2/default/images/1793729008704-cca144e8-6c45-4656-b8cb-d4772507c1a5.png" alt="CondoLink" />
        <h1>Esqueci a senha</h1>

<?php if ($enviado): ?>
        <div class="alerta alerta-sucesso" role="status">Se esse e-mail estiver cadastrado, enviamos um link para criar uma nova senha. Ele vale por 1 hora. Confira também o spam.</div>
        <div class="petgunta_login"><a href="login.php">Voltar ao login</a></div>
<?php else: ?>
        <p class="texto-apoio">Informe o e-mail da sua conta e enviaremos um link para redefinir a senha.</p>
<?php if ($erro): ?>
        <div class="alerta alerta-erro" role="alert"><?= e($erro) ?></div>
<?php endif; ?>
        <label for="email">E-mail</label>
        <input type="email" id="email" name="email" placeholder="Digite seu E-mail" value="<?= e($email) ?>" autocomplete="email" required autofocus>
        <button type="submit">Enviar link</button>
        <div class="petgunta_login"><a href="login.php">Voltar ao login</a></div>
<?php endif; ?>
    </div>
  </form>
</section>

<?php
$jsExtra = ['https://cdn.jsdelivr.net/npm/tsparticles-slim@2.12.0/tsparticles.slim.bundle.min.js', 'js/particles-config.js'];
require __DIR__ . '/includes/footer.php';
