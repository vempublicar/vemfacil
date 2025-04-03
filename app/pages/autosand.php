<?php include "init/listar_campanhas.php" ?>

<div class="row justify-content-center">
    <div class="col-md-11">
        <div class="card card-custom mb-4 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Montar Lista de Envio Automático</h5>
                <button class="btn btn-sm btn-light toggle-form" type="button">Mostrar / Ocultar</button>
            </div>
            <div class="card-body " id="form-lead" style="display: none;">
                <form action="init/gerar_fila_envio.php" method="POST" id="formCampanha">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Nome da Campanha</label>
                            <input type="text" name="nome_campanha" class="form-control border-secondary" placeholder="Campanha Abril 2025" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Limite de envio</label>
                            <input type="number" name="limite_envios" class="form-control text-secondary border-secondary" value="100" min="1" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Origem dos dados</label>
                            <select name="origem" class="form-select border-secondary" required>
                                <option value="contatos">Contatos</option>
                                <option value="leads">Leads</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Filtro 1 (obrigatório)</label>
                            <select name="filtro1_coluna" class="form-select border-secondary" onchange="carregarValoresUnicos(1)" required>
                                <option value="">-- Escolha um filtro --</option>
                                <option value="status">Status</option>
                                <option value="etiqueta">Etiqueta</option>
                                <option value="data">Data</option>
                                <option value="prioridade">Prioridade</option>
                                <option value="variavelA">Variável A</option>
                                <option value="variavelB">Variável B</option>
                                <option value="variavelC">Variável C</option>
                                <option value="grupoA">Grupo A</option>
                                <option value="grupoB">Grupo B</option>
                                <option value="grupoC">Grupo C</option>
                            </select>
                            <select name="filtro1_valor" id="valoresFiltro1" class="form-select  border-secondary mt-2" required>
                                <option value="">-- Selecione um valor --</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Filtro 2 (opcional)</label>
                            <select name="filtro2_coluna" class="form-select border-secondary" onchange="carregarValoresUnicos(2)">
                                <option value="">-- Nenhum --</option>
                                <option value="status">Status</option>
                                <option value="etiqueta">Etiqueta</option>
                                <option value="data">Data</option>
                                <option value="prioridade">Prioridade</option>
                                <option value="variavelA">Variável A</option>
                                <option value="variavelB">Variável B</option>
                                <option value="variavelC">Variável C</option>
                                <option value="grupoA">Grupo A</option>
                                <option value="grupoB">Grupo B</option>
                                <option value="grupoC">Grupo C</option>
                            </select>
                            <select name="filtro2_valor" id="valoresFiltro2" class="form-select border-secondary mt-2">
                                <option value="">-- Selecione um valor --</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Mensagem a ser enviada</label>
                            <textarea name="mensagem" class="form-control border-secondary" rows="4" required></textarea>
                        </div>

                        <div class="col-12 text-end mt-3">
                            <button type="submit" class="btn btn-custom btn-sm ">Gerar Campanha de Envio</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Exibição na tela -->
        <h5 class="mt-4 mb-2">Campanhas Cadastradas</h5>
        <?php if (count($campanhas) === 0): ?>
            <div class="alert alert-secondary">Nenhuma campanha registrada ainda.</div>
        <?php else: ?>

            <div class="card card-custom p-1 mb-5">
                <div class=" p-0 mb-4">
                    <!-- Envolvendo a tabela com uma div de rolagem -->
                    <div class="scroll-wrapper p-1" style="max-height: 60vh; overflow-y: auto;">
                        <table class="table table-rounded align-middle text-center mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Campanha</th>
                                    <th>Filtros</th>
                                    <th>Data</th>
                                    <th>Mensagens</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $estadoInstancia = json_decode($_SESSION['instancia_valida'] ?? '{}', true);
                                $instanciaConectada = ($estadoInstancia['state'] ?? '') === 'OPEN';
                                ?>
                                <?php foreach ($campanhas as $camp): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($camp['nome']) ?> <span class="badge bg-secondary"><?= strtoupper($camp['origem']) ?></span></td>
                                        <td><small><?= nl2br(htmlspecialchars($camp['filtros'])) ?></small></td>
                                        <td><?= date('d/m/Y H:i', strtotime($camp['criada_em'])) ?></td>
                                        <td><?= $camp['total_mensagens'] ?></td>
                                        <td class="d-flex gap-1">
                                            <a href="painel&loc=ver_campanha&tabela=<?= urlencode($camp['tabela_fila']) ?>&camp=<?= $camp['id'] ?>" class="btn btn-sm btn-custom-secundario">Lista</a>
                                            <?php if ($instanciaConectada): ?>
                                                <form action="init/iniciar_envio.php" method="POST" class="d-inline">
                                                    <input type="hidden" name="tabela" value="<?= htmlspecialchars($camp['tabela_fila']) ?>">
                                                    <button type="submit" class="btn btn-sm btn-custom"><i class="bi bi-play-fill"></i> Iniciar Envio</button>
                                                </form>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-custom" disabled><i class="bi bi-play-fill"></i> Iniciar Envio</button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<script>
    function carregarValoresUnicos(filtro) {
        const colunaSelect = document.querySelector(`[name="filtro${filtro}_coluna"]`);
        const valorSelect = document.getElementById(`valoresFiltro${filtro}`);
        const coluna = colunaSelect?.value;
        const origem = document.querySelector('[name="origem"]')?.value || 'contatos';

        if (!coluna) {
            valorSelect.innerHTML = `<option value="">-- Selecione um valor --</option>`;
            return;
        }

        fetch(`init/valores_unicos.php?coluna=${encodeURIComponent(coluna)}&origem=${origem}`)
            .then(res => res.json())
            .then(valores => {
                valorSelect.innerHTML = `<option value="">-- Selecione um valor --</option>`;
                valores.forEach(v => {
                    const opt = document.createElement('option');
                    opt.value = v;
                    opt.textContent = v;
                    valorSelect.appendChild(opt);
                });
            });
    }

    // Alternar visibilidade do formulário e carregar valores iniciais, se necessário
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('toggle-form')) {
            const formBody = document.getElementById('form-lead');
            const isHidden = formBody.style.display === 'none';
            formBody.style.display = isHidden ? 'block' : 'none';

            // Se formulário está sendo exibido e filtro1 já tiver uma coluna selecionada, carrega os valores únicos
            if (isHidden) {
                const selectFiltro1 = document.querySelector('[name="filtro1_coluna"]');
                if (selectFiltro1?.value) {
                    carregarValoresUnicos(1);
                }
            }
        }
    });
</script>
