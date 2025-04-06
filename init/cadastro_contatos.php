<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: ../index");
    exit;
}

function formatarTelefone($numero) {
    // Remove tudo que não for número
    $numero = preg_replace('/\D/', '', $numero);
  
    // Se tiver 10 ou 11 dígitos (sem DDI), adiciona o 55
    if (strlen($numero) === 10 || strlen($numero) === 11) {
        $numero = '55' . $numero;
    }
  
    return $numero;
}

$pastaHash = sha1($_SESSION['email']);
$caminhoBanco = "../clientes/{$pastaHash}/meubanco.sqlite";
$db = new SQLite3($caminhoBanco);

// Recebe os dados do formulário
$id         = $_POST['id'] ?? '';
$telefone   = formatarTelefone($_POST['telefone']) ?? '';
$nome       = $_POST['nome'] ?? '';
$email      = $_POST['email'] ?? '';
$status     = $_POST['status'] ?? 'ativo';
$prioridade = $_POST['prioridade'] ?? '';
$etiqueta   = $_POST['etiqueta'] ?? '';
$retorno    = $_POST['retorno'] ?? '';
$data       = $_POST['data'] ?? '';
$grupoA     = $_POST['grupoA'] ?? '';

// Caminho para o arquivo JSON
$numeroCriptografado = hash('sha256', $telefone);
$jsonPath = "../clientes/{$pastaHash}/mensagens/{$numeroCriptografado}.json";

function adicionarMensagemAoJson($path, $numero, $mensagem, $tipo) {
    if (!file_exists($path)) {
        $dados = ['mensagens' => []];
    } else {
        $conteudoAtual = file_get_contents($path);
        $dados = json_decode($conteudoAtual, true);
    }

    $dados['mensagens'][] = [
        'numero' => $numero,
        'mensagem' => $mensagem,
        'horario' => date('c'),
        'tipo' => $tipo
    ];

    file_put_contents($path, json_encode($dados));
}

if ($telefone) {
    if ($id) {
        // Atualiza lead existente
        $stmt = $db->prepare("
            UPDATE contatos SET 
            telefone = ?, 
            nome = ?, 
            email = ?, 
            status = ?, 
            prioridade = ?, 
            etiqueta = ?, 
            retorno = ?, 
            data = ?, 
            grupoA = ?, 
            data_alteracao = datetime('now') 
            WHERE id = ?
        ");
        $stmt->bindValue(1, $telefone);
        $stmt->bindValue(2, $nome);
        $stmt->bindValue(3, $email);
        $stmt->bindValue(4, $status);
        $stmt->bindValue(5, $prioridade);
        $stmt->bindValue(6, $etiqueta);
        $stmt->bindValue(7, $retorno);
        $stmt->bindValue(8, $data);
        $stmt->bindValue(9, $grupoA);
        $stmt->bindValue(10, $id);

        if ($stmt->execute()) {
            $_SESSION['mensagem'] = 'Lead atualizado com sucesso.';
            adicionarMensagemAoJson($jsonPath, $telefone, "Lead atualizado: " . $nome, 'enviada');
        } else {
            $_SESSION['mensagem'] = 'Erro ao atualizar o lead.';
        }

    } else {
        // Insere novo lead
        $stmt = $db->prepare("
            INSERT INTO contatos 
            (telefone, nome, email, status, prioridade, etiqueta, retorno, data, grupoA, data_criacao) 
            VALUES 
            (?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'))
        ");
        $stmt->bindValue(1, $telefone);
        $stmt->bindValue(2, $nome);
        $stmt->bindValue(3, $email);
        $stmt->bindValue(4, $status);
        $stmt->bindValue(5, $prioridade);
        $stmt->bindValue(6, $etiqueta);
        $stmt->bindValue(7, $retorno);
        $stmt->bindValue(8, $data);
        $stmt->bindValue(9, $grupoA);

        if ($stmt->execute()) {
            $_SESSION['mensagem'] = 'Lead cadastrado com sucesso.';
            adicionarMensagemAoJson($jsonPath, $telefone, "Novo lead cadastrado: " . $nome, 'enviada');
        } else {
            $_SESSION['mensagem'] = 'Erro ao cadastrar o lead.';
        }
    }
} else {
    $_SESSION['mensagem'] = 'Telefone é obrigatório.';
}

header("Location: ../painel&loc=contato");
exit;
