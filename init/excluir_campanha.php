<?php
session_start();
$pastaHash = sha1($_SESSION['email']);
$caminhoBanco = "../clientes/{$pastaHash}/meubanco.sqlite";
$db = new SQLite3($caminhoBanco);

if (!file_exists($caminhoBanco)) {
    die("Banco não encontrado.");
}

$db = new SQLite3($caminhoBanco);

$id = $_POST['id'] ?? '';
$tabela = $_POST['tabela'] ?? '';

if (!$id || !$tabela || !preg_match('/^fila_[a-f0-9]{40}$/', $tabela)) {
    die("Dados inválidos.");
}

// Exclui a tabela da fila de envio
$db->exec("DROP TABLE IF EXISTS {$tabela}");

// Exclui a entrada na tabela de campanhas
$stmt = $db->prepare("DELETE FROM campanhas WHERE id = ?");
$stmt->bindValue(1, $id, SQLITE3_INTEGER);
$stmt->execute();

// Redireciona
header('Location: ../painel&loc=autosand');
exit;

