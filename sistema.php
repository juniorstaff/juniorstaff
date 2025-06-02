<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit;
}

$usuario = $_SESSION['usuario'];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Sistema</title>
</head>
<body>
    <h1>Bem-vindo, <?php echo htmlspecialchars($usuario); ?>!</h1>
    <p><a href="logout.php">Sair</a></p>
    <!-- Aqui você coloca o conteúdo do seu sistema -->
</body>
</html>
