<?php

$pastaHash = sha1($_SESSION['email']);
$caminhoBanco = "clientes/{$pastaHash}/meubanco.sqlite";
$db = new SQLite3($caminhoBanco);

// Busca últimos 20 contatos
$result = $db->query("SELECT * FROM leads ORDER BY id DESC LIMIT 20");

?>

<div class="row justify-content-center">
    <div class="col-md-10">


        <!-- Mensagem -->
        <?php if (!empty($_SESSION['mensagem'])): ?>
            <div class="alert alert-success"><?= $_SESSION['mensagem'] ?></div>
            <?php unset($_SESSION['mensagem']); ?>
        <?php endif; ?>


        <!-- Tabela de contatos -->
        <h4 class="text-center mb-4">Meus Leads</h4>
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
                                <th>Tipo</th>
                                <th colspan="3">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="tabela-leads">
                            <?php if ($result): ?>
                                <?php while ($row = $result->fetchArray(SQLITE3_ASSOC)): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['nome']) ?></td>
                                        <td><?= htmlspecialchars($row['telefone']) ?></td>
                                        <td><?= htmlspecialchars($row['grupoB']) ?></td>
                                        <td>
                                            <?php if (empty($row['etiqueta']) && ($_SESSION['crm'] ?? 'on') !== 'off'): ?>
                                                <button type="button"
                                                    class="btn btn-outline-primary btn-sm"
                                                    onclick="converterParaCliente(<?= $row['id'] ?>)">
                                                    Contato
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <form action="init/excluir_lead.php" method="POST" style="display:inline;" onsubmit="return confirm('Deseja realmente excluir este lead?');">
                                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                <input type="hidden" name="tabela" value="leads">
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
    // 6. Busca dinâmica + reaplica os botões
    document.getElementById('busca-contato').addEventListener('input', function() {
        const termo = this.value.trim();

        fetch('init/buscar_leads.php?termo=' + encodeURIComponent(termo))
            .then(res => res.text())
            .then(html => {
                document.querySelector('#tabela-leads').innerHTML = html;
                aplicarEventosEditar(); // <- Aqui reaplicamos o clique em "Editar"
            });
    });

    function converterParaCliente(id) {
        fetch('init/lead_contato.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'id=' + id
            })
            .then(res => res.text())
            .then(msg => {
                alert(msg);
                location.reload();
            });
    }
</script>