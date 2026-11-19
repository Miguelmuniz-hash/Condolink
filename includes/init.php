<?php
// Inicialização comum: sessão segura, cabeçalhos, CSRF, flash e proteção de rotas.
// Incluído automaticamente por header.php. Páginas protegidas devem incluir este
// arquivo ANTES de qualquer saída e chamar require_login() / require_admin().
require_once __DIR__ . '/config.php';

if (!headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

if (session_status() === PHP_SESSION_NONE) {
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Logout automático por inatividade
if (isset($_SESSION['usuario_id'], $_SESSION['ultimo_acesso'])
    && time() - (int) $_SESSION['ultimo_acesso'] > (int) SESSAO_IDLE_SEGUNDOS) {
    $_SESSION = [];
    session_regenerate_id(true);
    $_SESSION['flash'][] = ['info', 'Sua sessão expirou por inatividade. Entre novamente.'];
}
$_SESSION['ultimo_acesso'] = time();

/** Escapa texto para exibir com segurança em HTML. */
function e($texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

/** Formata valor em reais. */
function moeda($valor): string
{
    return 'R$ ' . number_format((float) $valor, 2, ',', '.');
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/* ---------- Mensagens (flash) ---------- */
function flash_set(string $tipo, string $mensagem): void
{
    $_SESSION['flash'][] = [$tipo, $mensagem];
}

function flash_get(): array
{
    $mensagens = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $mensagens;
}

/* ---------- CSRF ---------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

/** Retorna true se o token enviado no POST confere. */
function csrf_ok(): bool
{
    $enviado = $_POST['csrf'] ?? '';
    return is_string($enviado) && $enviado !== '' && hash_equals($_SESSION['csrf'] ?? '', $enviado);
}

/* ---------- Sessão / usuário ---------- */
function usuario_logado(): bool
{
    return isset($_SESSION['usuario_id']);
}

function usuario_admin(): bool
{
    return usuario_logado() && ($_SESSION['usuario_tipo'] ?? '') === 'Admin';
}

/** Cria a sessão do usuário (chamar após login ou cadastro). */
function iniciar_sessao(array $usuario): void
{
    session_regenerate_id(true);
    $_SESSION['usuario_id']   = (int) $usuario['id'];
    $_SESSION['usuario_nome'] = $usuario['nome'];
    $_SESSION['usuario_tipo'] = $usuario['tipo'];
    $_SESSION['ultimo_acesso'] = time();
    unset($_SESSION['csrf']);
}

function encerrar_sessao(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/** Aceita apenas destinos internos simples (ex.: pagamento.php?x=1), evitando open redirect. */
function destino_seguro(?string $destino, string $padrao = 'painel.php'): string
{
    if ($destino !== null && preg_match('/^[a-z0-9_\-]+\.php(\?[A-Za-z0-9_=&%.\-]*)?$/i', $destino)) {
        return $destino;
    }
    return $padrao;
}

/**
 * Bloqueia a página para quem não está autenticado.
 * Confere no banco se a conta ainda está ativa e atualiza o papel (role) da sessão.
 */
function require_login(): array
{
    static $atual = null;
    if ($atual !== null) {
        return $atual;
    }

    if (!usuario_logado()) {
        flash_set('info', 'Faça login para acessar essa página.');
        $alvo = basename($_SERVER['SCRIPT_NAME'] ?? '') . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
        redirect('login.php?next=' . urlencode(destino_seguro($alvo, 'painel.php')));
    }

    require_once __DIR__ . '/conexao.php';
    $busca = db()->prepare('SELECT id, nome, email, tipo, sts FROM usuarios WHERE id = ? LIMIT 1');
    $busca->execute([$_SESSION['usuario_id']]);
    $usuario = $busca->fetch();

    if (!$usuario || $usuario['sts'] !== 'Ativo') {
        encerrar_sessao();
        session_start();
        flash_set('erro', 'Sua conta não está ativa. Entre em contato com o suporte.');
        redirect('login.php');
    }

    $_SESSION['usuario_tipo'] = $usuario['tipo'];
    $_SESSION['usuario_nome'] = $usuario['nome'];
    return $atual = $usuario;
}

/** Bloqueia a página para quem não for Admin (RBAC). */
function require_admin(): array
{
    $usuario = require_login();
    if ($usuario['tipo'] !== 'Admin') {
        flash_set('erro', 'Acesso negado: esta área é exclusiva para administradores.');
        redirect('painel.php');
    }
    return $usuario;
}

function ip_cliente(): string
{
    return substr($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', 0, 45);
}
