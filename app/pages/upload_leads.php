<div class="row justify-content-center">
  <div class="col-md-10">
    <div class="card card-custom">
      <div class="card-header border-1 d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Importar Leads por CSV</h5>
        <a href="modelo_leads.xlsx" class="btn btn-outline-dark btn-sm" download>Baixar Modelo</a>
      </div>
      <div class="card-body">
        <?php if (!empty($_SESSION['mensagem'])): ?>
          <div class="alert alert-success"><?= $_SESSION['mensagem'] ?></div>
          <?php unset($_SESSION['mensagem']); ?>
        <?php endif; ?>

        <form action="init/processar_upload.php" method="POST" enctype="multipart/form-data">
          <input type="hidden" name="tabela" value="leads">
          <div class="row align-items-end g-3">
            <div class="col-md-9">
              <label for="arquivo" class="form-label">Selecione o arquivo TSV</label>
              <input type="file" name="arquivo" id="arquivo" class="form-control form-control-custom" accept=".tsv" required>
            </div>
            <div class="col-md-3 d-flex justify-content-end">
              <button type="submit" class="btn btn-custom w-100">Incorporar Leads</button>
            </div>
          </div>
          <small class="text-white-50 d-block mt-2">Apenas arquivos .tsv exportados do Google Sheets</small>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="row justify-content-center">
  <div class="col-md-10">
    <div class="row justify-content-between">
      <!-- Gráfico de Importação -->
      <div class="col-md-5 p-4 text-center">
        <h6 class="mb-3 text-white">Importação de Leads</h6>
        <canvas id="graficoImportacao"></canvas>
      </div>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const inseridos = <?= $_SESSION['leads_importados'] ?? 0 ?>;
    const atualizados = <?= $_SESSION['leads_atualizados'] ?? 0 ?>;

    const cor1 = getComputedStyle(document.body).getPropertyValue('--grafico-cor1').trim();
    const cor2 = getComputedStyle(document.body).getPropertyValue('--grafico-cor5').trim() || '';

    if (window.Chart) {
      new Chart(document.getElementById('graficoImportacao'), {
        type: 'doughnut',
        data: {
          labels: ['Inseridos', 'Atualizados'],
          datasets: [{
            data: [inseridos, atualizados],
            backgroundColor: [cor1, cor2],
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
