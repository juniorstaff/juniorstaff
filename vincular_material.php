<?php
// Conexão com o banco de dados
include 'conexao.php';

// Buscar todos os técnicos
$sql_tecnicos = "SELECT id, nome FROM technicians ORDER BY nome";
$resultado_tecnicos = $conn->query($sql_tecnicos);

// Buscar todas as miscelâneas com saldo disponível
$sql_miscelaneas = "SELECT id, codigo, descricao, unidade, saldo FROM miscelaneas WHERE saldo > 0 ORDER BY descricao";
$resultado_miscelaneas = $conn->query($sql_miscelaneas);

// Verificar se o formulário foi enviado
if (isset($_POST['submit'])) {
    $tecnico_id = $_POST['tecnico_id'];
    $miscelanea_id = $_POST['miscelanea_id'];
    $quantidade = $_POST['quantidade'];
    $data_movimento = date('Y-m-d');
    
    // Verificar se a quantidade solicitada está disponível
    $sql_verificar = "SELECT saldo FROM miscelaneas WHERE id = '$miscelanea_id'";
    $result_verificar = $conn->query($sql_verificar);
    $row_verificar = $result_verificar->fetch_assoc();
    $saldo_disponivel = $row_verificar['saldo'];
    
    if ($quantidade > $saldo_disponivel) {
        $erro = "Quantidade solicitada ($quantidade) é maior que o saldo disponível ($saldo_disponivel)";
    } else {
        // Iniciar transação
        $conn->begin_transaction();
        
        try {
            // Registrar movimentação
            $sql_movimento = "INSERT INTO movimentacao_tecnico (tecnico_id, miscelanea_id, quantidade, data_movimento) 
                              VALUES ('$tecnico_id', '$miscelanea_id', '$quantidade', '$data_movimento')";
            $conn->query($sql_movimento);
            
            // Atualizar saldo da miscelânea
            $novo_saldo = $saldo_disponivel - $quantidade;
            $sql_atualizar = "UPDATE miscelaneas SET saldo = '$novo_saldo' WHERE id = '$miscelanea_id'";
            $conn->query($sql_atualizar);
            
            // Registrar na tabela de histórico
            $sql_historico = "INSERT INTO historico_movimentacoes (tipo_movimento, item_id, quantidade, usuario_id, data_movimento, destino_id, observacao) 
                             VALUES ('SAIDA', '$miscelanea_id', '$quantidade', '" . $_SESSION['usuario_id'] . "', '$data_movimento', '$tecnico_id', 'Material vinculado ao técnico')";
            $conn->query($sql_historico);
            
            // Confirmar transação
            $conn->commit();
            $mensagem = "Material vinculado ao técnico com sucesso!";
            
            // Recarregar dados das miscelâneas para atualizar o saldo
            $resultado_miscelaneas = $conn->query($sql_miscelaneas);
            
        } catch (Exception $e) {
            // Desfazer transação em caso de erro
            $conn->rollback();
            $erro = "Erro ao vincular material: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vincular Material ao Técnico</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-4">
        <h2>Vincular Material ao Técnico</h2>
        
        <?php if (isset($mensagem)): ?>
            <div class="alert alert-success"><?php echo $mensagem; ?></div>
        <?php endif; ?>
        
        <?php if (isset($erro)): ?>
            <div class="alert alert-danger"><?php echo $erro; ?></div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header">
                Vincular Material
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="tecnico_id" class="form-label">Técnico</label>
                            <select class="form-select" id="tecnico_id" name="tecnico_id" required>
                                <option value="">Selecione o Técnico</option>
                                <?php if ($resultado_tecnicos && $resultado_tecnicos->num_rows > 0): ?>
                                    <?php while ($tecnico = $resultado_tecnicos->fetch_assoc()): ?>
                                        <option value="<?php echo $tecnico['id']; ?>">
                                            <?php echo $tecnico['nome']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label for="miscelanea_id" class="form-label">Material</label>
                            <select class="form-select" id="miscelanea_id" name="miscelanea_id" required>
                                <option value="">Selecione o Material</option>
                                <?php if ($resultado_miscelaneas && $resultado_miscelaneas->num_rows > 0): ?>
                                    <?php while ($miscelanea = $resultado_miscelaneas->fetch_assoc()): ?>
                                        <option value="<?php echo $miscelanea['id']; ?>" data-saldo="<?php echo $miscelanea['saldo']; ?>">
                                            <?php echo $miscelanea['codigo'] . ' - ' . $miscelanea['descricao'] . ' (' . $miscelanea['unidade'] . ') - Saldo: ' . $miscelanea['saldo']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="quantidade" class="form-label">Quantidade</label>
                            <input type="number" class="form-control" id="quantidade" name="quantidade" min="1" step="1" required>
                            <small id="saldo-disponivel" class="form-text text-muted"></small>
                        </div>
                    </div>
                    <button type="submit" name="submit" class="btn btn-primary">Vincular Material</button>
                    <a href="saldos_tecnicos.php" class="btn btn-secondary">Voltar</a>
                </form>
            </div>
        </div>
        
        <!-- Histórico de Movimentações Recentes -->
        <div class="card">
            <div class="card-header">
                Movimentações Recentes
            </div>
            <div class="card-body">
                <?php
                $sql_historico = "SELECT h.id, h.tipo_movimento, m.codigo, m.descricao, h.quantidade, t.nome as tecnico, 
                                  h.data_movimento, h.observacao
                                  FROM historico_movimentacoes h
                                  JOIN miscelaneas m ON h.item_id = m.id
                                  JOIN technicians t ON h.destino_id = t.id
                                  WHERE h.tipo_movimento = 'SAIDA'
                                  ORDER BY h.data_movimento DESC
                                  LIMIT 10";
                $result_historico = $conn->query($sql_historico);
                ?>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Técnico</th>
                            <th>Material</th>
                            <th>Quantidade</th>
                            <th>Observação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result_historico && $result_historico->num_rows > 0): ?>
                            <?php while ($row = $result_historico->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($row['data_movimento'])); ?></td>
                                    <td><?php echo $row['tecnico']; ?></td>
                                    <td><?php echo $row['codigo'] . ' - ' . $row['descricao']; ?></td>
                                    <td><?php echo $row['quantidade']; ?></td>
                                    <td><?php echo $row['observacao']; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">Nenhum histórico encontrado</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script para exibir o saldo disponível e validar a quantidade
        document.getElementById('miscelanea_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const saldoDisponivel = selectedOption.getAttribute('data-saldo');
            
            if (saldoDisponivel) {
                document.getElementById('saldo-disponivel').textContent = 'Saldo disponível: ' + saldoDisponivel;
                document.getElementById('quantidade').max = saldoDisponivel;
            } else {
                document.getElementById('saldo-disponivel').textContent = '';
                document.getElementById('quantidade').removeAttribute('max');
            }
        });
    </script>
</body>
</html>