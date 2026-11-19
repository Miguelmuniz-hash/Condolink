<?php
// Funções de autenticação: validação de dados, limite de tentativas e tokens de senha.
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/conexao.php';

const LOGIN_MAX_FALHAS   = 5;     // falhas permitidas...
const LOGIN_JANELA_MIN   = 15;    // ...dentro desta janela (minutos)
const RESET_VALIDADE_MIN = 60;    // validade do link de redefinição
const RESET_MAX_POR_HORA = 3;     // pedidos de redefinição por e-mail por hora

function validar_nome(string $nome): ?string
{
    $tamanho = mb_strlen($nome, 'UTF-8');
    if ($tamanho < 3 || $tamanho > 100) {
        return 'Informe seu nome completo (3 a 100 caracteres).';
    }
    return null;
}

function validar_email(string $email): ?string
{
    if ($email === '' || mb_strlen($email) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'Informe um e-mail válido.';
    }
    return null;
}

function validar_senha(string $senha): ?string
{
    if (strlen($senha) < 8) {
        return 'A senha deve ter pelo menos 8 caracteres.';
    }
    if (strlen($senha) > 72) {
        return 'A senha pode ter no máximo 72 caracteres.';
    }
    if (!preg_match('/[A-Za-z]/', $senha) || !preg_match('/\d/', $senha)) {
        return 'A senha deve misturar letras e números.';
    }
    return null;
}

/* ---------- Limite de tentativas de login ---------- */
function login_bloqueado(string $email): bool
{
    $q = db()->prepare(
        'SELECT COUNT(*) FROM login_tentativas
          WHERE sucesso = 0 AND criado_em > (NOW() - INTERVAL ' . LOGIN_JANELA_MIN . ' MINUTE)
            AND (email = ? OR ip = ?)'
    );
    $q->execute([$email, ip_cliente()]);
    return (int) $q->fetchColumn() >= LOGIN_MAX_FALHAS;
}

function login_registrar(string $email, bool $sucesso): void
{
    $q = db()->prepare('INSERT INTO login_tentativas (email, ip, sucesso) VALUES (?, ?, ?)');
    $q->execute([mb_substr($email, 0, 100), ip_cliente(), $sucesso ? 1 : 0]);
    if ($sucesso) {
        // Zera as falhas recentes desse e-mail após um login correto
        db()->prepare('DELETE FROM login_tentativas WHERE email = ? AND sucesso = 0')->execute([$email]);
    }
}

/* ---------- Redefinição de senha ---------- */

/** Cria um token novo (guarda só o hash no banco) e devolve o token em texto puro para o link. */
function reset_criar_token(int $idUsuario): string
{
    $token = bin2hex(random_bytes(32));
    db()->prepare('UPDATE password_resets SET usado_em = NOW() WHERE id_usuario = ? AND usado_em IS NULL')
        ->execute([$idUsuario]);
    db()->prepare(
        'INSERT INTO password_resets (id_usuario, token_hash, expira_em)
         VALUES (?, ?, NOW() + INTERVAL ' . RESET_VALIDADE_MIN . ' MINUTE)'
    )->execute([$idUsuario, hash('sha256', $token)]);
    return $token;
}

/** Retorna a linha do token se ele existir, não foi usado e não expirou. */
function reset_buscar_token(string $token): ?array
{
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        return null;
    }
    $q = db()->prepare(
        'SELECT id, id_usuario FROM password_resets
          WHERE token_hash = ? AND usado_em IS NULL AND expira_em > NOW() LIMIT 1'
    );
    $q->execute([hash('sha256', $token)]);
    $linha = $q->fetch();
    return $linha ?: null;
}

function reset_excedeu_limite(int $idUsuario): bool
{
    $q = db()->prepare(
        'SELECT COUNT(*) FROM password_resets WHERE id_usuario = ? AND criado_em > (NOW() - INTERVAL 1 HOUR)'
    );
    $q->execute([$idUsuario]);
    return (int) $q->fetchColumn() >= RESET_MAX_POR_HORA;
}

/* ---------- CPF / CEP / telefone ---------- */
function so_digitos(string $v): string
{
    return preg_replace('/\D+/', '', $v) ?? '';
}

function cpf_valido(string $cpf): bool
{
    $cpf = so_digitos($cpf);
    if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
        return false;
    }
    for ($t = 9; $t < 11; $t++) {
        $soma = 0;
        for ($i = 0; $i < $t; $i++) {
            $soma += (int) $cpf[$i] * (($t + 1) - $i);
        }
        $digito = ((10 * $soma) % 11) % 10;
        if ((int) $cpf[$t] !== $digito) {
            return false;
        }
    }
    return true;
}
