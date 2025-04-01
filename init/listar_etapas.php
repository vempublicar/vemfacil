<?php
$pastaHash = sha1($_SESSION['email']);
$caminhoBanco = "clientes/{$pastaHash}/meubanco.sqlite";
$db = new SQLite3($caminhoBanco);

// Garante a existência da tabela
$db->exec("CREATE TABLE IF NOT EXISTS etapas_crm (id INTEGER PRIMARY KEY AUTOINCREMENT, nome TEXT NOT NULL)");

// Busca todas as etapas
$etapas = [];
$res = $db->query("SELECT * FROM etapas_crm ORDER BY id ASC LIMIT 10");
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    $etapas[] = $row;
}

// Você também pode preparar aqui a lógica de contatos por etapa se quiser reaproveitar
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
