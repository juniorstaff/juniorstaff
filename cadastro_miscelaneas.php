<?php
// Conexão com o banco de dados
include 'conexao.php';

// Verificar se o formulário foi enviado
if (isset($_POST['submit'])) {
    $codigo = $_POST['codigo'];
    $descricao = $_POST['descricao'];
    $unidade = $_POST['unidade'];
    $quantidade = $_POST['quantidade'];
    $saldo = $_POST['quantidade']; // Saldo inicial igual à quantidade
    $rma = $_POST['rma'];

    // Verifica se já existe o código informado
    $check = $conn->prepare("SELECT id FROM miscelaneas WHERE codigo = ?");
    $check->bind_param("s", $codigo);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $erro = "Já existe uma miscelânea cadastrada com o código informado.";
    } else {
        // Inserir dados na tabela de miscelâneas
        $sql = "INSERT INTO miscelaneas (codigo, descricao, unidade, quantidade, saldo, rma) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssiss", $codigo, $descricao, $unidade, $quantidade, $saldo, $rma);

        if ($stmt->execute()) {
            $mensagem = "Miscelânea cadastrada com sucesso!";
        } else {
            $erro = "Erro ao cadastrar: " . $conn->error;
        }
    }
    $check->close();
}

// Buscar todas as miscelâneas cadastradas
$sql = "SELECT * FROM miscelaneas ORDER BY codigo";
$resultado = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Miscelâneas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-4">
        <h2>Cadastro de Miscelâneas</h2>
        
        <?php if (isset($mensagem)): ?>
            <div class="alert alert-success"><?php echo $mensagem; ?></div>
        <?php endif; ?>
        
        <?php if (isset($erro)): ?>
            <div class="alert alert-danger"><?php echo $erro; ?></div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header">
                Novo Cadastro
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row mb-3">
                        <div class="col-md-2">
                            <label for="codigo" class="form-label">Código</label>
                            <input type="text" class="form-control" id="codigo" name="codigo" required>
                        </div>
                        <div class="col-md-5">
                            <label for="descricao" class="form-label">Descrição</label>
                            <input type="text" class="form-control" id="descricao" name="descricao" required>
                        </div>
                        <div class="col-md-2">
                            <label for="unidade" class="form-label">Unidade</label>
                            <input type="text" class="form-control" id="unidade" name="unidade" required>
                        </div>
                        <div class="col-md-1">
                            <label for="quantidade" class="form-label">Quantidade</label>
                            <input type="number" class="form-control" id="quantidade" name="quantidade" min="0" step="1" required>
                        </div>
                        <div class="col-md-2">
                            <label for="rma" class="form-label">RMA</label>
                            <input type="text" class="form-control" id="rma" name="rma">
                        </div>
                    </div>
                    <button type="submit" name="submit" class="btn btn-primary">Cadastrar</button>
                    <a href="index.php" class="btn btn-secondary ms-2">Voltar ao Início</a>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                Miscelâneas Cadastradas
            </div>
            <div class="card-body">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Descrição</th>
                            <th>Unidade</th>
                            <th>Quantidade</th>
                            <th>Saldo</th>
                            <th>RMA</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                   <tbody>
    <?php if ($resultado && $resultado->num_rows > 0): ?>
        <?php while ($row = $resultado->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['codigo'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($row['descricao'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($row['unidade'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($row['quantidade'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($row['saldo'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($row['rma'] ?? ''); ?></td>
                <td>
                    <a href="editar_miscelanea.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">Editar</a>
                    <a href="excluir_miscelanea.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja excluir?')">Excluir</a>
                </td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr>
            <td colspan="7" class="text-center">Nenhuma miscelânea cadastrada</td>
        </tr>
    <?php endif; ?>
</tbody>
                </table>
                <a href="index.php" class="btn btn-secondary mt-2">Voltar ao Início</a>
            </div>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>