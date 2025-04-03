<?php
include 'init/listar_etapas.php';
include 'func/geral.php';
// Garante que existe uma etapa base
$etapaBase = $db->querySingle("SELECT nome FROM etapas_crm ORDER BY id ASC LIMIT 1");
if (!$etapaBase) {
    $stmt = $db->prepare("INSERT INTO etapas_crm (nome) VALUES (?)");
    $stmt->bindValue(1, 'Base');
    $stmt->execute();
    header("Location: painel&loc=crm");
    exit;
}

?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<div class="container-fluid">
    <div class="row">
        <div class="mb-3 d-flex align-items-start gap-2">
            <label for="busca-lead" class="form-label mt-2 me-2">Buscar Lead:</label>
            <input type="text" class="form-control form-control-sm w-50" id="busca-lead" placeholder="Nome, telefone ou e-mail...">
            <div id="resultados-leads" class="list-group position-absolute mt-5 z-3" style="width: 300px;"></div>
        </div>

        <div class="etapas-scroll ms-4" style="position: relative; width: 80vw; left: calc(-1 * var(--bs-gutter-x)); overflow-x: auto; padding-bottom: 1rem;">
            <div class="d-flex flex-nowrap px-3" style="height: 75vh;">
                <?php foreach ($etapas as $etapa): ?>
                    <div class="me-3" style="min-width: 300px;">
                        <div class="card bg-light mb-3">
                            <div class="card-header d-flex justify-content-between align-items-center"
                                style="background-color: <?= htmlspecialchars($etapa['cor'] ?? '#6c757d') ?>; color: #fff;">
                                <span class="fw-bold"><?= htmlspecialchars($etapa['nome']) ?></span>
                                <?php if (strtolower($etapa['nome']) === 'base'): ?>
                                    <span class="badge bg-secondary">Fixo</span>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-excluir-etapa text-white"
                                        data-id="<?= $etapa['id'] ?>" title="Excluir etapa"
                                        style="background: transparent; border: none;">🗑️</button>
                                <?php endif; ?>
                            </div>
                            <div class="card-body p-2" style="height: 65vh; overflow-y: auto;">
                                <ul class="list-group" id="etapa-<?= $etapa['id'] ?>" data-etapa="<?= htmlspecialchars($etapa['nome']) ?>">
                                    <?php foreach ($contatosPorEtapa[$etapa['nome']] ?? [] as $contato): ?>
                                        <li class="list-group-item mb-2" data-id="<?= $contato['id'] ?>"
                                            style="cursor: pointer;"
                                            onclick="abrirConversa(<?= $contato['id'] ?>, '<?= addslashes($contato['nome']) ?>', '<?= $contato['telefone'] ?>')">

                                            <div class="fw-bold d-flex justify-content-between align-items-center">
                                                <?= htmlspecialchars($contato['nome']) ?>
                                                <?php if (!empty($contato['notifica']) && $contato['notifica'] > 0): ?>
                                                    <span class="badge bg-success ms-2">
                                                        <i class="bi bi-chat-dots-fill me-1"></i><?= $contato['notifica'] ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>

                                            <div><small><?= htmlspecialchars($contato['telefone']) ?></small></div>
                                            <div class="mt-2 d-flex flex-wrap gap-1"><?= badgeStatusCRM($contato) ?></div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Card Nova Etapa -->
                <div class="me-3" style="min-width: 300px;">
                    <div class="card bg-dark text-white mb-3">
                        <div class="card-header text-center fw-bold">
                            <i class="bi bi-plus-circle me-1"></i>Nova Etapa
                        </div>
                        <div class="card-body p-3">
                            <form action="init/adicionar_etapa.php" method="POST">
                                <p>Cor e Nome da Etiqueta.</p>
                                <div class="mb-2 d-flex text-white">
                                    <input type="color" name="cor" class="form-control form-control-color bg-dark form-control-sm mb-2" value="#6c757d">
                                    <input type="text" name="nome" class="form-control form-control-sm mb-2 bg-dark text-white" required>
                                </div>
                                <button type="submit" class="btn btn-sm btn-light w-100">Adicionar</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div> <!-- Fim da rolagem -->
    </div>
</div>

<!-- Modal de Conversa -->
<?php
$estadoInstancia = json_decode($_SESSION['instancia_valida'] ?? '{}', true);
$instanciaConectada = ($estadoInstancia['state'] ?? '') === 'OPEN';
?>
<div class="modal fade" id="modalConversa" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content bg-dark text-white">
            <div class="modal-header border-0">
                <h5 class="modal-title">Conversa com <span id="nomeLead"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div id="retornoEnvio" class="mt-2 small text-muted"></div>
            <div class="modal-body">
                <div id="historicoMensagens" class="rounded p-3 mb-3" style="height: 300px; overflow-y: auto;">
                    <div class="text-center text-muted">Carregando mensagens...</div>
                </div>

                <?php if ($instanciaConectada): ?>
                    <form id="formResposta">
                        <input type="hidden" name="numero" id="numeroLead">
                        <div class="mb-3">
                            <textarea name="mensagem" id="mensagemTexto" class="form-control border-1" placeholder="Digite sua mensagem" rows="3"></textarea>
                        </div>
                        <button type="button" onclick="enviarMensagem()" class="btn btn-success w-25">Enviar Mensagem</button>

                    </form>
                <?php else: ?>
                    <input type="hidden" id="numeroLead">
                    <div class="alert alert-secondary text-center mb-0">Instância desconectada ou não pronta para envio.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/crm.js"></script>