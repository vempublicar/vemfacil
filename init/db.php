<?php
session_start();
$pastaHash = sha1($_SESSION['email']);
$caminhoBanco = "../clientes/{$pastaHash}/meubanco.sqlite";

$db = new SQLite3($caminhoBanco);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = $_POST['instance_name'] ?? '';
  $number = $_POST['number'] ?? '';

  if ($name && $number) {
    $stmt = $db->prepare("INSERT OR IGNORE INTO conexao (instance_name, number) VALUES (?, ?)");
    $stmt->bindValue(1, $name);
    $stmt->bindValue(2, $number);
    $stmt->execute();
  }
  $_SESSION['mensagem'] = "Instância cadastrada com sucesso.";
  // ✅ Redireciona de volta para o painel
  header("Location: ../painel&loc=conexao");
  exit;
}