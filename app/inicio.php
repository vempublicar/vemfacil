<?php
// Dados da sua instância
$instanceName = 'Anna';
$token = '9A067462C095-4B60-90F3-345D90F5AD70';

// Endpoint para gerar o QR Code (confirme na documentação se o caminho é esse)
$apiUrl = "https://evolutionapi.vemfacil.com.br/v2/instance/{$instanceName}/qr?token={$token}";

// Chamada à API via cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);

if (curl_errno($ch)) {
    $error_msg = curl_error($ch);
}
curl_close($ch);

if (isset($error_msg)) {
    $qrCodeImage = null;
    $errorMessage = $error_msg;
} else {
    $data = json_decode($response, true);
    // Supondo que a resposta contenha a chave "qr" com a imagem do QR code
    $qrCodeImage = $data['qr'] ?? null;
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Conectar WhatsApp via Evolution API</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h1>Conectar WhatsApp</h1>
    <?php if ($qrCodeImage): ?>
        <p>Escaneie o QR Code abaixo com o aplicativo do WhatsApp para conectar sua conta:</p>
        <div class="text-center">
            <img src="<?php echo htmlspecialchars($qrCodeImage); ?>" alt="QR Code de Conexão" class="img-fluid">
        </div>
    <?php else: ?>
        <div class="alert alert-danger">
            Erro ao gerar o QR Code: <?php echo htmlspecialchars($errorMessage); ?>
        </div>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
