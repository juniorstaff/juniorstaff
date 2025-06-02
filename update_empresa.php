<?php
require_once 'db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contrato_id'], $_POST['nome_empresa'])) {
    $id = (int)$_POST['contrato_id'];
    $nome_empresa = trim($_POST['nome_empresa']);
    if ($id > 0 && $nome_empresa !== '') {
        $stmt = $conn->prepare("UPDATE contratos SET nome_empresa=? WHERE id=?");
        $stmt->execute([$nome_empresa, $id]);
    }
}
header('Location: index.php');
exit;
?>