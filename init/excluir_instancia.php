<?php
session_start();
$config = require 'api.php';

if (!isset($_SESSION['email'])) {
  die("Sessão expirada.");
}

$pastaHash = sha1($_SESSION['email']);
$caminhoBanco = "../clientes/{$pastaHash}/meubanco.sqlite";
$db = new SQLite3($caminhoBanco);

$instance = $_POST['instance'] ?? '';

if (!$instance) {
  $_SESSION['mensagem'] = 'Instância inválida.';
  header("Location: ../painel.php?loc=conexao");
  exit;
}

// Requisição para excluir no servidor
$url = $config['base_url'] . "/instance/delete/" . urlencode($instance);

$options = [
  'http' => [
    'method' => 'DELETE',
    'header' => "apikey: {$config['apikey']}"
  ]
];

$context = stream_context_create($options);
$response = file_get_contents($url, false, $context);

// Remove do banco local
$db->exec("DELETE FROM conexao WHERE instance_name = '$instance'");

// Se for a instância ativa, remove da sessão também
if (!empty($_SESSION['instancia_ativa']) && $_SESSION['instancia_ativa']['instance'] === $instance) {
  unset($_SESSION['instancia_ativa']);
}

if ($response === false) {
  $_SESSION['mensagem'] = 'Erro ao excluir a instância.';
} else {
  $_SESSION['mensagem'] = 'Instância excluída com sucesso.';
}

header("Location: ../painel&loc=conexao");
exit;
