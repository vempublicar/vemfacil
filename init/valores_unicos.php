<?php
session_start();
$pastaHash = sha1($_SESSION['email'] ?? '');
$dbPath = "../clientes/{$pastaHash}/meubanco.sqlite";

if (!file_exists($dbPath)) {
    echo json_encode([]);
    exit;
}

$db = new SQLite3($dbPath);

$coluna = $_GET['coluna'] ?? '';
$origem = $_GET['origem'] ?? 'contatos'; // valor padrão

// Valida tabela (evita SQL injection)
$tabela = ($origem === 'leads') ? 'leads' : 'contatos';

$colunasPermitidas = ['status','etiqueta','data','prioridade','variavelA','variavelB','variavelC','grupoA','grupoB','grupoC'];
if (!in_array($coluna, $colunasPermitidas)) {
    echo json_encode([]);
    exit;
}

$res = $db->query("SELECT DISTINCT $coluna FROM $tabela WHERE $coluna IS NOT NULL AND TRIM($coluna) != '' ORDER BY $coluna ASC");

$valores = [];
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    $valores[] = $row[$coluna];
}

echo json_encode($valores);
exit;
