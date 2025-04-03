<?php
// Caminho da pasta de jobs
$jobsDir ='../jobs';

// Verifica se a pasta existe
if (!is_dir($jobsDir)) {
    die("Pasta de jobs não encontrada.");
}

// Lista arquivos JSON de jobs
$arquivos = glob("{$jobsDir}/envio_*.json");

foreach ($arquivos as $arquivo) {
    $json = file_get_contents($arquivo);
    $job = json_decode($json, true);

    if (!$job || !isset($job['fila'], $job['id_cliente'], $job['instancia'])) continue;

    // Se já está finalizado, pula
    if (($job['finalizado'] ?? false) === true) continue;

    $config = require '../init/api.php';
    $instancia = $job['instancia'];
    $fila = $job['fila'];
    $mensagens = $job['mensagens'] ?? [];

    // Envia cada mensagem
    foreach ($mensagens as &$msg) {
        if ($msg['status'] !== 'pendente') continue;

        $payload = json_encode(['number' => $msg['telefone'], 'text' => $msg['mensagem']]);

        $optsSend = [
            'http' => [
                'method' => 'POST',
                'header' => [
                    'Content-Type: application/json',
                    'apikey: ' . $config['apikey']
                ],
                'content' => $payload,
                'ignore_errors' => true
            ]
        ];

        $urlSend = $config['base_url'] . "/message/sendText/" . urlencode($instancia);
        $responseJson = @file_get_contents($urlSend, false, stream_context_create($optsSend));
        $response = json_decode($responseJson, true);

        $status = strtolower($response['status'] ?? '');
        $fromMe = $response['key']['fromMe'] ?? 0;
        $foi = ($fromMe == 1) && in_array($status, ['pending', 'delivered', 'sent']);

        $msg['status'] = $foi ? 'enviado' : 'falhou';
        $msg['resposta'] = $response;
        $msg['data_envio'] = date('Y-m-d H:i:s');

        file_put_contents($arquivo, json_encode($job, JSON_PRETTY_PRINT));
        sleep(15); // Aguarda 15 segundos entre mensagens
    }

    unset($msg);

    // Verifica se todas foram processadas
    $pendentes = array_filter($job['mensagens'], fn($m) => $m['status'] === 'pendente');
    if (count($pendentes) === 0) {
        $job['finalizado'] = true;
        $job['concluido_em'] = date('Y-m-d H:i:s');
        file_put_contents($arquivo, json_encode($job, JSON_PRETTY_PRINT));
    }
}

echo "Processamento concluído.\n";
