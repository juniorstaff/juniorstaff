<?php
require 'conexao.php';

$id = $_POST['id'];
$mac = trim($_POST['mac']);
$hfc = trim($_POST['hfc']);
$descricao = $_POST['descricao'];
$tecnico = $_POST['tecnico_origem'];
$data = $_POST['data_recebimento'];
$rma = $_POST['rma'];

$stmt = $conn->prepare("UPDATE equipamentos SET mac=?, hfc=?, descricao=?, tecnico_origem=?, data_recebimento=?, rma=? WHERE id=?");
$stmt->bind_param("ssssssi", $mac, $hfc, $descricao, $tecnico, $data, $rma, $id);
$stmt->execute();

echo json_encode(['status' => 'success']);
?>
