<?php
require 'conexao.php';

$id = $_POST['id'];

$stmt = $conn->prepare("DELETE FROM equipamentos WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

echo json_encode(['status' => 'success']);
?>
