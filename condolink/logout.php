<?php
// Encerra a sessão (somente via POST com token CSRF) e volta para a página inicial.
require_once __DIR__ . '/includes/init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok()) {
    encerrar_sessao();
    session_start();
    flash_set('info', 'Você saiu da sua conta.');
}
redirect('index.php');
