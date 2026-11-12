<?php
// Conexão com o banco `condolink` via PDO.
// Uso: require_once __DIR__ . '/includes/conexao.php';  depois  db()->prepare(...)
// A conexão é criada só na primeira chamada de db() (páginas sem banco não conectam).
require_once __DIR__ . '/config.php';

function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $host    = defined('DB_HOST') ? DB_HOST : (getenv('DB_HOST') ?: 'localhost');
    $banco   = defined('DB_NAME') ? DB_NAME : (getenv('DB_NAME') ?: 'condolink');
    $usuario = defined('DB_USER') ? DB_USER : (getenv('DB_USER') ?: 'root');   // padrão do XAMPP
    $senha   = defined('DB_PASS') ? DB_PASS : (getenv('DB_PASS') ?: '');       // padrão do XAMPP

    $opcoes = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$banco;charset=utf8mb4", $usuario, $senha, $opcoes);
    } catch (PDOException $erro) {
        error_log('Falha na conexão: ' . $erro->getMessage());
        throw new RuntimeException('Não foi possível conectar ao banco de dados.');
    }
    return $pdo;
}
