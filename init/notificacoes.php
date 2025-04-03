<?php
session_start();
$pastaHash = sha1($_SESSION['email']);
$caminhoBanco = "../clientes/{$pastaHash}/meubanco.sqlite";

if (!file_exists($caminhoBanco)) {
    http_response_code(404);
    echo json_encode([]);
    exit;
}

$db = new SQLite3($caminhoBanco);

$stmt = $db->prepare("SELECT id, notifica FROM contatos WHERE notifica > 0");
$res = $stmt->execute();

$notificacoes = [];
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    $notificacoes[$row['id']] = $row['notifica'];
}

header('Content-Type: application/json');
echo json_encode($notificacoes);
exit;
