<?php
// Variáveis opcionais definidas na página ANTES de incluir este arquivo:
//   $titulo    - título da aba
//   $bodyClass - classe extra no <body>
//   $particles - true para exibir o fundo tsParticles
//   $cssExtra  - array de arquivos .css extras (dentro de css/)
require_once __DIR__ . '/init.php';
$titulo    = $titulo    ?? 'CondoLink';
$bodyClass = $bodyClass ?? '';
$particles = $particles ?? false;
$cssExtra  = $cssExtra  ?? [];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0D2E63">
    <title><?= e($titulo) ?> | CondoLink</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/app.css">
<?php foreach ($cssExtra as $arquivoCss): ?>
    <link rel="stylesheet" href="css/<?= e($arquivoCss) ?>">
<?php endforeach; ?>
</head>
<body<?= $bodyClass !== '' ? ' class="' . e($bodyClass) . '"' : '' ?>>
<?php if ($particles): ?>
    <div id="tsparticles"></div>
<?php endif; ?>

<?php require __DIR__ . '/nav.php'; ?>

<?php $mensagens = flash_get(); if ($mensagens): ?>
<div class="flash-area" role="status" aria-live="polite">
<?php foreach ($mensagens as [$tipoMsg, $textoMsg]): ?>
    <div class="alerta alerta-<?= e($tipoMsg) ?>"><?= e($textoMsg) ?></div>
<?php endforeach; ?>
</div>
<?php endif; ?>
