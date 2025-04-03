<?php
include "func/indicadores.php";
?>

<div class="row justify-content-center">
  <div class="col-md-10">
    <h4 class="mb-4 text-center">Visão Geral</h4>

    <div class="row g-3">
      <div class="col-md-4">
        <div class="card card-translucent p-3 text-center text-white shadow-sm">
          <h6>Total de Leads</h6>
          <h2><?= $totalLeads ?></h2>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card card-translucent p-3 text-center text-white shadow-sm">
          <h6>Total de Contatos</h6>
          <h2><?= $totalClientes ?></h2>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card card-translucent p-3 text-center text-white shadow-sm">
          <h6>Total de Envios</h6>
          <h2><?= $totalMensagensEnviadas ?></h2>
        </div>
      </div>
    </div>

    <hr class="my-4">

    <!-- Gráfico (exemplo) -->
    <div class="card card-custom shadow-sm p-4">
      <h6 class="mb-3 text-center">Evolução de Leads e Contatos</h6>
      <canvas id="graficoDashboard" style="height: 300px;"></canvas>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('graficoDashboard');
    new Chart(ctx, {
      type: 'line',
      data: {
        labels: <?= json_encode($ultimosMeses) ?>,
        datasets: [{
            label: 'Leads',
            data: <?= json_encode($leadsMes) ?>,
            backgroundColor: 'transparent',
            borderColor: getComputedStyle(document.body).getPropertyValue('--grafico-cor1').trim(),
            pointBackgroundColor: getComputedStyle(document.body).getPropertyValue('--grafico-cor1').trim(),
            pointRadius: 4,
            borderWidth: 2,
            tension: 0.4
          },
          {
            label: 'Contatos',
            data: <?= json_encode($clientesMes) ?>,
            backgroundColor: 'transparent',
            borderColor: getComputedStyle(document.body).getPropertyValue('--grafico-cor5').trim(),
            pointBackgroundColor: getComputedStyle(document.body).getPropertyValue('--grafico-cor5').trim(),
            pointRadius: 4,
            borderWidth: 2,
            tension: 0.4
          }
        ]
      },
      options: {
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              color: '#000',
              boxWidth: 12,
              padding: 16
            }
          },
          tooltip: {
            backgroundColor: '#222',
            titleColor: '#fff',
            bodyColor: '#ccc',
            borderColor: '#444',
            borderWidth: 1
          }
        },
        scales: {
          x: {
            ticks: {
              color: '#aaa'
            },
            grid: {
              color: 'rgba(255,255,255,0.05)'
            }
          },
          y: {
            beginAtZero: true,
            ticks: {
              color: '#aaa'
            },
            grid: {
              color: 'rgba(255,255,255,0.05)'
            }
          }
        }
      }
    });
  });
</script>