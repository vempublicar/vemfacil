<?php
// Verifica se foi enviado o client
$hash = $_GET['client'] ?? null;
if (!$hash || !preg_match('/^[a-f0-9]{40}$/', $hash)) {
    echo json_encode(['erro' => 'Cliente inválido.']);
    exit;
}

$caminhoBanco = "clientes/{$hash}/meubanco.sqlite";
if (!file_exists($caminhoBanco)) {
    echo json_encode(['erro' => 'Cliente não encontrado.']);
    exit;
}

// Connect with Write-Ahead Logging for better concurrency
$db = new SQLite3($caminhoBanco);
$db->exec('PRAGMA journal_mode = WAL');
$db->exec('PRAGMA synchronous = NORMAL');

// Inicializa tabelas e índices
initDatabase($db);

// Captura o corpo do POST
$input = file_get_contents('php://input');
$decoded = json_decode($input, true);

// Ajuste: se vier como array (como no Postman), extrai o objeto principal
$body = isset($decoded[0]['body']) ? $decoded[0]['body'] : $decoded;

if (!$body || !isset($body['event']) || $body['event'] !== 'messages.upsert') {
    echo json_encode(['status' => 'Ignorado']);
    exit;
}

$data = $body['data'] ?? [];
$fromMe = $data['key']['fromMe'] ?? true;
$numeroRaw = $data['key']['remoteJid'] ?? '';

if ($fromMe || !$numeroRaw || !str_contains($numeroRaw, '@s.whatsapp.net')) {
    echo json_encode(['status' => 'Ignorado']);
    exit;
}

// Extrai o número puro
$numero = str_replace('@s.whatsapp.net', '', $numeroRaw);
$mensagem = $data['message']['conversation'] ?? '';
$nome = $data['pushName'] ?? '';

// Start transaction for all database operations
$db->exec('BEGIN TRANSACTION');

try {
    // Registra no log de webhook
    $logStmt = $db->prepare("INSERT INTO logs_webhook (tipo, numero, mensagem) VALUES ('recebida', ?, ?)");
    $logStmt->bindValue(1, $numero);
    $logStmt->bindValue(2, $mensagem);
    $logStmt->execute();

    // Verifica e atualiza contatos
    $checkContact = $db->prepare("SELECT id FROM contatos WHERE telefone = ?");
    $checkContact->bindValue(1, $numero);
    $contactExists = $checkContact->execute()->fetchArray(SQLITE3_ASSOC);

    if ($contactExists) {
        $updateContact = $db->prepare("UPDATE contatos SET ultima_mensagem = datetime('now'), notifica = COALESCE(notifica, 0) + 1 WHERE telefone = ?");
        $updateContact->bindValue(1, $numero);
        $updateContact->execute();
    } else {
        // Se não existir, cria um novo contato
        $insertContact = $db->prepare("INSERT INTO contatos (telefone, nome, notifica, ultima_mensagem) VALUES (?, ?, 1, datetime('now'))");
        $insertContact->bindValue(1, $numero);
        $insertContact->bindValue(2, $nome);
        $insertContact->execute();
    }

    // Gerar caminho para o arquivo JSON
    $numeroCriptografado = hash('sha256', $numero);
    $jsonPath = "clientes/{$hash}/mensagens/{$numeroCriptografado}.json";

    // Adiciona a mensagem recebida ao arquivo JSON
    adicionarMensagemAoJson($jsonPath, $numero, $mensagem);

    // Commit all changes
    $db->exec('COMMIT');
    
    echo json_encode(['status' => 'Mensagem processada']);
} catch (Exception $e) {
    // Rollback on error
    $db->exec('ROLLBACK');
    echo json_encode(['erro' => 'Erro ao processar mensagem: ' . $e->getMessage()]);
} finally {
    // Ensure database connection is closed
    $db->close();
}

exit;

// Função para adicionar mensagens ao JSON
function adicionarMensagemAoJson($path, $numero, $mensagem) {
    if (!file_exists($path)) {
        $dados = ['mensagens' => []];
    } else {
        $conteudoAtual = file_get_contents($path);
        $dados = json_decode($conteudoAtual, true);
    }

    $dados['mensagens'][] = [
        'numero' => $numero,
        'mensagem' => $mensagem,
        'horario' => date('c'),
        'tipo' => 'recebida'
    ];

    file_put_contents($path, json_encode($dados));
}
// Function to initialize database with proper structure and indexes
function initDatabase($db) {
    // Verificar se precisamos adicionar a coluna ultima_mensagem à tabela contatos
    $columns = $db->query("PRAGMA table_info(contatos)");
    $hasUltimaMsg = false;
    
    if ($columns) {
        while ($column = $columns->fetchArray(SQLITE3_ASSOC)) {
            if ($column['name'] === 'ultima_mensagem') {
                $hasUltimaMsg = true;
                break;
            }
        }
        
        // Adicionar a coluna se ela não existir
        if (!$hasUltimaMsg) {
            try {
                $db->exec("ALTER TABLE contatos ADD COLUMN ultima_mensagem TEXT");
            } catch (Exception $e) {
                // Se a tabela não existir ainda, isso será ignorado
            }
        }
    }
    
    // Cria a tabela de logs com índice
    $db->exec("CREATE TABLE IF NOT EXISTS logs_webhook (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        tipo TEXT,
        numero TEXT,
        mensagem TEXT,
        data_hora TEXT DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Add index on numero for logs (if it's frequently queried)
    $db->exec("CREATE INDEX IF NOT EXISTS idx_logs_numero ON logs_webhook(numero)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_logs_data ON logs_webhook(data_hora)");
    
    // Make sure contatos table exists with proper structure
    $db->exec("CREATE TABLE IF NOT EXISTS contatos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        telefone TEXT UNIQUE,
        nome TEXT,
        etiqueta TEXT,
        grupoC TEXT,
        notifica INTEGER DEFAULT 0,
        data_criacao TEXT
    )");
    
}