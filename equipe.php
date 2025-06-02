<?php
include 'db.php';
session_start();
if (!isset($_SESSION['usuario'])) {
  header("Location: login.php");
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nome = $_POST['nome'];
  $responsavel = $_POST['responsavel'];
  $stmt = $pdo->prepare("INSERT INTO equipes (nome, responsavel) VALUES (?, ?)");
  $stmt->execute([$nome, $responsavel]);
  echo "Equipe cadastrada com sucesso!";
}
?>
<form method="POST">
  Nome da Equipe: <input type="text" name="nome" required><br>
  Responsável: <input type="text" name="responsavel" required><br>
  <button type="submit">Cadastrar</button>
</form>
<a href="dashboard.php">Voltar</a>
