<?php
session_start();

if (!isset($_SESSION['email'])) {
  http_response_code(401);
  echo "Sessão expirada";
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo "Método não permitido";
  exit;
}

$pastaHash = sha1($_SESSION['email']);
$caminhoBanco = "../../clientes/{$pastaHash}/meubanco.sqlite";
$db = new SQLite3($caminhoBanco);

// Recebe os dados enviados via POST
$leadId = $_POST['id'] ?? null;
$etiqueta = $_POST['etiqueta'] ?? null;

if (!$leadId || !$etiqueta) {
  http_response_code(400);
  echo "Dados inválidos";
  exit;
}

// Atualiza a etiqueta_crm do contato
$stmt = $db->prepare("UPDATE contatos SET etiqueta_crm = ?, data_alteracao = datetime('now') WHERE id = ?");
$stmt->bindValue(1, $etiqueta);
$stmt->bindValue(2, $leadId, SQLITE3_INTEGER);

if ($stmt->execute()) {
  echo "Etiqueta atualizada";
} else {
  http_response_code(500);
  echo "Erro ao atualizar etiqueta";
}
