<?php
session_start();

if (!isset($_SESSION['email'])) {
  http_response_code(403);
  echo 'Acesso negado';
  exit;
}

$pastaHash = sha1($_SESSION['email']);
$caminhoBanco = "../clientes/{$pastaHash}/meubanco.sqlite";
$db = new SQLite3($caminhoBanco);

$idEtapa = $_POST['id'] ?? null;
if (!$idEtapa) {
  echo 'ID inválido';
  exit;
}

// Pega o nome da etapa
$stmt = $db->prepare("SELECT nome FROM etapas_crm WHERE id = ?");
$stmt->bindValue(1, $idEtapa);
$result = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

if (!$result) {
  echo 'Etapa não encontrada';
  exit;
}

$nomeEtapa = $result['nome'];
if ($nomeEtapa === 'Base') {
  echo 'Etapa Base não pode ser excluída';
  exit;
}

// Verifica se há contatos usando essa etapa
$check = $db->prepare("SELECT COUNT(*) as total FROM contatos WHERE etiqueta = ?");
$check->bindValue(1, $nomeEtapa);
$total = $check->execute()->fetchArray(SQLITE3_ASSOC)['total'] ?? 0;

if ($total > 0) {
  echo 'Não é possível excluir uma etapa com leads atribuídos.';
  exit;
}

// Remove a etapa
$delete = $db->prepare("DELETE FROM etapas_crm WHERE id = ?");
$delete->bindValue(1, $idEtapa);
$delete->execute();

echo 'Etapa excluída com sucesso.';
