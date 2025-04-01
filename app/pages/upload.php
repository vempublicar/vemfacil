<div class="container mt-4">
  <div class="card bg-dark shadow-sm text-white">
    <div class="card-header text-white d-flex justify-content-between align-items-center">
      <h5 class="mb-0">Importar Contatos por CSV</h5>
      <a href="modelo_leads.csv" class="btn btn-info btn-sm" download>Baixar Modelo CSV</a>
    </div>
    <div class="card-body">
      <?php if (!empty($_SESSION['mensagem'])): ?>
        <div class="alert alert-info"><?= $_SESSION['mensagem'] ?></div>
        <?php unset($_SESSION['mensagem']); ?>
      <?php endif; ?>

      <form action="init/processar_upload.php" method="POST" enctype="multipart/form-data">
        <div class="row align-items-end g-2">
          <div class="col-md-9">
            <label for="arquivo" class="form-label">Selecione o arquivo TSV</label>
            <input type="file" name="arquivo" id="arquivo" class="form-control bg-dark text-white" accept=".tsv" required>
          </div>
          <div class="col-md-3 d-flex justify-content-end">
            <button type="submit" class="btn btn-light w-100">Incorporar</button>
          </div>

          <small class="text-white-50">Apenas arquivos .tsv exportados do Google Sheets</small>
        </div>
      </form>
    </div>
  </div>
</div>
<div class="container mt-4">
  <div class="row d-flex justify-content-between">
    <!-- Gráfico de Importação -->
    <div class="col-md-5 p-4 text-center">
        <h6 class="mb-3">Importação de Contatos</h6>
        <canvas id="graficoImportacao"></canvas>
    </div>

    <!-- Gráfico de Situação -->
    <div class="col-md-5 p-4 text-center">
        <h6 class="mb-3">Situação Financeira</h6>
        <canvas id="graficoSituacao"></canvas>
    </div>
  </div>
</div>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const inseridos = <?= $_SESSION['importados'] ?? 0 ?>;
    const atualizados = <?= $_SESSION['atualizados'] ?? 0 ?>;

    const ativos = <?= $_SESSION['ativos'] ?? 0 ?>;
    const inadimplentes = <?= $_SESSION['inadimplentes'] ?? 0 ?>;

    if (window.Chart) {
      // Gráfico de Importação
      new Chart(document.getElementById('graficoImportacao'), {
        type: 'doughnut',
        data: {
          labels: ['Inseridos', 'Atualizados'],
          datasets: [{
            data: [inseridos, atualizados],
            backgroundColor: ['#007bff', '#ffc107'],
          }]
        },
        options: {
          plugins: {
            legend: {
              position: 'bottom'
            }
          }
        }
      });

      // Gráfico de Situação
      new Chart(document.getElementById('graficoSituacao'), {
        type: 'doughnut',
        data: {
          labels: ['Ativos', 'Inadimplentes'],
          datasets: [{
            data: [ativos, inadimplentes],
            backgroundColor: ['#28a745', '#dc3545'],
          }]
        },
        options: {
          plugins: {
            legend: {
              position: 'bottom'
            }
          }
        }
      });
    }
  });
</script>