<?php
session_start();
$config = require 'api.php';
$db = new SQLite3('../clientes/meubanco.sqlite');

$instance = $_POST['instance'] ?? '';
$number = $_POST['number'] ?? '';

if (!$instance || !$number) {
  die("Dados inválidos.");
}

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
