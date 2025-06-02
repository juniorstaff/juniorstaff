<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
session_start();
require_once 'db.php'; // sua conexão deve permitir acesso aos dois bancos

// Checa autenticação
if (!isset($_SESSION['usuario']) || empty($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

// Limite de exibição
$limite = isset($_GET['limite']) && in_array((int)$_GET['limite'], [10, 100]) ? (int)$_GET['limite'] : 10;
$filtro_mac = isset($_GET['mac']) ? trim($_GET['mac']) : '';

// Consulta SQL cruzando sistemas diferentes e ajustada para os campos reais
$sql = "
SELECT 
    em.mac,
    em.tecnico AS tecnico_entrega,
    em.data_entrega,
    em.descricao,
    em.status AS status_entrega,
    em.observacao AS obs_entrega,
    c.contrato AS numero_contrato,
    c.nome AS nome_cliente,
    c.onu AS onu_contrato,
    c.tecnico AS tecnico_contrato,
    c.status AS status_contrato,
    c.servico,
    c.repasse,
    c.bairro,
    c.cadastro,
    c.agendamento,
    c.executada,
    c.metragem,
    c.observacao AS obs_contrato
FROM sistema_servicos.entregas_materiais AS em
LEFT JOIN sistema_servicos.contracts AS c 
    ON CONVERT(em.mac USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(c.onu USING utf8mb4) COLLATE utf8mb4_unicode_ci
WHERE 1=1
";
$params = [];

if ($filtro_mac !== '') {
    $sql .= " AND em.mac LIKE ?";
    $params[] = "%$filtro_mac%";
}

$sql .= " ORDER BY em.data_entrega DESC LIMIT $limite";

// Executa consulta
try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('<div style="color:red;padding:20px;">Erro ao consultar dados: ' . htmlspecialchars($e->getMessage()) . '</div>');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Consulta de Instalações</title>
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
            max-width: 1200px;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 8px 28px rgba(44, 62, 80, 0.09);
            padding: 32px 24px;
        }
        .header-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-bottom: 20px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: linear-gradient(90deg, #2ecc71, #3498db);
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
        }
        .btn:hover {
            background: linear-gradient(90deg, #3498db, #2ecc71);
            color: #fff;
            text-decoration: none;
        }
        h1 {
            margin-bottom: 24px;
            font-size: 2rem;
            font-weight: 700;
            color: #3498db;
            text-align: center;
        }
        form {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            align-items: center;
            justify-content: center;
            margin-bottom: 30px;
        }
        .input-group {
            display: flex;
            flex-direction: column;
        }
        label {
            font-size: 0.98rem;
            margin-bottom: 4px;
            color: #34495e;
        }
        input[type="text"], select {
            padding: 8px 10px;
            border: 1.5px solid #dce1e6;
            border-radius: 8px;
            font-size: 1rem;
            outline: none;
            transition: border 0.2s;
        }
        input[type="text"]:focus, select:focus {
            border-color: #3498db;
        }
        button[type="submit"] {
            background: linear-gradient(90deg, #2ecc71, #3498db);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 11px 30px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            box-shadow: 0 2px 10px rgba(44, 62, 80, 0.06);
            margin-top: 22px;
            margin-left: 10px;
        }
        button[type="submit"]:hover {
            background: linear-gradient(90deg, #3498db, #2ecc71);
        }
        .table-responsive {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
            background: #f8fafc;
        }
        th, td {
            padding: 10px 12px;
            text-align: left;
            border-bottom: 1.5px solid #e5e7eb;
            font-size: 1rem;
        }
        th {
            background: #3498db;
            color: #fff;
            font-weight: 700;
        }
        tr:nth-child(even) {
            background: #ecf5fa;
        }
        .info-icon {
            color: #16a085;
            margin-right: 4px;
        }
        .badge {
            display: inline-block;
            font-size: 0.92rem;
            padding: 2px 10px;
            border-radius: 6px;
            color: #fff;
            margin-left: 5px;
        }
        .badge-ok { background: #2ecc71; }
        .badge-fail { background: #e74c3c; }
        .badge-partial { background: #f39c12; }
        @media (max-width: 700px) {
            .container { padding: 12px 2px; }
            th, td { font-size: 0.93rem; }
            h1 { font-size: 1.3rem; }
            .header-actions { flex-direction: column; align-items: stretch; }
        }
        .obs-cell {
            max-width: 210px;
            white-space: pre-line;
            word-break: break-word;
            font-size: 0.93em;
            color: #888;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-actions">
            <a href="index.php" class="btn"><i class="fas fa-arrow-left"></i> Voltar ao Início</a>
            <a href="consulta_excel.php?mac=<?= urlencode($filtro_mac) ?>&limite=<?= $limite ?>" class="btn" target="_blank"><i class="fas fa-file-excel"></i> Extrair Excel</a>
        </div>
        <h1><i class="fas fa-search"></i> Consulta Instalações, Contratos e Materiais</h1>
        <form method="GET">
            <div class="input-group">
                <label for="mac"><i class="fas fa-network-wired info-icon"></i>MAC instalado</label>
                <input type="text" name="mac" id="mac" value="<?= htmlspecialchars($filtro_mac) ?>" placeholder="Digite parte do MAC">
            </div>
            <div class="input-group">
                <label for="limite"><i class="fas fa-list-ol info-icon"></i>Exibir</label>
                <select name="limite" id="limite">
                    <option value="10"<?= $limite == 10 ? ' selected' : '' ?>>Até 10</option>
                    <option value="100"<?= $limite == 100 ? ' selected' : '' ?>>Até 100</option>
                </select>
            </div>
            <button type="submit"><i class="fas fa-search"></i> Consultar</button>
        </form>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>MAC Instalado</th>
                        <th>Descrição</th>
                        <th>Técnico Entrega</th>
                        <th>Data Entrega</th>
                        <th>Status Entrega</th>
                        <th>Contrato Nº</th>
                        <th>Cliente</th>
                        <th>Técnico Contrato</th>
                        <th>Serviço</th>
                        <th>Status Contrato</th>
                        <th>Repasse</th>
                        <th>Bairro</th>
                        <th>Cadastro</th>
                        <th>Agendamento</th>
                        <th>Executada</th>
                        <th>Metragem</th>
                        <th>ONU (Contrato)</th>
                        <th>Obs Entrega</th>
                        <th>Obs Contrato</th>
                        <th>Status Cruzamento</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (count($registros) === 0): ?>
                    <tr>
                        <td colspan="20" style="text-align:center;color:#e74c3c;">Nenhum registro encontrado!</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($registros as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['mac']) ?></td>
                            <td><?= htmlspecialchars($r['descricao'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($r['tecnico_entrega'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($r['data_entrega'] ? date('d/m/Y', strtotime($r['data_entrega'])) : '-') ?></td>
                            <td><?= htmlspecialchars($r['status_entrega'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($r['numero_contrato'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($r['nome_cliente'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($r['tecnico_contrato'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($r['servico'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($r['status_contrato'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($r['repasse'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($r['bairro'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($r['cadastro'] ? date('d/m/Y', strtotime($r['cadastro'])) : '-') ?></td>
                            <td><?= htmlspecialchars($r['agendamento'] ? date('d/m/Y', strtotime($r['agendamento'])) : '-') ?></td>
                            <td><?= htmlspecialchars($r['executada'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($r['metragem'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($r['onu_contrato'] ?? '-') ?></td>
                            <td class="obs-cell"><?= htmlspecialchars($r['obs_entrega'] ?? '-') ?></td>
                            <td class="obs-cell"><?= htmlspecialchars($r['obs_contrato'] ?? '-') ?></td>
                            <td>
                                <?php if ($r['onu_contrato'] && $r['onu_contrato'] === $r['mac']): ?>
                                    <span class="badge badge-ok"><i class="fas fa-check-circle"></i>OK</span>
                                <?php elseif ($r['onu_contrato']): ?>
                                    <span class="badge badge-partial"><i class="fas fa-check"></i>Pertence a outro</span>
                                <?php else: ?>
                                    <span class="badge badge-fail"><i class="fas fa-times-circle"></i>Não encontrado</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>