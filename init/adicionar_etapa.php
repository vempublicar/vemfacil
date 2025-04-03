<?php
session_start();

$nome = trim($_POST['nome'] ?? '');
$cor = $_POST['cor'] ?? '#6c757d'; // cor padrão

if ($nome === '') {
  $_SESSION['mensagem'] = 'O nome da etapa não pode estar vazio.';
  header("Location: ../painel&loc=crm");
  exit;
}

$pastaHash = sha1($_SESSION['email']);
$caminhoBanco = "../clientes/{$pastaHash}/meubanco.sqlite";
$db = new SQLite3($caminhoBanco);

// Garante a existência da tabela
$db->exec("CREATE TABLE IF NOT EXISTS etapas_crm (id INTEGER PRIMARY KEY AUTOINCREMENT, nome TEXT NOT NULL)");

// Verifica se a coluna "cor" já existe (evita erro)
$colunaCorExiste = false;
$res = $db->query("PRAGMA table_info(etapas_crm)");
while ($coluna = $res->fetchArray(SQLITE3_ASSOC)) {
    if ($coluna['name'] === 'cor') {
        $colunaCorExiste = true;
        break;
    }
}
if (!$colunaCorExiste) {
    $db->exec("ALTER TABLE etapas_crm ADD COLUMN cor TEXT DEFAULT '#6c757d'");
}

// Verifica limite de 10 etapas
$quantidade = $db->querySingle("SELECT COUNT(*) FROM etapas_crm");
if ($quantidade >= 10) {
  $_SESSION['mensagem'] = 'Limite de 10 etapas atingido.';
  header("Location: ../painel&loc=crm");
  exit;
}

// Insere a nova etapa com a cor
$stmt = $db->prepare("INSERT INTO etapas_crm (nome, cor) VALUES (?, ?)");
$stmt->bindValue(1, $nome);
$stmt->bindValue(2, $cor);
$stmt->execute();

$_SESSION['mensagem'] = 'Nova etapa adicionada com sucesso.';
header("Location: ../painel&loc=crm");
exit;
