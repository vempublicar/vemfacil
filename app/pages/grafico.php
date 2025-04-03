<?php
include "func/indicadores.php";

// print_r($indicadores);
?>
<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="container my-4">
            <div class="row g-4">
                <!-- Card com indicadores principais -->
                <div class="col-md-3">
                    <div class="card card-translucent p-3 text-center text-white shadow-sm">
                        <h6>Leads Totais</h6>
                        <h2><?= $indicadores['leads_total'] ?></h2>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-translucent p-3 text-center text-white shadow-sm">
                        <h6>Contatos Totai</h6>
                        <h2><?= $indicadores['contatos_total'] ?></h2>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-translucent p-3 text-center text-white shadow-sm">
                        <h6>Mensagens Enviadas</h6>
                        <h2><?= $indicadores['mensagens_enviadas'] + ($totalEnviadasCampanhas ?? 0) ?></h2>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-translucent p-3 text-center text-white shadow-sm">
                        <h6>Falhas em Campanhas</h6>
                        <h2><?= $totalFalhasCampanhas ?></h2>
                    </div>
                </div>

                <!-- Gráfico de Leads por Status -->
                <div class="col-md-6">
                    <div class="mt-5">
                        <h5 class="mb-3">Status dos Leads</h5>
                        <canvas id="graficoStatusLeads"></canvas>
                    </div>
                </div>

                <!-- Gráfico de Campanhas -->
                <div class="col-md-6">
                    <div class="mt-5">
                        <h5 class="mb-3">Campanhas - Enviadas x Falhas</h5>
                        <canvas id="graficoCampanhas"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const cor1 = getComputedStyle(document.body).getPropertyValue('--grafico-cor1').trim();
        const cor2 = getComputedStyle(document.body).getPropertyValue('--grafico-cor2').trim();
        const cor3 = getComputedStyle(document.body).getPropertyValue('--grafico-cor4').trim();
        const cor4 = getComputedStyle(document.body).getPropertyValue('--grafico-cor5').trim();
        const cor5 = getComputedStyle(document.body).getPropertyValue('--grafico-cor3').trim();

        // Gráfico de Pizza para Leads por Status
        const ctxLeads = document.getElementById('graficoStatusLeads');
        new Chart(ctxLeads, {
            type: 'pie',
            data: {
                labels: <?= json_encode(array_keys($indicadores['leads']['status'] ?? [])) ?>,
                datasets: [{
                    label: 'Leads por Status',
                    data: <?= json_encode(array_values($indicadores['leads']['status'] ?? [])) ?>,
                    backgroundColor: [
                        cor1, cor2, cor3, cor4, cor5
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom'
                    }
                }
            }
        });

        // Gráfico de Pizza para Campanhas: Enviado vs Falhou
        const ctxCamp = document.getElementById('graficoCampanhas');
        new Chart(ctxCamp, {
            type: 'pie',
            data: {
                labels: ['Enviado', 'Falhou'],
                datasets: [{
                    data: [
                        <?= json_encode(array_reduce($indicadores['campanhas'], fn($carry, $item) => $carry + ($item['status']['enviado'] ?? 0), 0)) ?>,
                        <?= json_encode(array_reduce($indicadores['campanhas'], fn($carry, $item) => $carry + ($item['status']['falhou'] ?? 0), 0)) ?>
                    ],
                    backgroundColor: [cor1, cor2]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom'
                    }
                }
            }
        });
    });
</script>