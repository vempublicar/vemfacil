<?php
include 'init/listar_etapas.php';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<div class="container-fluid">
    <div class="row">
        <div class="mb-3">
            <label class="form-label">Buscar Lead para adicionar:</label>
            <input type="text" class="form-control" id="busca-lead" placeholder="Nome, telefone ou e-mail...">
            <div id="resultados-leads" class="list-group mt-1"></div>
        </div>

        <?php foreach ($etapas as $etapa): ?>
            <div class="col-md-3">
                <div class="card bg-light mb-3">
                    <div class="card-header text-center fw-bold"> <?= htmlspecialchars($etapa['nome']) ?> </div>
                    <div class="card-body p-2">
                        <ul class="list-group min-vh-25" id="etapa-<?= $etapa['id'] ?>" data-etapa="<?= htmlspecialchars($etapa['nome']) ?>">
                            <?php foreach ($contatosPorEtapa[$etapa['nome']] ?? [] as $contato): ?>
                                <li class="list-group-item mb-2" data-id="<?= $contato['id'] ?>">
                                    <strong><?= htmlspecialchars($contato['nome']) ?></strong><br>
                                    <small><?= htmlspecialchars($contato['telefone']) ?></small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Coluna fixa para adicionar nova etapa -->
        <div class="col-md-3">
            <div class="card bg-dark text-white mb-3">
                <div class="card-header text-center fw-bold">Nova Etapa</div>
                <div class="card-body p-3">
                    <form action="init/adicionar_etapa.php" method="POST">
                        <div class="mb-2">
                            <input type="text" name="nome" class="form-control form-control-sm" placeholder="Nome da etapa" required>
                        </div>
                        <button type="submit" class="btn btn-sm btn-light w-100">Adicionar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('[id^="etapa-"]').forEach(coluna => {
            new Sortable(coluna, {
                group: 'etapas',
                animation: 150,
                onAdd: function(evt) {
                    const contatoId = evt.item.dataset.id;
                    const novaEtapa = evt.to.dataset.etapa;
                    fetch('init/update_etiqueta.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `id=${contatoId}&etiqueta=${encodeURIComponent(novaEtapa)}`
                    });
                }
            });
        });

        document.getElementById('btn-add-etapa').addEventListener('click', function() {
            const nome = document.getElementById('nova-etapa').value.trim();
            if (!nome) return alert('Digite um nome para a etapa');

            fetch('init/adicionar_etapa.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'nome=' + encodeURIComponent(nome)
            }).then(() => location.reload());
        });

        document.getElementById('busca-lead').addEventListener('input', function() {
            const termo = this.value.trim();
            if (termo.length < 3) {
                document.getElementById('resultados-leads').innerHTML = '';
                return;
            }

            fetch('init/buscar_leads_crm.php?termo=' + encodeURIComponent(termo))
                .then(res => res.json())
                .then(leads => {
                    const container = document.getElementById('resultados-leads');
                    container.innerHTML = '';
                    leads.forEach(lead => {
                        const item = document.createElement('a');
                        item.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';
                        item.innerHTML = `
              <span><strong>${lead.nome}</strong> - ${lead.telefone}</span>
              <button class="btn btn-sm btn-outline-primary" onclick="adicionarLead(${lead.id})">Adicionar</button>
            `;
                        container.appendChild(item);
                    });
                });
        });
    });

    function adicionarLead(id) {
        fetch('init/update_etiqueta.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `id=${id}&etiqueta=<?= urlencode($etapas[0]['nome'] ?? 'Primeira Etapa') ?>`
        }).then(() => location.reload());
    }
</script>