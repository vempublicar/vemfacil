<?php
session_start();

if (!isset($_SESSION['email'])) {
  header("Location: ../index");
  exit;
}

$pastaHash = sha1($_SESSION['email']);
$caminhoBanco = "../clientes/{$pastaHash}/meubanco.sqlite";
$db = new SQLite3($caminhoBanco);

function formatarTelefone($numero) {
  $numero = preg_replace('/\D/', '', $numero);
  if (strlen($numero) === 10 || strlen($numero) === 11) {
    $numero = '55' . $numero;
  }
  return $numero;
}

// ⚠️ Verifica se foi enviada a tabela (contatos ou leads)
$tabela = $_POST['tabela'] ?? 'contatos';
if (!in_array($tabela, ['contatos', 'leads'])) {
  $_SESSION['mensagem'] = "Tabela inválida.";
  header("Location: ../painel&loc=upload");
  exit;
}

$importados = 0;
$atualizados = 0;

if (
  isset($_FILES['arquivo']) &&
  $_FILES['arquivo']['error'] === UPLOAD_ERR_OK &&
  pathinfo($_FILES['arquivo']['name'], PATHINFO_EXTENSION) === 'tsv'
) {
  $arquivoTmp = $_FILES['arquivo']['tmp_name'];
  $handle = fopen($arquivoTmp, 'r');

  if ($handle) {
    $header = fgetcsv($handle, 1000, "\t");

    if (count($header) < 4 || strtolower(trim($header[0])) !== 'telefone') {
      $_SESSION['mensagem'] = "Formato inválido. Use o modelo com colunas: telefone, nome, email, status.";
      header("Location: ../painel&loc=upload");
      exit;
    }

    while (($linha = fgetcsv($handle, 1000, "\t")) !== false) {
      $telefone = formatarTelefone($linha[0] ?? '');
      $nome     = trim($linha[1] ?? '');
      $email    = trim($linha[2] ?? '');
      $status   = trim($linha[3] ?? '');
      

      if (!$telefone || !$status) continue;

      $verifica = $db->prepare("SELECT id FROM $tabela WHERE telefone = ?");
      $verifica->bindValue(1, $telefone);
      $existe = $verifica->execute()->fetchArray(SQLITE3_ASSOC);

      if ($existe) {
        $stmt = $db->prepare("UPDATE $tabela SET nome = ?, email = ?, status = ?, data_alteracao = datetime('now') WHERE telefone = ?");
        $stmt->bindValue(1, $nome);
        $stmt->bindValue(2, $email);
        $stmt->bindValue(3, $status);
        $stmt->bindValue(4, $telefone);
        $stmt->execute();
        $atualizados++;
      } else {
        $grupo   = 'novo';
        $stmt = $db->prepare("INSERT INTO $tabela (telefone, nome, email, status, grupoB, data_criacao) VALUES (?, ?, ?, ?, ?, datetime('now'))");
        $stmt->bindValue(1, $telefone);
        $stmt->bindValue(2, $nome);
        $stmt->bindValue(3, $email);
        $stmt->bindValue(4, $status);
        $stmt->bindValue(5, $grupo);
        $stmt->execute();
        $importados++;
      }
    }

    fclose($handle);

    // Sessões específicas apenas para contatos
    if ($tabela === 'contatos') {
      $_SESSION['ativos'] = $db->querySingle("SELECT COUNT(*) FROM contatos WHERE status = 'ativo'");
      $_SESSION['inadimplentes'] = $db->querySingle("SELECT COUNT(*) FROM contatos WHERE status = 'inadimplente'");
      $_SESSION['importados'] = $importados;
      $_SESSION['atualizados'] = $atualizados;
    }else{
      $_SESSION['leads_importados'] = $importados;
      $_SESSION['leads_atualizados'] = $atualizados;
    }

    
    $_SESSION['mensagem'] = "Importação finalizada com sucesso!";
  } else {
    $_SESSION['mensagem'] = "Erro ao abrir o arquivo.";
  }
} else {
  $_SESSION['mensagem'] = "Envie um arquivo válido (.tsv).";
}

// Redireciona para a tela certa com base na tabela
$destino = $tabela === 'leads' ? 'upload_leads' : 'upload';
header("Location: ../painel&loc=$destino");
exit;
