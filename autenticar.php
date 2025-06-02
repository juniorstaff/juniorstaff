<?php
session_start();

// Simular dados de usuários para demonstração (em um cenário real, acessar banco de dados)
$usuarios = [
    'admin' => 'admin123',
    'usuario' => 'senha123'
];

// Obter dados do formulário
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

// Verificar credenciais
if (isset($usuarios[$username]) && $usuarios[$username] === $password) {
    // Credenciais válidas, criar sessão
    $_SESSION['username'] = $username;
    header('Location: index.html');
    exit;
} else {
    // Credenciais inválidas, redirecionar para login com mensagem de erro
    $_SESSION['erro_login'] = 'Usuário ou senha inválidos';
    header('Location: login.html');
    exit;
}
?>