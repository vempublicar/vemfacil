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

// Gera o hash do número para localizar o arquivo JSON
$numeroCriptografado = hash('sha256', $numero);
$jsonPath = "clientes/{$pastaHash}/mensagens/{$numeroCriptografado}.json";

// Carrega as mensagens do arquivo JSON
if (file_exists($jsonPath)) {
    $jsonData = file_get_contents($jsonPath);
    $mensagens = json_decode($jsonData, true)['mensagens'] ?? [];
    // Inverte para exibir da mais antiga para a mais nova
    $mensagens = array_reverse($mensagens);
} else {
    $mensagens = []; // Nenhuma mensagem encontrada
}

header('Content-Type: application/json');
echo json_encode($mensagens);
exit;
