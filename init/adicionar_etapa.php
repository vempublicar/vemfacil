<?php
session_start();


$nome = trim($_POST['nome'] ?? '');

if ($nome === '') {
  $_SESSION['mensagem'] = 'O nome da etapa não pode estar vazio.';
  header("Location: ../painel&loc=crm");
  exit;
}

$pastaHash = sha1($_SESSION['email']);
$caminhoBanco = "../clientes/{$pastaHash}/meubanco.sqlite";
$db = new SQLite3($caminhoBanco);

// Cria a tabela caso não exista
$db->exec("CREATE TABLE IF NOT EXISTS etapas_crm (id INTEGER PRIMARY KEY AUTOINCREMENT, nome TEXT NOT NULL)");

// Verifica limite
$quantidade = $db->querySingle("SELECT COUNT(*) FROM etapas_crm");
if ($quantidade >= 10) {
  $_SESSION['mensagem'] = 'Limite de 10 etapas atingido.';
  header("Location: ../painel&loc=crm");
  exit;
}

// Insere
$stmt = $db->prepare("INSERT INTO etapas_crm (nome) VALUES (?)");
$stmt->bindValue(1, $nome);
$stmt->execute();

$_SESSION['mensagem'] = 'Nova etapa adicionada com sucesso.';
header("Location: ../painel&loc=crm");
exit;
