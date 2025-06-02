<?php
$senha = 'senha123'; // Senha em texto plano
$hash = password_hash($senha, PASSWORD_DEFAULT); // Gerar hash da senha

// Exemplo de como inserir um usuário no banco de dados
try {
    $pdo = new PDO('mysql:host=localhost;dbname=sistema_servicos', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("INSERT INTO usuarios (usuario, senha) VALUES (?, ?)");
    $stmt->execute(['admin', $hash]);

    echo "Usuário inserido com sucesso!";
} catch (PDOException $e) {
    echo "Erro ao inserir usuário: " . $e->getMessage();
}
?>