<?php
session_start();
if (!isset($_SESSION['email'])) {
    http_response_code(401);
    echo "Não autorizado.";
    exit;
}

$pastaHash = sha1($_SESSION['email']);
$caminhoBanco = "../clientes/{$pastaHash}/meubanco.sqlite";
$db = new SQLite3($caminhoBanco);

$id = $_POST['id'] ?? '';
if (!$id) {
    http_response_code(400);
    echo "ID inválido.";
    exit;
}

// Buscar dados do lead
$stmt = $db->prepare("SELECT * FROM leads WHERE id = ?");
$stmt->bindValue(1, $id, SQLITE3_INTEGER);
$lead = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

if (!$lead) {
    http_response_code(404);
    echo "Lead não encontrado.";
    exit;
}

// Verifica se o número já está nos contatos
$verifica = $db->prepare("SELECT id FROM contatos WHERE telefone = ?");
$verifica->bindValue(1, $lead['telefone']);
$existe = $verifica->execute()->fetchArray(SQLITE3_ASSOC);

if ($existe) {
    echo "Este contato já está na sua base.";
    exit;
}

// Inserir no banco de contatos com telefone, nome e email
$insert = $db->prepare("INSERT INTO contatos (telefone, nome, email, data_criacao)
                        VALUES (?, ?, ?, datetime('now'))");
$insert->bindValue(1, $lead['telefone']);
$insert->bindValue(2, $lead['nome']);
$insert->bindValue(3, $lead['email']);
$insert->execute();

// Atualiza etiqueta no lead para "contato" e status para "cliente"
$update = $db->prepare("UPDATE leads SET etiqueta = 'contato', data_alteracao = datetime('now') WHERE id = ?");
$update->bindValue(1, $id, SQLITE3_INTEGER);
$update->execute();

echo "Lead convertido para cliente com sucesso!";
