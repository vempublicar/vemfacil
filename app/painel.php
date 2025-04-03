<?php
$planoUsuario = $_GET['p'] ?? 'pro'; // Ex: 'elite', 'start', etc.
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title><?= ucfirst($planoUsuario) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Ícones (opcional) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <!-- Estilo personalizado -->
    <link rel="stylesheet" href="assets/style.css">
</head>

<body class="painel plano-<?= $planoUsuario ?>">

    <div class="d-flex" id="wrapper">
        <!-- Sidebar -->
        <div class="p-1" id="sidebar">
            <h4 class="mb-4 text-center text-uppercase">
                <?= ucfirst($planoUsuario) ?>
            </h4>
            <hr>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link text-white" href="painel&loc=inicio">
                        <i class="bi bi-house"></i> Início
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="painel&loc=grafico">
                        <i class="bi bi-bar-chart"></i> Indicadores
                    </a>
                </li>                
                <li class="nav-item">
                    <a class="nav-link text-white" href="painel&loc=crm">
                        <i class="bi bi-card-list"></i> CRM
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="painel&loc=autosand">
                        <i class="bi bi-robot"></i> AutoSand
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white d-flex align-items-center" href="painel&loc=contato">
                        <i class="bi bi-person-check me-2"></i>
                        <span>Meus <strong>Contatos</strong></span>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link text-white d-flex align-items-center" href="painel&loc=leads">
                        <i class="bi bi-person-lines-fill me-2"></i>
                        <span>Meus <strong>Leads</strong></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="painel&loc=monitor">
                        <i class="bi bi-display"></i> Monitor
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white d-flex align-items-center" href="painel&loc=upload">
                        <i class="bi bi-cloud-upload me-2"></i>
                        <span>Importar <strong>Clientes</strong></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white d-flex align-items-center" href="painel&loc=upload_leads">
                        <i class="bi bi-cloud-arrow-up me-2"></i>
                        <span>Importar <strong>Leads</strong></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="painel&loc=conexao">
                        <i class="bi bi-diagram-3"></i> Conectar Número
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="painel&loc=configuracao">
                        <i class="bi bi-gear"></i> Configuração
                    </a>
                </li>
            </ul>
        </div>

        <!-- Conteúdo principal -->
        <div class="container-fluid ">
            <?php
            include 'init/verifica_conexao.php';
            $instanciaAtiva = $_SESSION['instancia_ativa'] ?? null;
            $estadoInstancia = json_decode($_SESSION['instancia_valida'] ?? '{}', true);
            $instanciaConectada = ($estadoInstancia['state'] ?? '') === 'OPEN';
            if($estadoInstancia['state'] == 'OPEN'){$cor = 'success';}else{$cor = 'danger';}
            ?>

            <nav class="navbar navbar-ajustada mb-4 pt-0 pb-0">
                <div class="container-fluid d-flex justify-content-between p-0">
                    <span class="navbar-text" id="statusConexaoTexto">
                        <?php if ($instanciaAtiva): ?>
                            <strong>Instância ativa:</strong> <?= htmlspecialchars($instanciaAtiva['instance']) ?> |
                            <strong>Número:</strong> <?= htmlspecialchars($instanciaAtiva['number']) ?> |
                            <strong><b class="text-<?= $cor ?>"><?= $estadoInstancia['state'] ?? 'DESCONHECIDO' ?></b></strong>
                        <?php else: ?>
                            <strong>Instância offline:</strong>
                        <?php endif; ?>
                    </span>
                    <span class="text-white"><?= $_SESSION['email'] ?></span>
                </div>
            </nav>
            <!-- Aqui entra a lógica de conteúdo dinâmico -->
            <div class="mt-4">
                <?php
                $pasta = "app/pages/";
                $pagina = $_GET['loc'] ?? 'inicio'; // padrão: inicio

                $arquivo = $pasta . basename($pagina) . ".php"; // evita path traversal

                if (file_exists($arquivo)) {
                    include($arquivo);
                } else {
                    echo '<div class="alert alert-warning">Página não encontrada.</div>';
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS + Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('grafico').getContext('2d');
        const grafico = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun'],
                datasets: [{
                    label: 'R$',
                    data: [1200, 1900, 3000, 2500, 4000, 4700],
                    fill: false,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: false
                    }
                }
            }
        });
    </script>


</body>

</html>