<?php
// Garante que o usuário está logado
if (!isset($_SESSION['email'])) {
    header("Location: ../index");
    exit;
}

// Monta o caminho para o banco do cliente
$pastaHash = sha1($_SESSION['email']);
$caminhoBanco = "clientes/{$pastaHash}/meubanco.sqlite";

// Abre conexão com o banco
$db = new SQLite3($caminhoBanco);
$db->exec("CREATE TABLE IF NOT EXISTS conexao (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    instance_name TEXT UNIQUE NOT NULL,
    number TEXT UNIQUE NOT NULL,
    status TEXT NOT NULL DEFAULT 'pendente',
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
  )");
// Consulta as instâncias cadastradas
$result = $db->query("SELECT * FROM conexao ORDER BY criado_em DESC");
?>


<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card card-custom mb-4 shadow-sm">
            <div class="card-header bg-transparent border-1">
                <h5 class="mb-0">Cadastrar nova instância</h5>
            </div>
            <div class="card-body">
                <form action="init/db.php" method="POST">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="instance_name" class="form-label">Nome da instância</label>
                            <input type="text" name="instance_name" id="instance_name" class="form-control form-control-custom" placeholder="Ex: minha-instancia" required>
                        </div>
                        <div class="col-md-4">
                            <label for="number" class="form-label">Número com DDD</label>
                            <input type="text" name="number" id="number" class="form-control form-control-custom" placeholder="Ex: 11999999999" required>
                        </div>
                        <div class="col-md-4 d-flex align-items-end justify-content-end">
                            <button class="btn btn-custom w-100">Salvar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($_SESSION['qrcode'])): ?>
    <div class="text-center my-4">
        <h5 class="text-white mb-3">📲 Escaneie o QR Code para conectar o WhatsApp</h5>
        <img src="<?= $_SESSION['qrcode'] ?>" alt="QR Code" class="img-fluid" style="max-width: 300px;">
        <?php unset($_SESSION['qrcode']); ?>
    </div>
<?php endif; ?>

<?php if (!empty($_SESSION['mensagem'])): ?>
    <div class="alert alert-success mt-3"><?= $_SESSION['mensagem'] ?></div>
    <?php unset($_SESSION['mensagem']); ?>
<?php endif; ?>

<h4 class="text-white text-center mt-5">Instâncias</h4>
<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="table-responsive mt-3">
            <table class="table table-rounded align-middle text-center">
                <thead>
                    <tr>
                        <th>Instância</th>
                        <th>Número</th>
                        <th>Status</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetchArray(SQLITE3_ASSOC)): ?>
                        <?php
                        $isAtiva = !empty($_SESSION['instancia_ativa']) &&
                            $_SESSION['instancia_ativa']['instance'] === $row['instance_name'];
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($row['instance_name']) ?></td>
                            <td><?= htmlspecialchars($row['number']) ?></td>
                            <td><?= $row['status'] ?></td>
                            <td>
                                <?php if ($row['status'] === 'pendente'): ?>
                                    <form action="init/criar_instancia.php" method="POST" class="d-inline">
                                        <input type="hidden" name="instance" value="<?= $row['instance_name'] ?>">
                                        <input type="hidden" name="number" value="<?= $row['number'] ?>">
                                        <button class="btn btn-success btn-sm">Criar</button>
                                    </form>
                                <?php elseif ($isAtiva): ?>
                                    <button class="btn btn-success btn-sm" disabled>Conectado</button>
                                    <form action="init/desconectar_instancia.php" method="POST" class="d-inline">
                                        <input type="hidden" name="instance" value="<?= $row['instance_name'] ?>">
                                        <button class="btn btn-danger btn-sm">Desconectar</button>
                                    </form>
                                <?php else: ?>
                                    <form action="init/conectar_instancia.php" method="POST" class="d-inline">
                                        <input type="hidden" name="instance" value="<?= $row['instance_name'] ?>">
                                        <input type="hidden" name="number" value="<?= $row['number'] ?>">
                                        <button class="btn btn-secondary btn-sm">Conectar</button>
                                    </form>
                                    <form action="init/excluir_instancia.php" method="POST" class="d-inline" onsubmit="return confirm('Deseja realmente excluir esta instância?');">
                                        <input type="hidden" name="instance" value="<?= $row['instance_name'] ?>">
                                        <button class="btn btn-outline-danger btn-sm">Excluir</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>