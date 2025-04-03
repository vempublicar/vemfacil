<?php
session_start();
// Dados da sua instância
if($_GET['pg'] == 'webhook'){
    include 'app/pages/webhook.php';
}elseif($_GET['pg'] == 'carregar_mensagens'){
        include 'app/pages/carregar_mensagens.php';
}elseif($_GET['pg'] == 'painel'){
    if(isset($_SESSION['email'])){
        include 'app/painel.php';
    }else{
        include 'app/login.php';        
    }
}else{
    include 'app/login.php';
}