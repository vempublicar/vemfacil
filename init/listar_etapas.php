<?php
// Caminho para o banco do cliente
$pastaHash = sha1($_SESSION['email']);
$caminhoBanco = "clientes/{$pastaHash}/meubanco.sqlite";
$db = new SQLite3($caminhoBanco);

// Garante a existência da tabela de etapas
$db->exec("CREATE TABLE IF NOT EXISTS etapas_crm (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome TEXT NOT NULL
)");

// Cria a etapa "Base" se não existir
$verificaBase = $db->querySingle("SELECT COUNT(*) FROM etapas_crm WHERE nome = 'Base'");
if ($verificaBase == 0) {
    $stmtBase = $db->prepare("INSERT INTO etapas_crm (nome) VALUES ('Base')");
    $stmtBase->execute();
}

// Adiciona a etapa "Base" manualmente
$etapas = [['id' => 0, 'nome' => 'Base']];

// Busca as outras etapas cadastradas (excluindo "Base")
$res = $db->query("SELECT * FROM etapas_crm WHERE nome != 'Base' ORDER BY id ASC LIMIT 9");
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    $etapas[] = $row;
}

// Agrupa os contatos por etapa (etiqueta_crm)
$contatosPorEtapa = [];
foreach ($etapas as $etapa) {
    $stmt = $db->prepare("SELECT * FROM contatos WHERE etiqueta = ? ORDER BY prioridade DESC, nome ASC");
    $stmt->bindValue(1, $etapa['nome']);
    $resContatos = $stmt->execute();

    while ($contato = $resContatos->fetchArray(SQLITE3_ASSOC)) {
        $contatosPorEtapa[$etapa['nome']][] = $contato;
    }
}
?>
