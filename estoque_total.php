<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
session_start();
require_once 'db.php';

if (!isset($_SESSION['usuario']) || empty($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

// ONUs: cada linha de cadastro_onu com mac ainda NÃO instalado (não está em contracts)
$onusEstoque = [];
try {
    $stmtOnus = $conn->query("
        SELECT co.mac, co.descricao, co.nota_fiscal, co.data_recebimento, co.rma
        FROM sistema_servicos.cadastro_onu co
        WHERE NOT EXISTS (
            SELECT 1 FROM sistema_servicos.contracts c
            WHERE c.onu = co.mac AND c.onu IS NOT NULL AND c.onu <> ''
        )
        ORDER BY co.descricao, co.mac
    ");
    $onusEstoque = $stmtOnus->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $onusEstoque = [];
}

// Agrupar ONUs por descrição
$onusAgrupadas = [];
$totalONUs = 0;
foreach ($onusEstoque as $onu) {
    $descricao = trim($onu['descricao']) ?: 'Sem descrição';
    if (!isset($onusAgrupadas[$descricao])) {
        $onusAgrupadas[$descricao] = [
            'quantidade' => 0,
            'onus' => []
        ];
    }
    $onusAgrupadas[$descricao]['quantidade']++;
    $onusAgrupadas[$descricao]['onus'][] = $onu;
    $totalONUs++;
}

// Miscelâneas em estoque (saldo > 0)
$miscelaneasEstoque = [];
try {
    $stmtMisc = $conn->query("
        SELECT codigo, descricao, unidade, quantidade, saldo, data_cadastro, rma
        FROM sistema_servicos.miscelaneas
        WHERE saldo > 0
        ORDER BY descricao
    ");
    $miscelaneasEstoque = $stmtMisc->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $miscelaneasEstoque = [];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Estoque Total Atual</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f6f8fa 0%, #e9eef2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #2c3e50;
            margin: 0;
            min-height: 100vh;
        }
        .container {
            margin: 32px auto 0 auto;
            max-width: 1400px;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 8px 28px rgba(44, 62, 80, 0.09);
            padding: 32px 24px;
        }
        h1 {
            margin-bottom: 24px;
            font-size: 2rem;
            font-weight: 700;
            color: #3498db;
            text-align: center;
        }
        .btn-voltar {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: linear-gradient(90deg, #e67e22, #3498db);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            padding: 8px 20px;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.2s;
            box-shadow: 0 2px 10px rgba(44, 62, 80, 0.06);
            margin-bottom: 18px;
        }
        .btn-voltar:hover {
            background: linear-gradient(90deg, #3498db, #e67e22);
            color: #fff;
            text-decoration: none;
        }
        h2 {
            font-size: 1.3rem;
            color: #222;
            margin-top: 30px;
            margin-bottom: 10px;
        }
        .table-responsive {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
            background: #f8fafc;
        }
        th, td {
            padding: 8px 11px;
            text-align: left;
            border-bottom: 1.5px solid #e5e7eb;
            font-size: 1rem;
        }
        th {
            background: #16a085;
            color: #fff;
            font-weight: 700;
        }
        tr:nth-child(even) {
            background: #ecf5fa;
        }
        .badge-warning { 
            background: #f39c12; 
            color: #fff; 
            border-radius: 7px; 
            padding:2px 8px;
            font-size: 0.8rem;
        }
        
        /* Estilos para resumo de ONUs */
        .onu-summary {
            background: #e8f6f3;
            border: 1px solid #16a085;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .onu-summary h3 {
            color: #16a085;
            margin-bottom: 15px;
            font-size: 1.2rem;
        }
        .onu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        .onu-card {
            background: #fff;
            border: 1px solid #b3d9d3;
            border-radius: 6px;
            padding: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .onu-card h4 {
            color: #2980b9;
            margin: 0 0 8px 0;
            font-size: 1rem;
        }
        .onu-card .quantidade {
            font-size: 1.3rem;
            font-weight: bold;
            color: #27ae60;
        }
        
        /* Tabela detalhada colapsável */
        .collapse-section {
            margin-top: 20px;
        }
        .toggle-btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        .toggle-btn:hover {
            background: #2980b9;
        }
        .collapsible-content {
            display: none;
        }
        .collapsible-content.show {
            display: block;
        }
        
        /* Destacar linha da descrição */
        .descricao-row {
            background: #d5e8e2 !important;
            font-weight: bold;
            color: #16a085;
        }
        .descricao-row td {
            border-top: 2px solid #16a085;
        }
        
        .total-box {
            background: #2c3e50;
            color: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
            font-size: 1.1rem;
            font-weight: bold;
        }
        
        @media (max-width: 700px) {
            .container { padding: 12px 2px; }
            th, td { font-size: 0.93rem; }
            h1 { font-size: 1.3rem; }
            .onu-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="btn-voltar"><i class="fas fa-arrow-left"></i> Voltar ao Início</a>
        <h1><i class="fas fa-warehouse"></i> Estoque Total Atual</h1>

        <h2><i class="fas fa-network-wired"></i> ONUs em Estoque</h2>
        
        <!-- Resumo por Descrição -->
        <div class="onu-summary">
            <h3><i class="fas fa-chart-bar"></i> Resumo por Tipo de ONU</h3>
            <div class="onu-grid">
                <?php foreach ($onusAgrupadas as $descricao => $dados): ?>
                    <div class="onu-card">
                        <h4><?= htmlspecialchars($descricao) ?></h4>
                        <div class="quantidade"><?= $dados['quantidade'] ?> unidade(s)</div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="total-box">
                <i class="fas fa-calculator"></i> Total Geral de ONUs: <?= $totalONUs ?>
            </div>
        </div>

        <!-- Botão para mostrar/ocultar detalhes -->
        <div class="collapse-section">
            <button class="toggle-btn" onclick="toggleDetails()">
                <i class="fas fa-list" id="toggle-icon"></i> 
                <span id="toggle-text">Mostrar Detalhes por MAC</span>
            </button>
            
            <div class="collapsible-content" id="details-content">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Descrição/MAC ONU</th>
                                <th>Nota Fiscal</th>
                                <th>Data Recebimento</th>
                                <th>RMA</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (count($onusEstoque) === 0): ?>
                            <tr>
                                <td colspan="5" style="text-align:center;color:#e74c3c;">Nenhuma ONU disponível em estoque.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($onusAgrupadas as $descricao => $dados): ?>
                                <!-- Linha da descrição -->
                                <tr class="descricao-row">
                                    <td colspan="5">
                                        <i class="fas fa-tag"></i> 
                                        <?= htmlspecialchars($descricao) ?> 
                                        <span style="font-size: 0.9rem;">(<?= $dados['quantidade'] ?> unidades)</span>
                                    </td>
                                </tr>
                                <!-- Linhas das ONUs desta descrição -->
                                <?php foreach ($dados['onus'] as $onu): ?>
                                    <tr>
                                        <td style="padding-left: 25px;">
                                            <i class="fas fa-wifi" style="color: #3498db;"></i> 
                                            <?= htmlspecialchars($onu['mac']) ?>
                                        </td>
                                        <td><?= htmlspecialchars($onu['nota_fiscal'] ?? '-') ?></td>
                                        <td><?= $onu['data_recebimento'] ? date('d/m/Y', strtotime($onu['data_recebimento'])) : '-' ?></td>
                                        <td><?= htmlspecialchars($onu['rma'] ?? '-') ?></td>
                                        <td><span style="color: #27ae60; font-weight: bold;">Em Estoque</span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <h2><i class="fas fa-boxes-stacked"></i> Miscelâneas em Estoque</h2>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Descrição</th>
                        <th>Unidade</th>
                        <th>Quantidade Inicial</th>
                        <th>Saldo Atual</th>
                        <th>Data Cadastro</th>
                        <th>RMA</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (count($miscelaneasEstoque) === 0): ?>
                    <tr>
                        <td colspan="7" style="text-align:center;color:#e74c3c;">Nenhum item de miscelânea em estoque.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($miscelaneasEstoque as $misc): ?>
                        <tr>
                            <td><?= htmlspecialchars($misc['codigo']) ?></td>
                            <td><?= htmlspecialchars($misc['descricao']) ?></td>
                            <td><?= htmlspecialchars($misc['unidade']) ?></td>
                            <td><?= (int)$misc['quantidade'] ?></td>
                            <td>
                                <?= (int)$misc['saldo'] ?>
                                <?php if ($misc['saldo'] < 10): ?>
                                    <span class="badge-warning">Baixo</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $misc['data_cadastro'] ? date('d/m/Y', strtotime($misc['data_cadastro'])) : '-' ?></td>
                            <td><?= htmlspecialchars($misc['rma']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function toggleDetails() {
            const content = document.getElementById('details-content');
            const icon = document.getElementById('toggle-icon');
            const text = document.getElementById('toggle-text');
            
            if (content.classList.contains('show')) {
                content.classList.remove('show');
                icon.className = 'fas fa-list';
                text.textContent = 'Mostrar Detalhes por MAC';
            } else {
                content.classList.add('show');
                icon.className = 'fas fa-eye-slash';
                text.textContent = 'Ocultar Detalhes por MAC';
            }
        }
    </script>
</body>
</html>