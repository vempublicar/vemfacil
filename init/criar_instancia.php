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
$number = $_POST['number'] ?? '';

if (!$instance || !$number) {
  die("Dados inválidos.");
}

// Monta a URL do webhook com base no hash da pasta do cliente
$webhookUrl = "https://app.vemfacil.com/webhook&client={$pastaHash}";

$url = $config['base_url'] . $config['endpoints']['create_instance'];

$data = [
  'instanceName' => $instance,
  'number' => $number,
  'qrcode' => true,
  'integration' => 'WHATSAPP-BAILEYS',
  'settings' => [
    'rejectCall' => true,
    'msgCall' => '',
    'groupsIgnore' => true,
    'alwaysOnline' => false,
    'readMessages' => false,
    'readStatus' => false,
    'syncFullHistory' => false
  ],
  'webhook' => [
    'url' => $webhookUrl,
    'byEvents' => true,
    'base64' => true,
    'headers' => [
      'Authorization' => 'Bearer ' . $config['apikey'],
      'Content-Type' => 'application/json'
    ],
    'events' => ['MESSAGES_UPSERT'] // Evento mais importante
  ]
];

$options = [
  'http' => [
    'header' => [
      "Content-type: application/json",
      "apikey: {$config['apikey']}"
    ],
    'method' => 'POST',
    'content' => json_encode($data)
  ]
];

$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);

if ($result === false) {
  die('Erro ao criar instância.');
}

$db->exec("UPDATE conexao SET status = 'criada' WHERE instance_name = '$instance'");

$response = json_decode($result, true);
$_SESSION['qrcode'] = $response['qrcode']['base64'] ?? null;

header("Location: ../painel&loc=conexao");
exit;
