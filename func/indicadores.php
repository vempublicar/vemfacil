<?php

if (!isset($_SESSION['email'])) {
  header("Location: ../index");
  exit;
}

$pastaHash = sha1($_SESSION['email']);
$caminhoBanco = "clientes/{$pastaHash}/meubanco.sqlite";
$db = new SQLite3($caminhoBanco);

// Função utilitária
function contar($db, $tabela, $condicao = '') {
  $sql = "SELECT COUNT(*) as total FROM {$tabela}";
  if ($condicao) $sql .= " WHERE $condicao";
  $res = $db->querySingle($sql);
  return $res ?: 0;
}

// ========== Leads ==========
$indicadores['leads_total'] = contar($db, 'leads');
$indicadores['leads_etiqueta_contato'] = contar($db, 'leads', "etiqueta = 'contato'");

$res = $db->query("SELECT status, COUNT(*) as qtd FROM leads GROUP BY status");
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    $indicadores['leads']['status'][strtolower($row['status'])] = $row['qtd'];
}

$res = $db->query("SELECT grupoB, COUNT(*) as qtd FROM leads GROUP BY grupoB");
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    $indicadores['leads']['grupoB'][strtolower($row['grupoB'])] = $row['qtd'];
}

// ========== Contatos ==========
$indicadores['contatos_total'] = contar($db, 'contatos');

$res = $db->query("SELECT status, COUNT(*) as qtd FROM contatos GROUP BY status");
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    $indicadores['contatos']['status'][strtolower($row['status'])] = $row['qtd'];
}

$res = $db->query("SELECT etiqueta, COUNT(*) as qtd FROM contatos GROUP BY etiqueta");
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    $indicadores['contatos']['etiqueta'][strtolower($row['etiqueta'])] = $row['qtd'];
}

$indicadores['contatos_com_retorno'] = contar($db, 'contatos', "data IS NOT NULL AND TRIM(data) != ''");

$res = $db->query("SELECT prioridade, COUNT(*) as qtd FROM contatos GROUP BY prioridade");
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    $indicadores['contatos']['prioridade'][strtolower($row['prioridade'])] = $row['qtd'];
}

foreach (['variavelA', 'variavelB', 'variavelC'] as $variavel) {
  $res = $db->query("SELECT $variavel as tipo, COUNT(*) as qtd FROM contatos WHERE $variavel IS NOT NULL AND TRIM($variavel) != '' GROUP BY $variavel");
  while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    $indicadores["contatos"][$variavel][strtolower($row['tipo'])] = $row['qtd'];
  }
}

foreach (['grupoA', 'grupoB', 'grupoC'] as $grupo) {
  $res = $db->query("SELECT $grupo as tipo, COUNT(*) as qtd FROM contatos WHERE $grupo IS NOT NULL AND TRIM($grupo) != '' GROUP BY $grupo");
  while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    $indicadores["contatos"][$grupo][strtolower($row['tipo'])] = $row['qtd'];
  }
}

$indicadores['contatos_nao_lidas'] = $db->querySingle("SELECT SUM(CAST(notifica AS INTEGER)) FROM contatos");

// ========== Mensagens ==========
$indicadores['mensagens_recebidas'] = contar($db, 'mensagens', "tipo = 'recebida'");
$indicadores['mensagens_enviadas'] = contar($db, 'mensagens', "tipo = 'enviada'");
$indicadores['numeros_unicos_recebidos'] = $db->querySingle("SELECT COUNT(DISTINCT numero) FROM mensagens WHERE tipo = 'recebida'");

// ========== Instâncias ==========
$indicadores['instancias_total'] = contar($db, 'conexao');

// ========== Campanhas ==========
$indicadores['campanhas_total'] = contar($db, 'campanhas');

// Cria a tabela de cache se não existir
$db->exec("CREATE TABLE IF NOT EXISTS campanhas_resultado (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tabela_fila TEXT UNIQUE,
    total_enviadas INTEGER DEFAULT 0,
    total_falhas INTEGER DEFAULT 0,
    total_mensagens INTEGER DEFAULT 0,
    processado_em TEXT DEFAULT CURRENT_TIMESTAMP
)");

$res = $db->query("SELECT * FROM campanhas ORDER BY criada_em DESC");

while ($camp = $res->fetchArray(SQLITE3_ASSOC)) {
    $tabelaFila = $camp['tabela_fila'];

    // Verifica se a tabela da fila existe
    $check = $db->querySingle("SELECT name FROM sqlite_master WHERE type='table' AND name='$tabelaFila'");
    if (!$check) continue;

    // Verifica se já existe cache
    $resultadoExistente = $db->querySingle("SELECT COUNT(*) FROM campanhas_resultado WHERE tabela_fila = '$tabelaFila'");

    if ($resultadoExistente) {
        $totalEnviadas = (int) $db->querySingle("SELECT total_enviadas FROM campanhas_resultado WHERE tabela_fila = '$tabelaFila'");
        $totalFalhas   = (int) $db->querySingle("SELECT total_falhas FROM campanhas_resultado WHERE tabela_fila = '$tabelaFila'");
        $totalMensagens = (int) $db->querySingle("SELECT total_mensagens FROM campanhas_resultado WHERE tabela_fila = '$tabelaFila'");
    } else {
        $totalMensagens = (int) $db->querySingle("SELECT COUNT(*) FROM {$tabelaFila}");
        $totalEnviadas  = (int) $db->querySingle("SELECT COUNT(*) FROM {$tabelaFila} WHERE status = 'enviado'");
        $totalFalhas    = (int) $db->querySingle("SELECT COUNT(*) FROM {$tabelaFila} WHERE status = 'falhou'");

        $ins = $db->prepare("INSERT INTO campanhas_resultado (tabela_fila, total_enviadas, total_falhas, total_mensagens) VALUES (?, ?, ?, ?)");
        $ins->bindValue(1, $tabelaFila);
        $ins->bindValue(2, $totalEnviadas);
        $ins->bindValue(3, $totalFalhas);
        $ins->bindValue(4, $totalMensagens);
        $ins->execute();
    }

    // Salva os dados para exibição (evita contagem dupla depois)
    $indicadores['campanhas'][] = [
        'nome' => $camp['nome'],
        'tabela' => $tabelaFila,
        'criada_em' => $camp['criada_em'],
        'total' => $totalMensagens,
        'status' => [
            'enviado' => $totalEnviadas,
            'falhou'  => $totalFalhas
        ]
    ];
}

// ==============================
// Cálculo global de envios e falhas
// ==============================
$totalEnviadasCampanhas = 0;
$totalFalhasCampanhas = 0;

// Carrega tabelas já cacheadas
$tabelasFinalizadas = [];
$resFinalizadas = $db->query("SELECT tabela_fila FROM campanhas_resultado");
while ($row = $resFinalizadas->fetchArray(SQLITE3_ASSOC)) {
    $tabelasFinalizadas[] = $row['tabela_fila'];
}

// Soma apenas campanhas não finalizadas
foreach ($indicadores['campanhas'] as $campanha) {
    $tabela = $campanha['tabela'] ?? '';
    if (in_array($tabela, $tabelasFinalizadas)) continue;

    $totalEnviadasCampanhas += (int)($campanha['status']['enviado'] ?? 0);
    $totalFalhasCampanhas   += (int)($campanha['status']['falhou'] ?? 0);
}

// Soma os dados cacheados
$res = $db->query("SELECT total_enviadas, total_falhas FROM campanhas_resultado");
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    $totalEnviadasCampanhas += (int)$row['total_enviadas'];
    $totalFalhasCampanhas   += (int)$row['total_falhas'];
}

$totalLeads           = $indicadores['leads_total'] ?? 0;
$totalClientes        = $indicadores['contatos_total'] ?? 0;
$clientesAdimplentes  = $indicadores['contatos']['status']['ativo'] ?? 0;
$totalMensagens       = $indicadores['mensagens_enviadas'] ?? 0;
$totalMensagensEnviadas = $totalEnviadasCampanhas ?? 0;
$conversasIniciadas   = $indicadores['numeros_unicos_recebidos'] ?? 0;
$leadsRecentes        = $db->querySingle("SELECT COUNT(*) FROM leads WHERE data_criacao >= date('now', '-7 days')");

$leadsMes = [];
$clientesMes = [];
$ultimosMeses = [];

for ($i = 5; $i >= 0; $i--) {
  $mesRef = date('Y-m', strtotime("-{$i} months"));
  $ultimosMeses[] = date('M/Y', strtotime("-{$i} months"));

  $qtdLeads = $db->querySingle("SELECT COUNT(*) FROM leads WHERE strftime('%Y-%m', data_criacao) = '$mesRef'");
  $qtdContatos = $db->querySingle("SELECT COUNT(*) FROM contatos WHERE strftime('%Y-%m', data_criacao) = '$mesRef'");

  $leadsMes[] = (int)$qtdLeads;
  $clientesMes[] = (int)$qtdContatos;
}


// Você pode salvar os dados em SESSION se for usar depois
// $_SESSION['indicadores'] = $indicadores;


