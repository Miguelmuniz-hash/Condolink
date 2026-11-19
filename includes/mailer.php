<?php
// Envio de e-mails via SMTP (Gmail) usando PHPMailer.
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../PHPMailer/Exception.php';
require_once __DIR__ . '/../PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/SMTP.php';

/**
 * Envia um e-mail. Retorna true se o SMTP aceitou o envio.
 * Sem SMTP configurado: em modo APP_DEBUG grava o conteúdo no log do PHP (útil no XAMPP).
 */
function enviar_email(string $para, string $assunto, string $html, string $texto): bool
{
    if (!defined('SMTP_USER') || !defined('SMTP_PASS') || SMTP_USER === '' || SMTP_PASS === '') {
        if (APP_DEBUG === '1') {
            error_log("[CondoLink] (sem SMTP configurado) e-mail para $para | $assunto | $texto");
        }
        return false;
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';
        $mail->Timeout    = 10;

        $mail->setFrom(SMTP_USER, 'CondoLink');
        $mail->addAddress($para);
        $mail->Subject = $assunto;
        $mail->isHTML(true);
        $mail->Body    = $html;
        $mail->AltBody = $texto;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('[CondoLink] SMTP falhou: ' . $mail->ErrorInfo);
        return false;
    }
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