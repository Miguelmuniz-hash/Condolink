<?php
// Etapa "Seus dados" do checkout (nome do arquivo mantido para não quebrar links).
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/planos.php';
$usuario = require_login();

// Plano escolhido na página de planos
if (isset($_GET['plano'])) {
    $plano = buscar_plano((string) $_GET['plano']);
    if ($plano) {
        $_SESSION['checkout_plano'] = $plano['slug'];
    }
}
$plano = isset($_SESSION['checkout_plano']) ? buscar_plano($_SESSION['checkout_plano']) : null;
if (!$plano) {
    flash_set('info', 'Escolha um plano para continuar.');
    redirect('plano.php');
}

// Preenche com o que já existe no banco
$q = db()->prepare('SELECT nome, email, telefone, cpf, cep, endereco FROM clientes WHERE id_usuario = ?');
$q->execute([$usuario['id']]);
$cliente = $q->fetch() ?: [];

$dados = [
    'nome'     => $cliente['nome']     ?? $usuario['nome'],
    'email'    => $cliente['email']    ?? $usuario['email'],
    'telefone' => $cliente['telefone'] ?? '',
    'cpf'      => $cliente['cpf']      ?? '',
    'cep'      => $cliente['cep']      ?? '',
    'endereco' => $cliente['endereco'] ?? '',
];
$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['nome', 'email', 'telefone', 'cpf', 'cep', 'endereco'] as $campo) {
        $dados[$campo] = trim((string) ($_POST[$campo] ?? ''));
    }
    $dados['email'] = mb_strtolower($dados['email']);

    if (!csrf_ok())                                  { $erros['geral'] = 'Sessão expirada. Recarregue a página.'; }
    if ($m = validar_nome($dados['nome']))           { $erros['nome'] = $m; }
    if ($m = validar_email($dados['email']))         { $erros['email'] = $m; }
    $tel = so_digitos($dados['telefone']);
    if (strlen($tel) < 10 || strlen($tel) > 11)      { $erros['telefone'] = 'Informe o telefone com DDD.'; }
    if (!cpf_valido($dados['cpf']))                  { $erros['cpf'] = 'CPF inválido.'; }
    $cep = so_digitos($dados['cep']);
    if (strlen($cep) !== 8)                          { $erros['cep'] = 'CEP deve ter 8 números.'; }
    if (mb_strlen($dados['endereco']) < 5 || mb_strlen($dados['endereco']) > 255) {
        $erros['endereco'] = 'Informe o endereço completo.';
    }

    if (!$erros) {
        $sql = 'INSERT INTO clientes (id_usuario, nome, email, telefone, cpf, cep, endereco)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE nome = VALUES(nome), email = VALUES(email), telefone = VALUES(telefone),
                                        cpf = VALUES(cpf), cep = VALUES(cep), endereco = VALUES(endereco)';
        db()->prepare($sql)->execute([
            $usuario['id'], $dados['nome'], $dados['email'], $tel, so_digitos($dados['cpf']), $cep, $dados['endereco'],
        ]);
        redirect('pagamento.php');
    }
}

$titulo = 'Seus dados';
require __DIR__ . '/includes/header.php';

function campo_erro(array $erros, string $c): string
{
    return isset($erros[$c]) ? '<small class="campo-erro">' . e($erros[$c]) . '</small>' : '';
}
?>

<form action="confirmar_email.php" method="POST" novalidate>
<?= csrf_field() ?>
<section class="section_confirmar">
    <h1>Seus Dados</h1>
    <p>Plano escolhido: <strong><?= e($plano['nome']) ?></strong> · <a href="plano.php">trocar</a></p>

<?php if (!empty($erros['geral'])): ?>
    <div class="alerta alerta-erro" role="alert"><?= e($erros['geral']) ?></div>
<?php endif; ?>

    <div class="confirmar">
        <span class="campo">
            <label for="nome">Nome Completo</label>
            <input type="text" id="nome" name="nome" placeholder="Digite seu nome completo" value="<?= e($dados['nome']) ?>" autocomplete="name" required>
            <?= campo_erro($erros, 'nome') ?>
        </span>

        <span class="campo">
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" placeholder="Digite seu E-mail" value="<?= e($dados['email']) ?>" autocomplete="email" required>
            <?= campo_erro($erros, 'email') ?>
        </span>

        <span class="campo">
            <label for="telefone">Telefone / WhatsApp</label>
            <input type="tel" id="telefone" name="telefone" placeholder="(11) 99999-9999" value="<?= e($dados['telefone']) ?>" autocomplete="tel" inputmode="tel" required>
            <?= campo_erro($erros, 'telefone') ?>
        </span>

        <span class="campo campo_1">
            <label for="cpf">CPF</label>
            <input type="text" id="cpf" name="cpf" placeholder="000.000.000-00" value="<?= e($dados['cpf']) ?>" inputmode="numeric" maxlength="14" required>
            <?= campo_erro($erros, 'cpf') ?>
        </span>
    </div>

    <h1>Endereço de Cobrança</h1>
    <p>Informe o endereço para emissão da cobrança.</p>

    <div class="confirmar">
        <span class="campo">
            <label for="cep">CEP</label>
            <input type="text" id="cep" name="cep" placeholder="00000-000" value="<?= e($dados['cep']) ?>" autocomplete="postal-code" inputmode="numeric" maxlength="9" required>
            <?= campo_erro($erros, 'cep') ?>
        </span>

        <span class="campo">
            <label for="endereco">Endereço</label>
            <input type="text" id="endereco" name="endereco" placeholder="Rua, número, complemento, cidade" value="<?= e($dados['endereco']) ?>" autocomplete="street-address" maxlength="255" required>
            <?= campo_erro($erros, 'endereco') ?>
        </span>
    </div>

    <button type="submit" class="fomra_pagamento">Forma de Pagamento</button>
</section>
</form>

<?php
$jsExtra = ['js/mascaras.js'];
require __DIR__ . '/includes/footer.php';
