<?php
$pastaHash = sha1($_SESSION['email'] ?? '');
$caminhoBanco = "../clientes/{$pastaHash}/meubanco.sqlite";

if (!file_exists($caminhoBanco)) {
    die("Banco não encontrado.");
}

$db = new SQLite3($caminhoBanco);
$config = require 'api.php';

$limitePorExecucao = 10;
$limiteTentativas = 3;

$instancia = $_SESSION['instancia_ativa']['instance'] ?? null;
if (!$instancia) {
    echo "Instância não definida.";
    exit;
}

// Verifica conexão uma vez
$url = $config['base_url'] . "/instance/connectionState/" . urlencode($instancia);
$opts = [
    'http' => [
        'method' => 'GET',
        'header' => ['Content-Type: application/json', 'apikey: ' . $config['apikey']],
    ]
];
$estadoJson = @file_get_contents($url, false, stream_context_create($opts));
$estado = json_decode($estadoJson, true);
if (($estado['instance']['state'] ?? '') !== 'open') {
    echo "Instância não conectada.";
    exit;
}

// Cria pasta de jobs se não existir
@mkdir('../jobs');

$resCamp = $db->query("SELECT * FROM campanhas ORDER BY criada_em ASC");

while ($campanha = $resCamp->fetchArray(SQLITE3_ASSOC)) {
    $tabela = $campanha['tabela_fila'];

    $check = $db->querySingle("SELECT name FROM sqlite_master WHERE type='table' AND name='{$tabela}'");
    if (!$check) continue;

    $stmt = $db->prepare("SELECT * FROM {$tabela} WHERE status = 'pendente' AND tentativa < ? LIMIT ?");
    $stmt->bindValue(1, $limiteTentativas, SQLITE3_INTEGER);
    $stmt->bindValue(2, 1000, SQLITE3_INTEGER); // Captura máx. 1000 p/ estimar
    $res = $stmt->execute();

    $fila = [];
    while ($msg = $res->fetchArray(SQLITE3_ASSOC)) {
        $fila[] = [
            'id' => $msg['id'],
            'telefone' => $msg['telefone'],
            'mensagem' => $msg['mensagem'],
            'tentativa' => $msg['tentativa']
        ];
    }

    if (count($fila) === 0) continue;

    // Cálculo de tempo estimado
    $total = count($fila);
    $minutos = ceil($total / 4); // 4 por minuto
    $inicio = date('Y-m-d H:i:s');
    $fim = date('Y-m-d H:i:s', strtotime("+{$minutos} minutes"));

    // Monta JSON
    $json = [
        'cliente_hash' => $pastaHash,
        'instancia' => $instancia,
        'tabela' => $tabela,
        'inicio_previsto' => $inicio,
        'fim_previsto' => $fim,
        'mensagens_totais' => $total,
        'tempo_estimado' => "$minutos minutos",
        'dados' => $fila,
        'finalizado' => false
    ];

    file_put_contents("../jobs/{$pastaHash}_{$tabela}.json", json_encode($json, JSON_PRETTY_PRINT));
}

echo "Fila exportada em JSON com sucesso.";
