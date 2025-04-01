<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: ../index");
    exit;
}

$pastaHash = sha1($_SESSION['email']);
$caminhoBanco = "../clientes/{$pastaHash}/meubanco.sqlite";
$db = new SQLite3($caminhoBanco);

$id = $_POST['id'] ?? '';

if ($id) {
    $stmt = $db->prepare("DELETE FROM contatos WHERE id = ?");
    $stmt->bindValue(1, $id, SQLITE3_INTEGER);
    $result = $stmt->execute();

    if ($result) {
        $_SESSION['mensagem'] = 'Contato excluído com sucesso.';
    } else {
        $_SESSION['mensagem'] = 'Erro ao excluir o contato.';
    }
} else {
    $_SESSION['mensagem'] = 'ID inválido para exclusão.';
}

header("Location: ../painel&loc=contato");
exit;
