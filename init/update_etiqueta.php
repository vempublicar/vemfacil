<?php
session_start();
if (!isset($_SESSION['email'])) {
  http_response_code(401);
  echo 'Não autorizado';
  exit;
}

$pastaHash = sha1($_SESSION['email']);
$caminhoBanco = "../clientes/{$pastaHash}/meubanco.sqlite";
$db = new SQLite3($caminhoBanco);

$id = $_POST['id'] ?? null;
$etiqueta = $_POST['etiqueta'] ?? '';

if ($id && $etiqueta) {
  $stmt = $db->prepare("UPDATE contatos SET etiqueta = ?, data_alteracao = datetime('now') WHERE id = ?");
  $stmt->bindValue(1, $etiqueta);
  $stmt->bindValue(2, $id, SQLITE3_INTEGER);
  $stmt->execute();
  echo 'Atualizado';
} else {
  http_response_code(400);
  echo 'Dados incompletos';
}
