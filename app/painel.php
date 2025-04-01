<?php
// print_r($_SESSION);
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Dashboard - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Ícones (opcional) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Estilo personalizado -->
    <link rel="stylesheet" href="assets/style.css">
</head>

<body>

    <div class="d-flex" id="wrapper">
        <!-- Sidebar -->
        <div class="bg-dark text-white p-3" id="sidebar">
            <h4 class="mb-4 text-center">app.vemfacil</h4>
            <hr>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link text-white" href="painel&loc=inicio">
                        <i class="bi bi-house"></i> Início
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="painel&loc=crm">
                        <i class="bi bi-person-lines-fill"></i> CRM
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="painel&loc=contato">
                        <i class="bi bi-envelope"></i> Contatos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="painel&loc=upload">
                        <i class="bi bi-cloud-upload"></i> Upload
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="painel&loc=configuracao">
                        <i class="bi bi-gear"></i> Configuração
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="painel&loc=grafico">
                        <i class="bi bi-bar-chart"></i> Gráficos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="painel&loc=conexao">
                        <i class="bi bi-diagram-3"></i> Conexão
                    </a>
                </li>
            </ul>
        </div>

        <!-- Conteúdo principal -->
        <div class="container-fluid ">
            <?php
            $instanciaAtiva = $_SESSION['instancia_ativa'] ?? null;
            ?>

            <nav class="navbar <?= $instanciaAtiva ? 'bg-success' : 'bg-dark' ?> navbar-dark mb-4 pt-0 pb-0" style="margin-left: -12px; margin-right: -12px; padding-left: 1rem; padding-right: 1rem;">

                <div class="container-fluid d-flex justify-content-between p-0">
                    <span class="navbar-text">
                        <?php if ($instanciaAtiva): ?>
                            <strong>Instância ativa:</strong> <?= htmlspecialchars($instanciaAtiva['instance']) ?> |
                            <strong>Número:</strong> <?= htmlspecialchars($instanciaAtiva['number']) ?>
                        <?php else: ?>
                            <strong>Instância offline:</strong> Nenhuma conexão ativa.
                        <?php endif; ?>
                    </span>
                    <span class="text-white">
                        <?= $_SESSION['email'] ?>
                    </span>
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