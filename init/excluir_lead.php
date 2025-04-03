<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: ../index");
    exit;
}

$pastaHash = sha1($_SESSION['email']);
$caminhoBanco = "../clientes/{$pastaHash}/meubanco.sqlite";
$db = new SQLite3($caminhoBanco);

// Pegando dados do POST
$id = $_POST['id'] ?? '';
$tabela = $_POST['tabela'] ?? 'contatos';

// Segurança: aceita apenas 'contatos' ou 'leads'
if (!in_array($tabela, ['contatos', 'leads'])) {
    $_SESSION['mensagem'] = 'Tabela inválida.';
    header("Location: ../painel");
    exit;
}

if ($id) {
    $stmt = $db->prepare("DELETE FROM {$tabela} WHERE id = ?");
    $stmt->bindValue(1, $id, SQLITE3_INTEGER);
    $result = $stmt->execute();

    if ($result) {
        $_SESSION['mensagem'] = ucfirst($tabela) . ' excluído com sucesso.';
    } else {
        $_SESSION['mensagem'] = 'Erro ao excluir da tabela ' . $tabela . '.';
    }
} else {
    $_SESSION['mensagem'] = 'ID inválido para exclusão.';
}

// Redireciona para a página correta
$destino = $tabela === 'leads' ? 'upload_leads' : 'contato';
header("Location: ../painel&loc={$destino}");
exit;
