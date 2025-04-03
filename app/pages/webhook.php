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
    // Registra no log
    $stmt = $db->prepare("INSERT INTO logs_webhook (tipo, numero, mensagem) VALUES ('recebida', ?, ?)");
    $stmt->bindValue(1, $numero);
    $stmt->bindValue(2, $mensagem);
    $stmt->execute();

    // Verifica se já existe contato
    $check = $db->prepare("SELECT id FROM contatos WHERE telefone = ?");
    $check->bindValue(1, $numero);
    $existe = $check->execute()->fetchArray(SQLITE3_ASSOC);

    if ($existe) {
        // Atualiza contato existente
        $hasUltimaMsg = false;
        $columns = $db->query("PRAGMA table_info(contatos)");
        while ($column = $columns->fetchArray(SQLITE3_ASSOC)) {
            if ($column['name'] === 'ultima_mensagem') {
                $hasUltimaMsg = true;
                break;
            }
        }
    
        if ($hasUltimaMsg) {
            $update = $db->prepare("UPDATE contatos SET notifica = COALESCE(notifica, 0) + 1, ultima_mensagem = datetime('now') WHERE telefone = ?");
        } else {
            $update = $db->prepare("UPDATE contatos SET notifica = COALESCE(notifica, 0) + 1 WHERE telefone = ?");
        }
    
        $update->bindValue(1, $numero);
        $update->execute();
    } else {
        // Verifica se está na tabela leads
        $verificaLead = $db->prepare("SELECT id FROM leads WHERE telefone = ?");
        $verificaLead->bindValue(1, $numero);
        $leadExiste = $verificaLead->execute()->fetchArray(SQLITE3_ASSOC);
    
        if ($leadExiste) {
            // Adiciona na tabela contatos vindo de lead
            $insereContato = $db->prepare("INSERT INTO contatos (telefone, nome, etiqueta, grupoC, notifica, data_criacao, ultima_mensagem) 
                                           VALUES (?, ?, 'Base', 'lead', 1, datetime('now'), datetime('now'))");
            $insereContato->bindValue(1, $numero);
            $insereContato->bindValue(2, $nome);
            $insereContato->execute();
    
            // Atualiza o lead como convertido
            $atualizaLead = $db->prepare("UPDATE leads SET etiqueta = 'contato', data_resposta = datetime('now') WHERE id = ?");
            $atualizaLead->bindValue(1, $leadExiste['id']);
            $atualizaLead->execute();
        } else {
            // Adiciona novo contato padrão
            $insere = $db->prepare("INSERT INTO contatos (telefone, nome, etiqueta, grupoC, notifica, data_criacao, ultima_mensagem) 
                                    VALUES (?, ?, 'Base', 'whatsapp', 1, datetime('now'), datetime('now'))");
            $insere->bindValue(1, $numero);
            $insere->bindValue(2, $nome);
            $insere->execute();
        }
    }

    // Salva a mensagem recebida
    $insertMsg = $db->prepare("INSERT INTO mensagens (numero, mensagem, tipo) VALUES (?, ?, 'recebida')");
    $insertMsg->bindValue(1, $numero);
    $insertMsg->bindValue(2, $mensagem);
    $insertMsg->execute();

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
    
   
    // Add index on telefone (if not already created by UNIQUE constraint)
    $db->exec("CREATE INDEX IF NOT EXISTS idx_contatos_telefone ON contatos(telefone)");
    
    // Cria a tabela de mensagens com índices apropriados
    $db->exec("CREATE TABLE IF NOT EXISTS mensagens (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        numero TEXT,
        mensagem TEXT,
        tipo TEXT, -- enviada | recebida
        data_hora TEXT DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Add indexes for faster message retrieval
    $db->exec("CREATE INDEX IF NOT EXISTS idx_mensagens_numero ON mensagens(numero)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_mensagens_data ON mensagens(data_hora)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_mensagens_tipo ON mensagens(tipo)");
}