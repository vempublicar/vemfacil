<?php
$config = require 'api.php';

$instancia = $_SESSION['instancia_ativa']['instance'] ?? null;
if ($instancia) {
    $url = $config['base_url'] . "/instance/connectionState/" . urlencode($instancia);

    $options = [
        'http' => [
            'method' => 'GET',
            'header' => [
                'Content-Type: application/json',
                'apikey: ' . $config['apikey']
            ]
        ]
    ];

    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);
    $data = json_decode($response, true);

    // Corrigido: busca do state dentro de "instance"
    $estado = strtoupper($data['instance']['state'] ?? 'off');

    $_SESSION['instancia_valida'] = json_encode(['state' => $estado]);
}else{
    $_SESSION['instancia_valida'] = json_encode(['state' => 'DESCONECTADO']);
}
