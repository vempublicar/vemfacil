<?php
$pastaHash = sha1($_SESSION['email'] ?? '');
$caminhoBanco = "clientes/{$pastaHash}/meubanco.sqlite";

// Verifica e conecta
if (!file_exists($caminhoBanco)) {
    echo "<div class='alert alert-danger'>Banco de dados não encontrado.</div>";
    return;
}

$db = new SQLite3($caminhoBanco);

// Cria ou ajusta a tabela campanhas com o campo 'origem'
$db->exec("CREATE TABLE IF NOT EXISTS campanhas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome TEXT,
    tabela_fila TEXT,
    filtros TEXT,
    mensagem TEXT,
    origem TEXT,
    criada_em TEXT DEFAULT CURRENT_TIMESTAMP
)");

// Busca as campanhas
$campanhas = [];
$res = $db->query("SELECT * FROM campanhas ORDER BY criada_em DESC");

while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    // Conta quantas mensagens existem na tabela da campanha
    $tabela = $row['tabela_fila'];
    $total = 0;
    if ($tabela) {
        $count = @$db->querySingle("SELECT COUNT(*) FROM {$tabela}");
        $total = $count !== false ? $count : 0;
    }

    // Adiciona a origem, com fallback para 'contatos'
    $row['origem'] = $row['origem'] ?? 'contatos';
    $row['total_mensagens'] = $total;
    $campanhas[] = $row;
}
?>
