<?php 
// Verifica se foi enviado o client
$clientParam = $_GET['client'] ?? null;
// Se houver uma barra ("/"), extrai somente a parte antes dela
if ($clientParam && strpos($clientParam, '/') !== false) {
    $clientParam = explode('/', $clientParam)[0];
}
if (!$clientParam || !preg_match('/^[a-f0-9]{40}$/', $clientParam)) {
    echo json_encode(['erro' => 'Cliente inválido.']);
    exit;
}
$hash = $clientParam;

$caminhoBanco = "clientes/{$hash}/meubanco.sqlite";
if (!file_exists($caminhoBanco)) {
    echo json_encode(['erro' => 'Cliente não encontrado.']);
    exit;
}

// Conecta com o SQLite utilizando Write-Ahead Logging para melhor concorrência
$db = new SQLite3($caminhoBanco);
$db->exec('PRAGMA journal_mode = WAL');
$db->exec('PRAGMA synchronous = NORMAL');

// Captura o corpo do POST
$input = file_get_contents('php://input');
$decoded = json_decode($input, true);

// Ajuste: se vier como array (ex.: Postman), extrai o objeto principal
$body = isset($decoded[0]['body']) ? $decoded[0]['body'] : $decoded;

if (!$body || !isset($body['event']) || $body['event'] !== 'messages.upsert') {
    echo json_encode(['status' => 'Ignorado']);
    exit;
}

$data = $body['data'] ?? [];
$fromMe = $data['key']['fromMe'] ?? true;
$numeroRaw = $data['key']['remoteJid'] ?? '';

if ($fromMe || !$numeroRaw || strpos($numeroRaw, '@s.whatsapp.net') === false) {
    echo json_encode(['status' => 'Ignorado']);
    exit;
}

// Extrai o número puro e os demais dados
$numero = str_replace('@s.whatsapp.net', '', $numeroRaw);
$mensagem = $data['message']['conversation'] ?? '';
$nome = $data['pushName'] ?? '';

// Define a pasta onde o JSON será salvo
$baseDir = "clientes/{$hash}/mensagens/";
if (!is_dir($baseDir)) {
    mkdir($baseDir, 0777, true);
}

// Inicia a transação do banco
$db->exec('BEGIN TRANSACTION');

try {
    // Registra o log da mensagem
    $stmt = $db->prepare("INSERT INTO logs_webhook (tipo, numero, mensagem) VALUES ('recebida', ?, ?)");
    $stmt->bindValue(1, $numero);
    $stmt->bindValue(2, $mensagem);
    $stmt->execute();

    // Verifica se já existe o contato
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
        // Verifica se o contato está na tabela leads
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

    // Em vez de salvar a mensagem no banco, salva em um arquivo JSON
    $numeroCriptografado = hash('sha256', $numero);
    $jsonPath = $baseDir . $numeroCriptografado . ".json";
    adicionarMensagemAoJson($jsonPath, $numero, $mensagem);

    // Commit das alterações no banco
    $db->exec('COMMIT');
    
    echo json_encode(['status' => 'Mensagem processada']);
} catch (Exception $e) {
    // Rollback em caso de erro
    $db->exec('ROLLBACK');
    echo json_encode(['erro' => 'Erro ao processar mensagem: ' . $e->getMessage()]);
} finally {
    // Encerra a conexão com o banco
    $db->close();
}
exit;

/**
 * Função para adicionar a mensagem ao arquivo JSON.
 * Se o arquivo não existir, cria a estrutura inicial.
 */
function adicionarMensagemAoJson($path, $numero, $mensagem) {
    if (!file_exists($path)) {
        $dados = ['mensagens' => []];
    } else {
        $conteudoAtual = file_get_contents($path);
        $dados = json_decode($conteudoAtual, true);
        if (!$dados) {
            $dados = ['mensagens' => []];
        }
    }

    $dados['mensagens'][] = [
        'numero'    => $numero,
        'mensagem'  => $mensagem,
        'horario'   => date('c'),
        'tipo'      => 'recebida'
    ];

    file_put_contents($path, json_encode($dados));
}

/**
 * Função para inicializar o banco de dados com a estrutura e índices corretos.
 * (Caso seja necessário utilizar em futuras implementações)
 */
function initDatabase($db) {
    // Verifica se a coluna 'ultima_mensagem' existe na tabela contatos
    $columns = $db->query("PRAGMA table_info(contatos)");
    $hasUltimaMsg = false;
    
    if ($columns) {
        while ($column = $columns->fetchArray(SQLITE3_ASSOC)) {
            if ($column['name'] === 'ultima_mensagem') {
                $hasUltimaMsg = true;
                break;
            }
        }
        
        if (!$hasUltimaMsg) {
            try {
                $db->exec("ALTER TABLE contatos ADD COLUMN ultima_mensagem TEXT");
            } catch (Exception $e) {
                // Ignora caso a tabela ainda não exista
            }
        }
    }
    
    // Cria a tabela de logs e índices
    $db->exec("CREATE TABLE IF NOT EXISTS logs_webhook (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        tipo TEXT,
        numero TEXT,
        mensagem TEXT,
        data_hora TEXT DEFAULT CURRENT_TIMESTAMP
    )");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_logs_numero ON logs_webhook(numero)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_logs_data ON logs_webhook(data_hora)");
    
    // Cria a tabela de contatos e índice
    $db->exec("CREATE TABLE IF NOT EXISTS contatos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        telefone TEXT UNIQUE,
        nome TEXT,
        etiqueta TEXT,
        grupoC TEXT,
        notifica INTEGER DEFAULT 0,
        data_criacao TEXT
    )");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_contatos_telefone ON contatos(telefone)");
 
   
}
