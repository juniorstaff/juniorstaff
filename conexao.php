<?php
// Conexão com MySQL - Altere os valores conforme seu ambiente
$servername = "localhost";
$username = "root";  // Altere para seu usuário do MySQL
$password = "";      // Altere para sua senha do MySQL
$dbname = "sistema_servicos";  // Nome do banco que vai ser criado no instalar.php

// Criar conexão sem especificar o banco de dados inicialmente
$conn = new mysqli($servername, $username, $password);

// Verificar conexão
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// Verificar se o banco existe e selecionar
$result = $conn->query("SHOW DATABASES LIKE '$dbname'");
if ($result->num_rows > 0) {
    // Se o banco existe, seleciona ele
    $conn->select_db($dbname);
}

// Configurar charset para UTF-8
$conn->set_charset("utf8mb4");

// Iniciar sessão para controle de usuário
session_start();

// Verificar se o usuário está logado (implemente conforme sua lógica de autenticação)
if (!isset($_SESSION['usuario_id']) && !strpos($_SERVER['PHP_SELF'], 'login.php') && !strpos($_SERVER['PHP_SELF'], 'instalar.php')) {
    // Configuração para desenvolvimento - remova ou altere para produção
    // Simular um usuário logado para teste
    $_SESSION['usuario_id'] = 1;
    $_SESSION['usuario_nome'] = 'Administrador';
    
    // Descomente esta linha para redirecionar para o login em ambiente de produção
    // header("Location: login.php");
    // exit;
}
?>