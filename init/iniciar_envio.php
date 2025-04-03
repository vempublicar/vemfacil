<?php
session_start();

$tabela = $_POST['tabela'] ?? '';
if (!$tabela || !preg_match('/^fila_[a-f0-9]{40}$/', $tabela)) {
    $_SESSION['erro_envio'] = 'Tabela inválida.';
    header('Location: ../painel&loc=autosand');
    exit;
}

// Executa o processamento da fila (envio das mensagens)
require '../func/processar_fila.php';

// Redireciona para a tela de monitoramento
header('Location: ../painel&loc=monitor');
exit;
