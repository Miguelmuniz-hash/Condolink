<?php
// Configurações do projeto.
// Valores sensíveis (chave do Resend, senha do banco) ficam em includes/config.local.php
// (NÃO versionar) ou em variáveis de ambiente. Veja includes/config.local.example.php.
$arquivoLocal = __DIR__ . '/config.local.php';
if (is_file($arquivoLocal)) {
    require_once $arquivoLocal;
}

$padroes = [
    'APP_URL'             => '',                                        // vazio = detectar automaticamente
    'APP_DEBUG'           => '0',                                       // 1 = grava links de e-mail no log quando não há chave Resend
    'RESEND_API_KEY'      => '',
    'MAIL_FROM'           => 'CondoLink <onboarding@resend.dev>',      // troque por um remetente do seu domínio verificado
    'PAGAMENTO_SIMULADO'  => '1',                                       // 1 = não cobra de verdade (demonstração)
    'DESCONTO_ANUAL'      => '0',                                       // % de desconto no ciclo anual
    'DESCONTO_PIX'        => '10',                                      // % de desconto no PIX
    'SESSAO_IDLE_SEGUNDOS'=> '7200',                                    // logout automático por inatividade
];
foreach ($padroes as $chave => $valor) {
    if (!defined($chave)) {
        $env = getenv($chave);
        define($chave, $env !== false ? $env : $valor);
    }
}

/** URL base do site (sem barra final), ex.: http://localhost/condolink */
function app_url(): string
{
    if (APP_URL !== '') {
        return rtrim(APP_URL, '/');
    }
    $https  = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $pasta  = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    return ($https ? 'https://' : 'http://') . $host . $pasta;
}
