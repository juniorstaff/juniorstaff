<?php
include 'conexao.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: cadastro_miscelaneas.php");
    exit;
}

$id = intval($_GET['id']);

// Busca os dados atuais
$sql = "SELECT * FROM miscelaneas WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows != 1) {
    header("Location: cadastro_miscelaneas.php");
    exit;
}

$miscelanea = $resultado->fetch_assoc();
$stmt->close();

if (isset($_POST['submit'])) {
    $codigo = $_POST['codigo'];
    $descricao = $_POST['descricao'];
    $unidade = $_POST['unidade'];
    $quantidade = $_POST['quantidade'];
    $saldo = $_POST['saldo'];
    $rma = $_POST['rma'];

    $sqlUpdate = "UPDATE miscelaneas SET 
        codigo = ?, 
        descricao = ?, 
        unidade = ?, 
        quantidade = ?, 
        saldo = ?, 
        rma = ?
        WHERE id = ?";

    $stmt = $conn->prepare($sqlUpdate);
    $stmt->bind_param("sssissi", $codigo, $descricao, $unidade, $quantidade, $saldo, $rma, $id);

    if ($stmt->execute()) {
        $mensagem = "Miscelânea atualizada com sucesso!";
        // Atualiza os dados para exibir o novo valor no formulário
        $sql = "SELECT * FROM miscelaneas WHERE id = ?";
        $stmt2 = $conn->prepare($sql);
        $stmt2->bind_param("i", $id);
        $stmt2->execute();
        $resultado = $stmt2->get_result();
        $miscelanea = $resultado->fetch_assoc();
        $stmt2->close();
    } else {
        $erro = "Erro ao atualizar: " . $conn->error;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Miscelânea</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-4">
        <h2>Editar Miscelânea</h2>

        <?php if (isset($mensagem)): ?>
            <div class="alert alert-success"><?php echo $mensagem; ?></div>
        <?php endif; ?>

        <?php if (isset($erro)): ?>
            <div class="alert alert-danger"><?php echo $erro; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                Alterar Dados
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row mb-3">
                        <div class="col-md-2">
                            <label for="codigo" class="form-label">Código</label>
                            <input type="text" class="form-control" id="codigo" name="codigo" value="<?php echo htmlspecialchars($miscelanea['codigo'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-5">
                            <label for="descricao" class="form-label">Descrição</label>
                            <input type="text" class="form-control" id="descricao" name="descricao" value="<?php echo htmlspecialchars($miscelanea['descricao'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-2">
                            <label for="unidade" class="form-label">Unidade</label>
                            <input type="text" class="form-control" id="unidade" name="unidade" value="<?php echo htmlspecialchars($miscelanea['unidade'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-1">
                            <label for="quantidade" class="form-label">Quantidade</label>
                            <input type="number" class="form-control" id="quantidade" name="quantidade" value="<?php echo htmlspecialchars($miscelanea['quantidade'] ?? ''); ?>" min="0" step="1" required>
                        </div>
                        <div class="col-md-1">
                            <label for="saldo" class="form-label">Saldo</label>
                            <input type="number" class="form-control" id="saldo" name="saldo" value="<?php echo htmlspecialchars($miscelanea['saldo'] ?? ''); ?>" min="0" step="1" required>
                        </div>
                        <div class="col-md-1">
                            <label for="rma" class="form-label">RMA</label>
                            <input type="text" class="form-control" id="rma" name="rma" value="<?php echo htmlspecialchars($miscelanea['rma'] ?? ''); ?>">
                        </div>
                    </div>
                    <button type="submit" name="submit" class="btn btn-primary">Salvar Alterações</button>
                    <a href="cadastro_miscelaneas.php" class="btn btn-secondary ms-2">Voltar</a>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>