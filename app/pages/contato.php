<?php

$pastaHash = sha1($_SESSION['email']);
$caminhoBanco = "clientes/{$pastaHash}/meubanco.sqlite";
$db = new SQLite3($caminhoBanco);

// Busca últimos 20 contatos
$result = $db->query("SELECT * FROM contatos ORDER BY id DESC LIMIT 20");
$etiquetas = [];
$etc = $db->query("SELECT * FROM etapas_crm ORDER BY id ASC");

while ($row = $etc->fetchArray(SQLITE3_ASSOC)) {
    $etiquetas[] = $row['nome'];
}
?>

<div class="row justify-content-center">
    <div class="col-md-10">

        <!-- Formulário -->
        <div class="card card-custom mb-4 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Adicionar Contato</h5>
                <button class="btn btn-custom-outline btn-sm toggle-form" type="button">Mostrar / Ocultar</button>
            </div>
            <div class="card-body" id="form-lead" style="display: none;">
                <form action="init/cadastro_contatos.php" method="POST">
                    <div class="row g-2">
                        <div class="col-md-1 mb-2">
                            <label class="form-label">ID</label>
                            <input type="text" id="lead-id" name="id" class="form-control form-control-custom" readonly>
                        </div>
                        <div class="col-md-4 mb-2">
                            <label class="form-label">Nome</label>
                            <input type="text" name="nome" class="form-control form-control-custom">
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="form-label">Telefone *</label>
                            <input type="text" name="telefone" class="form-control form-control-custom phone" required>
                        </div>
                        <div class="col-md-4 mb-2">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control form-control-custom">
                        </div>

                        <div class="col-md-4 mb-2">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select form-control-custom">
                                <option value="ativo">Ativo</option>
                                <option value="pendente">Pendente</option>
                                <option value="inadimplente">Inadimplente</option>
                            </select>
                        </div>

                        <div class="col-md-4 mb-2">
                            <label class="form-label">Prioridade</label>
                            <select name="prioridade" class="form-select form-control-custom">
                                <option value="crítica">Crítica</option>
                                <option value="alta">Alta</option>
                                <option value="média">Média</option>
                                <option value="baixa">Baixa</option>
                            </select>
                        </div>

                        <div class="col-md-4 mb-2">
                            <label class="form-label">Etiqueta</label>
                            <select name="etiqueta" class="form-select form-control-custom">
                                <option value="">Selecione</option>
                                <?php foreach ($etiquetas as $etiqueta): ?>
                                    <option value="<?= htmlspecialchars($etiqueta) ?>">
                                        <?= ucwords(str_replace('_', ' ', $etiqueta)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6 mb-2">
                            <label class="form-label">Motivo do Retorno</label>
                            <textarea name="retorno" class="form-control form-control-custom" rows="2" placeholder="Descreva aqui o motivo e a forma do retorno..."></textarea>
                        </div>

                        <div class="col-md-2 mb-2">
                            <label class="form-label">Data Retorno</label>
                            <input type="text" name="data" class="form-control form-control-custom date-mask" placeholder="dd/mm/aaaa">
                        </div>

                        <div class="col-md-4 mb-2">
                            <label class="form-label">Grupo A</label>
                            <select name="grupoA" class="form-select form-control-custom">
                                <option value="urgencia">Urgência</option>
                                <option value="envio">Envio</option>
                                <option value="vencido">Vencidos</option>
                                <option value="novo">Novo</option>
                            </select>
                        </div>

                        <div class="col-md-12 d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-custom-outline btn-sm w-25" id="cancelar-edicao">Cancelar</button>
                            <button type="submit" class="btn btn-custom btn-sm w-25">Salvar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Mensagem -->
        <?php if (!empty($_SESSION['mensagem'])): ?>
            <div class="alert alert-success"><?= $_SESSION['mensagem'] ?></div>
            <?php unset($_SESSION['mensagem']); ?>
        <?php endif; ?>


        <!-- Tabela de contatos -->
        <h4 class="text-center mb-4">Meus Contatos</h4>
        <!-- Campo de busca -->
        <div class="mb-3">
            <input type="text" id="busca-contato" class="form-control form-control-custom" placeholder="Buscar por nome, telefone, email ou grupo...">
        </div>

        <div class="card card-custom p-1 mb-5">
            <div class=" p-0 mb-4">
                <!-- Envolvendo a tabela com uma div de rolagem -->
                <div class="scroll-wrapper p-1" style="max-height: 60vh; overflow-y: auto;">
                    <table class="table table-rounded align-middle text-center mb-0">
                        <thead class="table-dark sticky-top">
                            <tr>
                                <th>Nome</th>
                                <th>Telefone</th>
                                <th>Status</th>
                                <th>Prioridade</th>
                                <th colspan="3">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="tabela-contatos">
                            <?php if ($result): ?>
                                <?php while ($row = $result->fetchArray(SQLITE3_ASSOC)): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['nome']) ?></td>
                                        <td><?= htmlspecialchars($row['telefone']) ?></td>
                                        <td><?= htmlspecialchars($row['status']) ?></td>
                                        <td><?= htmlspecialchars($row['prioridade']) ?></td>
                                        <td>
                                            <?php if (empty($row['etiqueta']) && ($_SESSION['crm'] ?? 'on') !== 'off'): ?>
                                                <button type="button"
                                                    class="btn btn-outline-primary btn-sm"
                                                    onclick="adicionarLead(<?= $row['id'] ?>)">
                                                    CRM
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button type="button"
                                                class="btn btn-custom btn-sm editar-lead"
                                                data-id="<?= $row['id'] ?>"
                                                data-nome="<?= htmlspecialchars($row['nome']) ?>"
                                                data-telefone="<?= htmlspecialchars($row['telefone']) ?>"
                                                data-email="<?= htmlspecialchars($row['email']) ?>"
                                                data-status="<?= $row['status'] ?>"
                                                data-prioridade="<?= $row['prioridade'] ?>"
                                                data-etiqueta="<?= $row['etiqueta'] ?>"
                                                data-retorno="<?= htmlspecialchars($row['retorno']) ?>"
                                                data-data="<?= $row['data'] ?>"
                                                data-grupoa="<?= $row['grupoA'] ?>">
                                                Editar
                                            </button>
                                        </td>
                                        <td>
                                            <form action="init/excluir_lead.php" method="POST" style="display:inline;" onsubmit="return confirm('Deseja realmente excluir este contato?');">
                                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                <input type="hidden" name="tabela" value="contatos">
                                                <button type="submit" class="btn btn-outline-danger btn-sm">Excluir</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6">Nenhum dado encontrado.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>


    </div>
</div>


<script>
    function aplicarEventosEditar() {
        document.querySelectorAll('.editar-lead').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelector('input[name="id"]').value = this.dataset.id;
                document.querySelector('input[name="nome"]').value = this.dataset.nome;
                document.querySelector('input[name="telefone"]').value = this.dataset.telefone;
                document.querySelector('input[name="email"]').value = this.dataset.email;
                document.querySelector('select[name="status"]').value = this.dataset.status;
                document.querySelector('select[name="prioridade"]').value = this.dataset.prioridade;
                document.querySelector('select[name="etiqueta"]').value = this.dataset.etiqueta;
                document.querySelector('textarea[name="retorno"]').value = this.dataset.retorno;
                document.querySelector('input[name="data"]').value = this.dataset.data;
                document.querySelector('select[name="grupoA"]').value = this.dataset.grupoa;

                document.getElementById('form-lead').style.display = 'block';
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        // 1. Máscara para telefone
        document.querySelectorAll('.phone').forEach(input => {
            input.addEventListener('input', function() {
                let value = this.value.replace(/\D/g, '');
                if (value.length > 11) value = value.slice(0, 11);
                this.value = value.replace(/^(\d{2})(\d{5})(\d{4})$/, '($1) $2-$3');
            });
        });

        // 2. Máscara para data
        document.querySelectorAll('.date-mask').forEach(input => {
            input.addEventListener('input', function() {
                let value = this.value.replace(/\D/g, '').slice(0, 8);
                this.value = value.replace(/^(\d{2})(\d{2})(\d{0,4})$/, '$1/$2/$3');
            });
        });

        // 3. Toggle do formulário
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('toggle-form')) {
                const formBody = document.getElementById('form-lead');
                formBody.style.display = (formBody.style.display === 'none') ? 'block' : 'none';
            }
        });

        // 4. Botão de cancelar
        document.getElementById('cancelar-edicao').addEventListener('click', function() {
            const form = document.querySelector('form');
            form.reset();
            if (form.querySelector('input[name="id"]')) {
                form.querySelector('input[name="id"]').value = '';
            }
            document.getElementById('form-lead').style.display = 'none';
        });

        // 5. Chamar os eventos de editar
        aplicarEventosEditar();
    });

    // 6. Busca dinâmica + reaplica os botões
    document.getElementById('busca-contato').addEventListener('input', function() {
        const termo = this.value.trim();

        fetch('init/buscar_contatos.php?termo=' + encodeURIComponent(termo))
            .then(res => res.text())
            .then(html => {
                document.querySelector('#tabela-contatos').innerHTML = html;
                aplicarEventosEditar(); // <- Aqui reaplicamos o clique em "Editar"
            });
    });

    function adicionarLead(id) {
        fetch('init/update_etiqueta.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `id=${id}&etiqueta=Base`
        }).then(() => location.reload());
    }
</script>