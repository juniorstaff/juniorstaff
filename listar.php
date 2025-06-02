<?php
require 'conexao.php';

$result = $conn->query("SELECT * FROM equipamentos ORDER BY id DESC");

$dados = [];

while ($row = $result->fetch_assoc()) {
    $dados[] = $row;
}

echo json_encode($dados);
?>
