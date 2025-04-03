<?php
session_start();

if (!isset($_SESSION['email'])) {
  http_response_code(401);
  echo json_encode(['erro' => 'Não autorizado']);
  exit;
}

$pastaHash = sha1($_SESSION['email']);
$caminhoBanco = "../clientes/{$pastaHash}/meubanco.sqlite";
$db = new SQLite3($caminhoBanco);

$termo = $_GET['termo'] ?? '';
$termo = '%' . $termo . '%';

$stmt = $db->prepare("SELECT id, nome, telefone FROM contatos 
    WHERE nome LIKE ? OR telefone LIKE ? OR email LIKE ?
    ORDER BY nome ASC LIMIT 10");
$stmt->bindValue(1, $termo);
$stmt->bindValue(2, $termo);
$stmt->bindValue(3, $termo);
$res = $stmt->execute();

$leads = [];
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    $leads[] = $row;
}

header('Content-Type: application/json');
echo json_encode($leads);
