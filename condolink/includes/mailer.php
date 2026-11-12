<?php
// Envio de e-mails pela API do Resend (https://resend.com/docs/api-reference/emails/send-email).
require_once __DIR__ . '/config.php';

/**
 * Envia um e-mail. Retorna true se o Resend aceitou o envio.
 * Sem chave configurada: em modo APP_DEBUG grava o conteúdo no log do PHP (útil no XAMPP).
 */
function enviar_email(string $para, string $assunto, string $html, string $texto): bool
{
    if (RESEND_API_KEY === '') {
        if (APP_DEBUG === '1') {
            error_log("[CondoLink] (sem RESEND_API_KEY) e-mail para $para | $assunto | $texto");
        }
        return false;
    }

    $corpo = json_encode([
        'from'    => MAIL_FROM,
        'to'      => [$para],
        'subject' => $assunto,
        'html'    => $html,
        'text'    => $texto,
    ], JSON_UNESCAPED_UNICODE);

    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $corpo,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . RESEND_API_KEY,
            'Content-Type: application/json',
        ],
    ]);
    $resposta = curl_exec($ch);
    $status   = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $erro     = curl_error($ch);
    curl_close($ch);

    if ($resposta === false || $status < 200 || $status >= 300) {
        error_log("[CondoLink] Resend falhou (HTTP $status): $erro $resposta");
        return false;
    }
    return true;
}

function enviar_email_redefinicao(string $para, string $nome, string $link): bool
{
    $nomeSeguro = htmlspecialchars($nome, ENT_QUOTES, 'UTF-8');
    $linkSeguro = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');
    $html = '<div style="font-family:Arial,sans-serif;max-width:480px;margin:auto;color:#1f2937">'
          . '<h2 style="color:#0D2E63">Redefinição de senha</h2>'
          . "<p>Olá, $nomeSeguro!</p>"
          . '<p>Recebemos um pedido para redefinir a senha da sua conta CondoLink. O link vale por 1 hora.</p>'
          . '<p><a href="' . $linkSeguro . '" style="display:inline-block;background:#D4AF37;color:#0D2E63;'
          . 'padding:12px 22px;border-radius:8px;text-decoration:none;font-weight:bold">Criar nova senha</a></p>'
          . '<p style="font-size:13px;color:#6b7280">Se o botão não funcionar, copie este endereço no navegador:<br>'
          . $linkSeguro . '</p>'
          . '<p style="font-size:13px;color:#6b7280">Se você não pediu isso, ignore este e-mail: sua senha continua a mesma.</p>'
          . '</div>';
    $texto = "Olá, $nome!\n\nPara criar uma nova senha acesse (vale por 1 hora):\n$link\n\n"
           . "Se você não pediu isso, ignore este e-mail.";
    return enviar_email($para, 'Redefinição de senha - CondoLink', $html, $texto);
}
