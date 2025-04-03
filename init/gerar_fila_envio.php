<?php
session_start();
$pastaHash = sha1($_SESSION['email'] ?? '');
$caminhoBanco = "../clientes/{$pastaHash}/meubanco.sqlite";

if (!file_exists($caminhoBanco)) {
    die(json_encode(['erro' => 'Banco de dados não encontrado.']));
}

$db = new SQLite3($caminhoBanco);
$db->exec("PRAGMA journal_mode = WAL");

// Cria tabela de campanhas se não existir
$db->exec("CREATE TABLE IF NOT EXISTS campanhas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome TEXT,
    tabela_fila TEXT,
    filtros TEXT,
    mensagem TEXT,
    origem TEXT,
    criada_em TEXT DEFAULT CURRENT_TIMESTAMP
)");

// Coleta os dados do POST
$origem         = $_POST['origem'] ?? 'contatos'; // padrão
$filtro1_coluna = $_POST['filtro1_coluna'] ?? '';
$filtro1_valor  = $_POST['filtro1_valor'] ?? '';
$filtro2_coluna = $_POST['filtro2_coluna'] ?? '';
$filtro2_valor  = $_POST['filtro2_valor'] ?? '';
$mensagem       = trim($_POST['mensagem'] ?? '');
$nomeCampanha   = trim($_POST['nome_campanha'] ?? 'Campanha');
$limite         = (int) ($_POST['limite_envios'] ?? 100);

// Validação mínima
if (!$filtro1_coluna || !$filtro1_valor || !$mensagem) {
    die(json_encode(['erro' => 'Campos obrigatórios não preenchidos.']));
}

// Garante que a origem é segura
$tabelaOrigem = ($origem === 'leads') ? 'leads' : 'contatos';

// Gera hash da campanha
$hashTabela = 'fila_' . sha1($nomeCampanha . $filtro1_coluna . $filtro1_valor . time());
$tabelaFila = $hashTabela;

// Cria a nova tabela da fila
$db->exec("CREATE TABLE IF NOT EXISTS $tabelaFila (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    telefone TEXT,
    mensagem TEXT,
    status TEXT DEFAULT 'pendente',
    tentativa INTEGER DEFAULT 0,
    criado_em TEXT DEFAULT CURRENT_TIMESTAMP
)");

// Monta SQL com filtros
$sql = "SELECT telefone FROM {$tabelaOrigem} WHERE {$filtro1_coluna} = ?";
$params = [$filtro1_valor];

if ($filtro2_coluna && $filtro2_valor) {
    $sql .= " AND {$filtro2_coluna} = ?";
    $params[] = $filtro2_valor;
}

$sql .= " LIMIT ?";
$params[] = $limite;

// Executa a consulta
$stmt = $db->prepare($sql);
foreach ($params as $i => $val) {
    $stmt->bindValue($i + 1, $val);
}
$res = $stmt->execute();

$inseridos = 0;
while ($contato = $res->fetchArray(SQLITE3_ASSOC)) {
    $telefone = $contato['telefone'];

    $ins = $db->prepare("INSERT INTO $tabelaFila (telefone, mensagem) VALUES (?, ?)");
    $ins->bindValue(1, $telefone);
    $ins->bindValue(2, $mensagem);
    $ins->execute();
    $inseridos++;
}

// Registra a campanha
$stmtCamp = $db->prepare("INSERT INTO campanhas (nome, tabela_fila, filtros, mensagem, origem) VALUES (?, ?, ?, ?, ?)");
$stmtCamp->bindValue(1, $nomeCampanha);
$stmtCamp->bindValue(2, $tabelaFila);
$stmtCamp->bindValue(3, json_encode([
    'filtro1' => [$filtro1_coluna, $filtro1_valor],
    'filtro2' => [$filtro2_coluna, $filtro2_valor]
]));
$stmtCamp->bindValue(4, $mensagem);
$stmtCamp->bindValue(5, $origem);
$stmtCamp->execute();

header("Location: ../painel&loc=autosand");
exit;
