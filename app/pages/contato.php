<?php

$pastaHash = sha1($_SESSION['email']);
$caminhoBanco = "clientes/{$pastaHash}/meubanco.sqlite";
$db = new SQLite3($caminhoBanco);

// Busca últimos 20 contatos
$result = $db->query("SELECT * FROM contatos ORDER BY id DESC LIMIT 20");
?>

<div class="row">
    <!-- Formulário -->
    <div class="col-md-12">
        <div class="card bg-dark mb-4 shadow-sm">
            <div class="card-header text-white d-flex justify-content-between">
                <h5 class="mb-0">Adicionar Lead</h5>
                <button class="btn btn-sm btn-light toggle-form" type="button">Mostrar / Ocultar</button>
            </div>
            <div class="card-body text-white" id="form-lead" style="display: none;">
                <form action="init/cadastro_lead.php" method="POST">
                    <div class="row g-2">
                        <div class="col-md-1 mb-2">
                            <label class="form-label">ID</label>
                            <input type="text" id="lead-id" name="id" class="form-control bg-dark" readonly>
                        </div>
                        <div class="col-md-4 mb-2">
                            <label class="form-label">Nome</label>
                            <input type="text" name="nome" class="form-control bg-dark text-white">
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="form-label">Telefone *</label>
                            <input type="text" name="telefone" class="form-control phone bg-dark text-white" required>
                        </div>
                        <div class="col-md-4 mb-2">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control bg-dark text-white">
                        </div>

                        <div class="col-md-4 mb-2">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select bg-dark text-white">
                                <option value="ativo">Ativo</option>
                                <option value="pendente">Pendente</option>
                                <option value="inadimplente">Inadimplente</option>
                            </select>
                        </div>

                        <div class="col-md-4 mb-2">
                            <label class="form-label">Prioridade</label>
                            <select name="prioridade" class="form-select bg-dark text-white">
                                <option value="crítica">Crítica</option>
                                <option value="alta">Alta</option>
                                <option value="média">Média</option>
                                <option value="baixa">Baixa</option>
                            </select>
                        </div>

                        <div class="col-md-4 mb-2">
                            <label class="form-label">Etiqueta</label>
                            <select name="etiqueta" class="form-select bg-dark text-white">
                                <option value="novo_contato">Novo Contato</option>
                                <option value="orçamento_enviado">Orçamento Enviado</option>
                                <option value="aguardando_resposta">Aguardando Resposta</option>
                                <option value="cliente_fiel">Cliente Fiel</option>
                                <option value="interesse_baixo">Interesse Baixo</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-2">
                            <label class="form-label">Motivo do Retorno</label>
                            <textarea name="retorno" class="form-control bg-dark text-white" rows="2" placeholder="Descreva aqui o motivo e a forma do retorno..."></textarea>
                        </div>

                        <div class="col-md-2 mb-2">
                            <label class="form-label">Data Retorno</label>
                            <input type="text" name="data" class="form-control date-mask bg-dark text-white" placeholder="dd/mm/aaaa">
                        </div>

                        <div class="col-md-4 mb-2">
                            <label class="form-label">Grupo A</label>
                            <select name="grupoA" class="form-select bg-dark text-white">
                                <option value="urgencia">Urgência</option>
                                <option value="envio">Envio</option>
                                <option value="vencido">Vencidos</option>
                                <option value="novo">Novo</option>
                            </select>
                        </div>
                        <div class="col-md-12 d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-outline-light btn-sm w-25" id="cancelar-edicao">Cancelar</button>
                            <button type="submit" class="btn btn-light btn-sm w-25">Salvar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php if (!empty($_SESSION['mensagem'])): ?>
        <div class="alert alert-info"><?= $_SESSION['mensagem'] ?></div>
        <?php unset($_SESSION['mensagem']); ?>
    <?php endif; ?>

    <div class="mb-3">
        <input type="text" id="busca-contato" class="form-control" placeholder="Buscar por nome, telefone, email ou grupo...">
    </div>
    <!-- Tabela -->
    <div class="col-md-12">
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0">Últimos Contatos</h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Telefone</th>
                            <th>Status</th>
                            <th>Prioridade</th>
                        </tr>
                    </thead>
                    <tbody id="tabela-contatos">
                        <?php while ($row = $result->fetchArray(SQLITE3_ASSOC)): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['nome']) ?></td>
                                <td><?= htmlspecialchars($row['telefone']) ?></td>
                                <td><?= htmlspecialchars($row['status']) ?></td>
                                <td><?= htmlspecialchars($row['prioridade']) ?></td>
                                <td>
                                    <a href="#" class="btn btn-outline-secondary btn-sm">CRM</a>
                                </td>
                                <td>
                                    <button
                                        type="button"
                                        class="btn btn-outline-primary btn-sm editar-lead"
                                        data-id="<?= $row['id'] ?>"
                                        data-nome="<?= htmlspecialchars($row['nome']) ?>"
                                        data-telefone="<?= htmlspecialchars($row['telefone']) ?>"
                                        data-email="<?= htmlspecialchars($row['email']) ?>"
                                        data-status="<?= $row['status'] ?>"
                                        data-prioridade="<?= $row['prioridade'] ?>"
                                        data-etiqueta="<?= $row['etiqueta'] ?>"
                                        data-retorno="<?= htmlspecialchars($row['retorno']) ?>"
                                        data-data="<?= $row['data'] ?>"
                                        data-grupoa="<?= $row['grupoA'] ?>">Editar
                                    </button>
                                </td>
                                <td>
                                    <form action="init/excluir_lead.php" method="POST" style="display:inline;" onsubmit="return confirm('Deseja realmente excluir este contato?');">
                                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm">Excluir</button>
                                    </form>
                                </td>

                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script>
    function aplicarEventosEditar() {
        document.querySelectorAll('.editar-lead').forEach(btn => {
            btn.addEventListener('click', function () {
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
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        // 1. Máscara para telefone
        document.querySelectorAll('.phone').forEach(input => {
            input.addEventListener('input', function () {
                let value = this.value.replace(/\D/g, '');
                if (value.length > 11) value = value.slice(0, 11);
                this.value = value.replace(/^(\d{2})(\d{5})(\d{4})$/, '($1) $2-$3');
            });
        });

        // 2. Máscara para data
        document.querySelectorAll('.date-mask').forEach(input => {
            input.addEventListener('input', function () {
                let value = this.value.replace(/\D/g, '').slice(0, 8);
                this.value = value.replace(/^(\d{2})(\d{2})(\d{0,4})$/, '$1/$2/$3');
            });
        });

        // 3. Toggle do formulário
        document.addEventListener('click', function (e) {
            if (e.target.classList.contains('toggle-form')) {
                const formBody = document.getElementById('form-lead');
                formBody.style.display = (formBody.style.display === 'none') ? 'block' : 'none';
            }
        });

        // 4. Botão de cancelar
        document.getElementById('cancelar-edicao').addEventListener('click', function () {
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
    document.getElementById('busca-contato').addEventListener('input', function () {
        const termo = this.value.trim();

        fetch('init/buscar_leads.php?termo=' + encodeURIComponent(termo))
            .then(res => res.text())
            .then(html => {
                document.querySelector('#tabela-contatos').innerHTML = html;
                aplicarEventosEditar(); // <- Aqui reaplicamos o clique em "Editar"
            });
    });
</script>
