<?php
$pastaHash = sha1($_SESSION['email'] ?? '');
$caminhoBanco = "clientes/{$pastaHash}/meubanco.sqlite";
$idCampanha = $_GET['camp'] ?? '';

if (!file_exists($caminhoBanco)) {
    echo "<div class='alert alert-danger'>Banco de dados não encontrado.</div>";
    exit;
}

$db = new SQLite3($caminhoBanco);

$tabela = $_GET['tabela'] ?? '';
if (!$tabela || !preg_match('/^fila_[a-f0-9]{40}$/', $tabela)) {
    echo "<div class='alert alert-warning'>Tabela inválida.</div>";
    exit;
}

// Verifica se a tabela existe
$check = $db->querySingle("SELECT name FROM sqlite_master WHERE type='table' AND name='{$tabela}'");
if (!$check) {
    echo "<div class='alert alert-danger'>Tabela da campanha não encontrada.</div>";
    exit;
}

// Busca os registros
$res = $db->query("SELECT * FROM {$tabela} ORDER BY id DESC");
$dados = [];
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    $dados[] = $row;
}
$estadoInstancia = json_decode($_SESSION['instancia_valida'] ?? '{}', true);
$instanciaConectada = ($estadoInstancia['state'] ?? '') === 'OPEN';
?>

<div class="row justify-content-center">
    <div class="col-md-10 mt-2">
        <h4 class="text-center" >Lista da Campanha</h4>

        <?php if (count($dados) === 0): ?>
            <div class="alert alert-info">Nenhuma mensagem encontrada nesta campanha.</div>
        <?php else: ?>
            <div class="card card-custom p-1 mb-5">
                <div class=" p-0 mb-4">
                    <!-- Envolvendo a tabela com uma div de rolagem -->
                    <div class="scroll-wrapper p-1" style="max-height: 60vh; overflow-y: auto;">
                        <table class="table table-rounded align-middle text-center mb-0">
                            <thead class="table-dark sticky-top">
                                <tr>
                                    <th>ID</th>
                                    <th>Telefone</th>
                                    <th>Mensagem</th>
                                    <th>Status</th>
                                    <th>Tentativas</th>
                                    <th>Criado em</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dados as $item): ?>
                                    <tr>
                                        <td><?= $item['id'] ?></td>
                                        <td><?= htmlspecialchars($item['telefone']) ?></td>
                                        <td><?= htmlspecialchars($item['mensagem']) ?></td>
                                        <td>
                                            <?php
                                            $status = strtolower($item['status']);
                                            $badge = 'secondary';
                                            if ($status === 'enviado') $badge = 'success';
                                            elseif ($status === 'falhou') $badge = 'danger';
                                            elseif ($status === 'pendente') $badge = 'warning';
                                            ?>
                                            <span class="badge bg-<?= $badge ?>"><?= ucfirst($status) ?></span>
                                        </td>
                                        <td><?= $item['tentativa'] ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($item['criado_em'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="mt-4 d-flex justify-content-between flex-wrap gap-2">
                <a href="painel&loc=autosand" class="btn btn-custom-link">⬅ Voltar</a>

                <div class="d-flex flex-wrap gap-2">
                    <form action="init/excluir_campanha.php" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir esta campanha? Esta ação não poderá ser desfeita.')">
                        <input type="hidden" name="id" value="<?= $idCampanha ?>">
                        <input type="hidden" name="tabela" value="<?= htmlspecialchars($tabela) ?>">
                        <button type="submit" class="btn btn-custom-danger">Excluir Campanha</button>
                    </form>
                    <?php if ($instanciaConectada): ?>
                    <a href="init/iniciar_envio.php?tabela=<?= urlencode($tabela) ?>" class="btn btn-custom-secundario">
                    <i class="bi bi-play-fill"></i>
                     Iniciar Envio
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>