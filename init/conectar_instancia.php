<?php
session_start();
$config = require 'api.php';

if (!isset($_SESSION['email'])) {
  die("Sessão expirada. Faça login novamente.");
}

// Banco exclusivo do cliente
$pastaHash = sha1($_SESSION['email']);
$caminhoBanco = "../clientes/{$pastaHash}/meubanco.sqlite";
$db = new SQLite3($caminhoBanco);

// Dados recebidos
$instance = $_POST['instance'] ?? '';
$number = $_POST['number'] ?? '';

if (!$instance || !$number) {
  die("Dados inválidos.");
}

// Endpoint para conectar a instância
$url = $config['base_url'] . "/instance/connect/" . urlencode($instance) . "?number=" . urlencode($number);

$options = [
  'http' => [
    'method' => 'GET',
    'header' => "apikey: {$config['apikey']}"
  ]
];

$context = stream_context_create($options);
$response = file_get_contents($url, false, $context);

if ($response === false) {
  $_SESSION['mensagem'] = 'Erro ao conectar a instância.';
} else {
  $_SESSION['mensagem'] = 'Instância conectada com sucesso.';

  // ✅ Define instância ativa na sessão
  $_SESSION['instancia_ativa'] = [
    'instance' => $instance,
    'number' => $number
  ];
}

header("Location: ../painel&loc=conexao");
exit;
