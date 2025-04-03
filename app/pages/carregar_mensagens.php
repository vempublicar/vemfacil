<?php
$pastaHash = sha1($_SESSION['email'] ?? '');
$caminhoBanco = "clientes/{$pastaHash}/meubanco.sqlite";

if (!file_exists($caminhoBanco)) {
    http_response_code(404);
    echo json_encode(['erro' => 'Banco de dados não encontrado.']);
    exit;
}

$db = new SQLite3($caminhoBanco);

$idContato = $_GET['id'] ?? null;
if (!$idContato) {
    http_response_code(400);
    echo json_encode(['erro' => 'ID do contato não fornecido.']);
    exit;
}

// Recupera o número do contato
$res = $db->prepare("SELECT telefone FROM contatos WHERE id = ?");
$res->bindValue(1, $idContato);
$contato = $res->execute()->fetchArray(SQLITE3_ASSOC);
if (!$contato) {
    echo json_encode(['erro' => 'Contato não encontrado.']);
    exit;
}

$numero = $contato['telefone'];

// Cria a tabela de mensagens se não existir
$db->exec("CREATE TABLE IF NOT EXISTS mensagens (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    numero TEXT,
    mensagem TEXT,
    tipo TEXT, -- enviada | recebida
    data_hora TEXT DEFAULT CURRENT_TIMESTAMP
)");

$stmt = $db->prepare("SELECT * FROM mensagens WHERE numero = ? ORDER BY data_hora DESC LIMIT 20");
$stmt->bindValue(1, $numero);
$res = $stmt->execute();

$mensagens = [];
while ($msg = $res->fetchArray(SQLITE3_ASSOC)) {
    $mensagens[] = $msg;
}

// Invertemos para exibir da mais antiga para a mais nova
$mensagens = array_reverse($mensagens);

header('Content-Type: application/json');
echo json_encode($mensagens);
exit;
