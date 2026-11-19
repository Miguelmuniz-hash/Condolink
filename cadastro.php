<?php
require_once __DIR__ . '/includes/auth.php';

if (usuario_logado()) {
    redirect('painel.php');
}

$erros = [];
$dados = ['nome' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados['nome']  = trim((string) ($_POST['nome'] ?? ''));
    $dados['email'] = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
    $senha          = (string) ($_POST['senha'] ?? '');
    $confirmar      = (string) ($_POST['senha2'] ?? '');
    $aceitePriv     = isset($_POST['aceite_privacidade']);
    $aceiteCookies  = isset($_POST['aceite_cookies']);

    if (!csrf_ok()) {
        $erros['geral'] = 'Sessão expirada. Recarregue a página e tente novamente.';
    }
    if ($msg = validar_nome($dados['nome']))   { $erros['nome']  = $msg; }
    if ($msg = validar_email($dados['email'])) { $erros['email'] = $msg; }
    if ($msg = validar_senha($senha))          { $erros['senha'] = $msg; }
    if ($senha !== $confirmar)                 { $erros['senha2'] = 'As senhas não conferem.'; }
    if (!$aceitePriv || !$aceiteCookies) {
        $erros['termos'] = 'Para criar a conta é obrigatório aceitar os Termos de Privacidade e a Política de Cookies.';
    }

    if (!$erros) {
        try {
            $q = db()->prepare(
                "INSERT INTO usuarios (id_unid, nome, email, senha_hash, tipo, sts, aceite_privacidade_em, aceite_cookies_em)
                 VALUES (NULL, ?, ?, ?, 'Morador', 'Ativo', NOW(), NOW())"
            );
            $q->execute([$dados['nome'], $dados['email'], password_hash($senha, PASSWORD_DEFAULT)]);
            $id = (int) db()->lastInsertId();

            // Login automático: vai direto para a aplicação, sem passar pela tela de login
            iniciar_sessao(['id' => $id, 'nome' => $dados['nome'], 'tipo' => 'Morador']);
            flash_set('sucesso', 'Conta criada com sucesso! Bem-vindo(a) à CondoLink.');
            redirect('painel.php');
        } catch (PDOException $erro) {
            if ($erro->getCode() === '23000') {      // e-mail duplicado (chave UNIQUE)
                $erros['email'] = 'Este e-mail já está cadastrado. Tente entrar ou redefinir a senha.';
            } else {
                error_log('Cadastro: ' . $erro->getMessage());
                $erros['geral'] = 'Não foi possível criar a conta agora. Tente novamente em instantes.';
            }
        }
    }
}

$titulo = 'Cadastro';
$particles = true;
require __DIR__ . '/includes/header.php';
?>

<section>
    <form action="cadastro.php" method="POST" novalidate>
        <?= csrf_field() ?>
        <div class="login_fiv">
            <img src="https://www.image2url.com/r2/default/images/1793729008704-cca144e8-6c45-4656-b8cb-d4772507c1a5.png" alt="CondoLink" />
            <h1>Cadastro</h1>

<?php if (!empty($erros['geral'])): ?>
            <div class="alerta alerta-erro" role="alert"><?= e($erros['geral']) ?></div>
<?php endif; ?>

            <label for="nome">Nome</label>
            <input type="text" id="nome" name="nome" placeholder="Digite seu nome" value="<?= e($dados['nome']) ?>" autocomplete="name" maxlength="100" required>
<?php if (!empty($erros['nome'])): ?><p class="campo-erro"><?= e($erros['nome']) ?></p><?php endif; ?>

            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" placeholder="Digite seu E-mail" value="<?= e($dados['email']) ?>" autocomplete="email" maxlength="100" required>
<?php if (!empty($erros['email'])): ?><p class="campo-erro"><?= e($erros['email']) ?></p><?php endif; ?>

            <label for="senha">Senha</label>
            <input type="password" id="senha" name="senha" placeholder="Mínimo 8 caracteres, com letras e números" autocomplete="new-password" minlength="8" maxlength="72" required>
<?php if (!empty($erros['senha'])): ?><p class="campo-erro"><?= e($erros['senha']) ?></p><?php endif; ?>

            <label for="senha2">Confirmar senha</label>
            <input type="password" id="senha2" name="senha2" placeholder="Repita a senha" autocomplete="new-password" required>
<?php if (!empty($erros['senha2'])): ?><p class="campo-erro"><?= e($erros['senha2']) ?></p><?php endif; ?>

            <div class="termo_cadastro">
                <input type="checkbox" id="aceite_privacidade" name="aceite_privacidade" required>
                <label for="aceite_privacidade">Li e aceito os <a href="termos.php#privacidade" target="_blank" rel="noopener">Termos de Privacidade</a></label>
            </div>
            <div class="termo_cadastro">
                <input type="checkbox" id="aceite_cookies" name="aceite_cookies" required>
                <label for="aceite_cookies">Li e aceito a <a href="termos.php#cookies" target="_blank" rel="noopener">Política de Cookies</a></label>
            </div>
<?php if (!empty($erros['termos'])): ?><p class="campo-erro"><?= e($erros['termos']) ?></p><?php endif; ?>

            <button type="submit">Cadastrar</button>

            <div class="petgunta_login">
                <p>Já tem uma conta?</p><a href="login.php">Login</a>
            </div>
        </div>
    </form>
</section>

<?php
$jsExtra = ['https://cdn.jsdelivr.net/npm/tsparticles-slim@2.12.0/tsparticles.slim.bundle.min.js', 'js/particles-config.js'];
require __DIR__ . '/includes/footer.php';
