<?php
// Conexão com o banco de dados
include 'conexao.php';

// Buscar todos os técnicos
$sql_tecnicos = "SELECT id, nome FROM technicians ORDER BY nome";
$resultado_tecnicos = $conn->query($sql_tecnicos);

// Filtrar por técnico específico se solicitado
$filtro_tecnico = isset($_GET['tecnico']) ? $_GET['tecnico'] : '';

// Consulta para buscar os materiais vinculados aos técnicos
$sql = "SELECT m.codigo, m.descricao, m.unidade, mt.quantidade, t.nome as tecnico, mt.data_movimento
        FROM movimentacao_tecnico mt
        JOIN miscelaneas m ON mt.miscelanea_id = m.id
        JOIN technicians t ON mt.tecnico_id = t.id";

// Adicionar filtro por técnico se especificado
if (!empty($filtro_tecnico)) {
    $sql .= " WHERE mt.tecnico_id = '$filtro_tecnico'";
}

$sql .= " ORDER BY t.nome, m.codigo";
$resultado = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saldos por Técnico</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-4">
        <h2>Materiais Vinculados aos Técnicos</h2>
        
        <div class="card mb-4">
            <div class="card-header">
                Filtros
            </div>
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-4">
                        <label for="tecnico" class="form-label">Filtrar por Técnico</label>
                        <select class="form-select" id="tecnico" name="tecnico">
                            <option value="">Todos os Técnicos</option>
                            <?php if ($resultado_tecnicos && $resultado_tecnicos->num_rows > 0): ?>
                                <?php while ($tecnico = $resultado_tecnicos->fetch_assoc()): ?>
                                    <option value="<?php echo $tecnico['id']; ?>" <?php echo ($filtro_tecnico == $tecnico['id']) ? 'selected' : ''; ?>>
                                        <?php echo $tecnico['nome']; ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">Filtrar</button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                Materiais por Técnico
            </div>
            <div class="card-body">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Técnico</th>
                            <th>Código</th>
                            <th>Descrição</th>
                            <th>Unidade</th>
                            <th>Quantidade</th>
                            <th>Data de Movimentação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($resultado && $resultado->num_rows > 0): ?>
                            <?php 
                            $tecnico_atual = '';
                            $total_por_tecnico = [];
                            
                            while ($row = $resultado->fetch_assoc()): 
                                // Inicializar contagem para novo técnico
                                if (!isset($total_por_tecnico[$row['tecnico']])) {
                                    $total_por_tecnico[$row['tecnico']] = 0;
                                }
                                
                                // Incrementar contagem
                                $total_por_tecnico[$row['tecnico']] += $row['quantidade'];
                                
                                // Verificar se mudou o técnico para inserir cabeçalho
                                if ($tecnico_atual != $row['tecnico']) {
                                    $tecnico_atual = $row['tecnico'];
                                    echo '<tr class="table-secondary"><td colspan="6"><strong>' . $tecnico_atual . '</strong></td></tr>';
                                }
                            ?>
                                <tr>
                                    <td></td> <!-- Célula vazia pois já temos o cabeçalho do técnico -->
                                    <td><?php echo $row['codigo']; ?></td>
                                    <td><?php echo $row['descricao']; ?></td>
                                    <td><?php echo $row['unidade']; ?></td>
                                    <td><?php echo $row['quantidade']; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($row['data_movimento'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                            
                            <!-- Exibir totais por técnico -->
                            <?php foreach ($total_por_tecnico as $tecnico => $total): ?>
                                <tr class="table-info">
                                    <td><strong><?php echo $tecnico; ?></strong></td>
                                    <td colspan="3" class="text-end"><strong>Total de itens:</strong></td>
                                    <td><strong><?php echo $total; ?></strong></td>
                                    <td></td>
                                </tr>
                            <?php endforeach; ?>
                            
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">Nenhum material encontrado</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="mt-3">
            <a href="cadastro_miscelaneas.php" class="btn btn-secondary">Voltar para Cadastro</a>
            <a href="vincular_material.php" class="btn btn-success">Vincular Material a Técnico</a>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>