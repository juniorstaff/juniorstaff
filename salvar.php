<?php
require 'conexao.php';

$macs = explode("\n", trim($_POST['mac']));
$hfcs = explode("\n", trim($_POST['hfc']));
$descricao = $_POST['descricao'];
$tecnico = $_POST['tecn_origem'];
$data = $_POST['data_recebimento'] ?? null;
$rma = $_POST['rma'];

if (count($macs) !== count($hfcs)) {
    echo json_encode(['status' => 'error', 'message' => 'Quantidade de MACs e HFCs não coincide.']);
    exit;
}

foreach ($macs as $i => $mac) {
    $mac = trim($mac);
    $hfc = trim($hfcs[$i]);

    if ($mac && $hfc) {
        $stmt = $conn->prepare("INSERT INTO equipamentos (mac, hfc, descricao, tecnico_origem, data_recebimento, rma) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $mac, $hfc, $descricao, $tecnico, $data, $rma);
        $stmt->execute();
    }
}

echo json_encode(['status' => 'success', 'message' => 'Equipamentos salvos com sucesso.']);
?>
