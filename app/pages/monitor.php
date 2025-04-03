<?php
$pastaHash = sha1($_SESSION['email'] ?? '');
$caminhoBanco = "clientes/{$pastaHash}/meubanco.sqlite";

if (!file_exists($caminhoBanco)) {
    echo "<div class='alert alert-danger'>Banco de dados não encontrado.</div>";
    exit;
}

$db = new SQLite3($caminhoBanco);

// Busca todas as campanhas
$campanhas = [];
$res = $db->query("SELECT * FROM campanhas ORDER BY criada_em DESC");
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    $tabela = $row['tabela_fila'];

    $total = $db->querySingle("SELECT COUNT(*) FROM {$tabela}");
    $enviadas = $db->querySingle("SELECT COUNT(*) FROM {$tabela} WHERE status = 'enviado'");
    $falhadas = $db->querySingle("SELECT COUNT(*) FROM {$tabela} WHERE status = 'falhou'");

    $row['total'] = $total;
    $row['enviadas'] = $enviadas;
    $row['falhadas'] = $falhadas;
    $row['percentual'] = $total > 0 ? round(($enviadas / $total) * 100) : 0;
    $row['status_campanha'] = $row['percentual'] >= 100 ? 'Finalizada' : 'Em andamento';

    $campanhas[] = $row;
}
?>

<div class="container mt-4">
  <h4 class="text-center mb-4">Monitoramento de Campanhas</h4>

  <?php if (count($campanhas) === 0): ?>
    <div class="alert alert-secondary text-center">Nenhuma campanha em andamento.</div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-rounded align-middle text-center">
        <thead>
          <tr>
            <th>Campanha</th>
            <th>Status</th>
            <th>Enviadas</th>
            <th>Falhadas</th>
            <th>Total</th>
            <th>Progresso</th>
            <th>Início</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($campanhas as $camp): ?>
            <tr>
              <td><?= htmlspecialchars($camp['nome']) ?></td>
              <td>
                <span class="table-status-badge bg-<?= $camp['status_campanha'] === 'Finalizada' ? 'success' : 'warning' ?>">
                  <?= $camp['status_campanha'] ?>
                </span>
              </td>
              <td><?= $camp['enviadas'] ?></td>
              <td><?= $camp['falhadas'] ?></td>
              <td><?= $camp['total'] ?></td>
              <td>
                <div class="progress">
                  <div class="progress-bar" style="width: <?= $camp['percentual'] ?>%;">
                    <?= $camp['percentual'] ?>%
                  </div>
                </div>
              </td>
              <td><?= date('d/m/Y H:i', strtotime($camp['criada_em'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>


<script>
    // Recarrega automaticamente a página a cada 10 segundos para atualização dos dados
    setTimeout(() => location.reload(), 10000);  
</script>