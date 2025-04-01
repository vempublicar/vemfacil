<?php
session_start();
// Dados da sua instância
if($_GET['pg'] == 'painel'){
    include 'app/painel.php';
}else{
    include 'app/login.php';
}