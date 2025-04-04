<?php
session_start();
$config = require 'api.php';

$instancia = $_SESSION['instancia_ativa']['instance'] ?? null;
$numero = $_POST['numero'] ?? null;
$mensagem = trim($_POST['mensagem'] ?? '');

if (!$instancia || !$numero || $mensagem === '') {
    http_response_code(400);
    echo json_encode(['erro' => 'Dados inválidos']);
    exit;
}

$pastaHash = sha1($_SESSION['email']);
$caminhoBanco = "../clientes/{$pastaHash}/meubanco.sqlite";

// Envia a mensagem para a API
$url = $config['base_url'] . "/message/sendText/" . urlencode($instancia);

$payload = [
    "number" => $numero,
    "text"   => $mensagem
];

$options = [
    'http' => [
        'method'  => 'POST',
        'header'  => [
            'Content-Type: application/json',
            'apikey: ' . $config['apikey']
        ],
        'content' => json_encode($payload),
        'ignore_errors' => true // Permite capturar resposta mesmo com HTTP 500
    ]
];

$context = stream_context_create($options);
$response = @file_get_contents($url, false, $context);

$apiSuccess = false;
$apiResponse = null;

if ($response === false) {
    $error = error_get_last();
    $apiResponse = [
        'erro' => 'Falha na requisição',
        'detalhe' => $error['message'] ?? 'Erro desconhecido'
    ];
} else {
    $apiSuccess = true;
    
    // Processa a resposta da API
    $contentType = '';
    foreach ($http_response_header ?? [] as $header) {
        if (stripos($header, 'Content-Type:') !== false) {
            $contentType = $header;
            break;
        }
    }

    if (stripos($contentType, 'application/json') !== false) {
        $apiResponse = $response; // já é JSON
    } else {
        $apiResponse = [
            'status' => 'Resposta recebida',
            'conteudo' => $response
        ];
    }
}

// Grava a mensagem no banco de dados e também salva no arquivo JSON, se possível
if ($caminhoBanco && file_exists($caminhoBanco)) {
    try {
        // Conecta com o SQLite
        $db = new SQLite3($caminhoBanco);
        $db->exec('PRAGMA journal_mode = WAL');
        $db->exec('PRAGMA synchronous = NORMAL');
        
        // Cria a tabela de mensagens, se não existir
        $db->exec("CREATE TABLE IF NOT EXISTS mensagens (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            numero TEXT,
            mensagem TEXT,
            tipo TEXT, -- enviada | recebida
            data_hora TEXT DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Cria índices para a tabela de mensagens
        $db->exec("CREATE INDEX IF NOT EXISTS idx_mensagens_numero ON mensagens(numero)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_mensagens_data ON mensagens(data_hora)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_mensagens_tipo ON mensagens(tipo)");
        
        // Cria a tabela de contatos, se não existir
        $db->exec("CREATE TABLE IF NOT EXISTS contatos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            telefone TEXT UNIQUE,
            nome TEXT,
            etiqueta TEXT,
            grupoC TEXT,
            notifica INTEGER DEFAULT 0,
            data_criacao TEXT
        )");
        
        // Verifica se a coluna 'ultima_mensagem' existe na tabela de contatos
        $hasUltimaMsg = false;
        $columns = $db->query("PRAGMA table_info(contatos)");
        while ($column = $columns->fetchArray(SQLITE3_ASSOC)) {
            if ($column['name'] === 'ultima_mensagem') {
                $hasUltimaMsg = true;
                break;
            }
        }
        
        // Adiciona a coluna 'ultima_mensagem', se necessário
        if (!$hasUltimaMsg) {
            try {
                $db->exec("ALTER TABLE contatos ADD COLUMN ultima_mensagem TEXT");
                $hasUltimaMsg = true;
            } catch (Exception $e) {
                // Ignora se não conseguir adicionar
            }
        }
        
        // Inicia a transação
        $db->exec('BEGIN TRANSACTION');
        
        // Grava a mensagem enviada na tabela de mensagens
        $stmt = $db->prepare("INSERT INTO mensagens (numero, mensagem, tipo) VALUES (?, ?, 'enviada')");
        $stmt->bindValue(1, $numero);
        $stmt->bindValue(2, $mensagem);
        $stmt->execute();
        
        // Atualiza ou insere o contato correspondente
        $check = $db->prepare("SELECT id FROM contatos WHERE telefone = ?");
        $check->bindValue(1, $numero);
        $existe = $check->execute()->fetchArray(SQLITE3_ASSOC);
        
        if ($existe) {
            // Atualiza o contato existente
            $update = $db->prepare("UPDATE contatos SET ultima_mensagem = datetime('now'), notifica = 0 WHERE telefone = ?");
            $update->bindValue(1, $numero);
            $update->execute();
        } else {
            // Insere um novo contato
            if ($hasUltimaMsg) {
                $insert = $db->prepare("INSERT INTO contatos (telefone, nome, etiqueta, grupoC, notifica, data_criacao, ultima_mensagem) 
                                        VALUES (?, ?, 'Base', 'whatsapp', 0, datetime('now'), datetime('now'))");
            } else {
                $insert = $db->prepare("INSERT INTO contatos (telefone, nome, etiqueta, grupoC, notifica, data_criacao) 
                                        VALUES (?, ?, 'Base', 'whatsapp', 0, datetime('now'))");
            }
            $insert->bindValue(1, $numero);
            $insert->bindValue(2, ''); // Nome desconhecido
            $insert->execute();
        }
        
        // Commit das alterações no banco
        $db->exec('COMMIT');
        
        // Salva a mensagem enviada no arquivo JSON
        $jsonDir = "../clientes/{$pastaHash}/mensagens/";
        if (!is_dir($jsonDir)) {
            mkdir($jsonDir, 0777, true);
        }
        $numeroCriptografado = hash('sha256', $numero);
        $jsonPath = $jsonDir . $numeroCriptografado . ".json";
        adicionarMensagemAoJson($jsonPath, $numero, $mensagem, 'enviada');
        
        // Indica sucesso na operação do banco na resposta
        if (is_array($apiResponse)) {
            $apiResponse['db_saved'] = true;
        }
        
    } catch (Exception $e) {
        // Em caso de erro, realiza rollback e registra no log
        if (isset($db)) {
            $db->exec('ROLLBACK');
            
            try {
                $logError = $db->prepare("INSERT INTO logs_webhook (tipo, numero, mensagem) VALUES ('erro', ?, ?)");
                $logError->bindValue(1, $numero);
                $logError->bindValue(2, 'Erro ao salvar mensagem enviada: ' . $e->getMessage());
                $logError->execute();
            } catch (Exception $logEx) {
                // Ignora se não conseguir gravar o log
            }
            
            if (is_array($apiResponse)) {
                $apiResponse['db_error'] = $e->getMessage();
            }
        }
    } finally {
        if (isset($db)) {
            $db->close();
        }
    }
}

/**
 * Função para adicionar a mensagem ao arquivo JSON.
 * Se o arquivo não existir, cria a estrutura inicial.
 * O parâmetro $tipo deve ser 'enviada' ou 'recebida'.
 */
function adicionarMensagemAoJson($path, $numero, $mensagem, $tipo) {
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
        'tipo'      => $tipo
    ];
    
    file_put_contents($path, json_encode($dados));
}

// Retorna a resposta para o cliente
if (is_array($apiResponse)) {
    echo json_encode($apiResponse);
} else {
    echo $apiResponse;
}
exit;
