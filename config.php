<?php
// Iniciar sessão se ainda não estiver iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Configurações de conexão com o banco de dados
$host = "localhost";
$username = "root";
$password = "";
$database = "sistema_servicos";

// Criar conexão
$conn = new mysqli($host, $username, $password, $database);

// Verificar conexão
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// Definir charset para UTF-8
$conn->set_charset("utf8mb4");

// Função para proteger contra SQL Injection
function sanitize($conn, $data) {
    return $conn->real_escape_string($data);
}

// Função para verificar se o usuário está logado
function verificarLogin() {
    if (!isset($_SESSION['usuario_id'])) {
        header("Location: login.php");
        exit;
    }
}

// Função para verificar se o usuário é admin
function verificarAdmin() {
    if (!isset($_SESSION['nivel_acesso']) || $_SESSION['nivel_acesso'] != 'admin') {
        header("Location: dashboard.php?erro=acesso_negado");
        exit;
    }
}
?>