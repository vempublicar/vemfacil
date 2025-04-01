<?php
session_start();

// Dados fictícios para teste
$usuarioCorreto = "admin";
$senhaCorreta = "123456";

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if ($username === $usuarioCorreto && $password === $senhaCorreta) {
    $_SESSION['email'] = 'teste@teste.com';
    $_SESSION['usuario'] = 'autorizado';

    // 1. Cria hash da pasta
    $pastaHash = sha1($_SESSION['email']);
    $caminhoPasta = "../clientes/{$pastaHash}";

    // 2. Cria pasta se não existir
    if (!file_exists($caminhoPasta)) {
        mkdir($caminhoPasta, 0777, true);
    }

    // 3. Caminho completo do banco
    $caminhoBanco = $caminhoPasta . "/meubanco.sqlite";

    $db = new SQLite3($caminhoBanco);
    $schema = file_get_contents('../sql/schema.sql');
    $db->exec($schema);

    header("Location: ../painel");
    exit;
} else {
    $_SESSION['msg'] = 'login e senha inválido';
    header("Location: ../index");
    exit;
}
