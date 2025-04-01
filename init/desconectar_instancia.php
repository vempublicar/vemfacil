<?php
session_start();
$config = require 'api.php';

if (!isset($_SESSION['email'])) {
  die("Sessão expirada.");
}

$instance = $_POST['instance'] ?? '';

if (!$instance) {
  $_SESSION['mensagem'] = 'Instância inválida.';
  header("Location: ../painel&loc=conexao");
  exit;
}

// Endpoint de logout
$url = $config['base_url'] . "/instance/logout/" . urlencode($instance);

$options = [
  'http' => [
    'method' => 'DELETE',
    'header' => "apikey: {$config['apikey']}"
  ]
];

$context = stream_context_create($options);
$response = @file_get_contents($url, false, $context);
$http_response_header = $http_response_header ?? [];

if ($response === false) {
  $_SESSION['mensagem'] = 'Erro ao desconectar a instância.';
  $_SESSION['mensagem'] .= '<br><small>Resposta HTTP: ' . htmlspecialchars(implode(', ', $http_response_header)) . '</small>';
} else {
  $_SESSION['mensagem'] = 'Instância desconectada com sucesso.';
  unset($_SESSION['instancia_ativa']);
}

header("Location: ../painel&loc=conexao");
exit;
