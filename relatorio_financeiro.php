<?php

$host = "localhost";
$username = "root";
$password = "";
$database = "sistema_servicos";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

// --- FILTRO DE DATA ---
// Permite filtrar por dia, mês e ano (ou todos)
$dia = isset($_GET['dia']) && $_GET['dia'] !== '' ? intval($_GET['dia']) : '';
$mes = isset($_GET['mes']) && $_GET['mes'] !== '' ? intval($_GET['mes']) : '';
$ano = isset($_GET['ano']) && $_GET['ano'] !== '' ? intval($_GET['ano']) : date('Y');

$whereParts = [];
if ($ano) $whereParts[] = "YEAR(agendamento) = $ano";
if ($mes) $whereParts[] = "MONTH(agendamento) = $mes";
if ($dia) $whereParts[] = "DAY(agendamento) = $dia";
$whereDate = $whereParts ? "AND " . implode(" AND ", $whereParts) : "";

// Pega todos os serviços para referência de valor
$services = [];
$serviceResult = $conn->query("SELECT nome, valor FROM services");
if ($serviceResult && $serviceResult->num_rows > 0) {
    while ($row = $serviceResult->fetch_assoc()) {
        $services[$row['nome']] = (float)$row['valor'];
    }
}

// Consulta todos os contratos executados, trazendo técnico e serviço, filtrando por agendamento
$sql = "SELECT tecnico, servico FROM contracts WHERE executada = 1 $whereDate";
$result = $conn->query($sql);

$totalProduced = 0.00;
$saldoTotal = 0.00;
$service_count = [];
$tecnico_servico = []; // [tecnico][servico] = quantidade
$tecnico_total = [];   // [tecnico] = valor total

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $servico = $row['servico'];
        $tecnico = $row['tecnico'];
        $valor = isset($services[$servico]) ? $services[$servico] : 0;
        $totalProduced += $valor;
        $service_count[$servico] = ($service_count[$servico] ?? 0) + 1;

        if (!isset($tecnico_servico[$tecnico])) $tecnico_servico[$tecnico] = [];
        if (!isset($tecnico_servico[$tecnico][$servico])) $tecnico_servico[$tecnico][$servico] = 0;
        $tecnico_servico[$tecnico][$servico]++;
        $tecnico_total[$tecnico] = ($tecnico_total[$tecnico] ?? 0) + $valor;
    }
}
$saldoTotal = $totalProduced;

$all_tecnicos = array_keys($tecnico_servico);
$all_servicos = array_keys($services);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Financeiro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background: linear-gradient(120deg, #f8fafc 70%, #e0eafc 100%); }
        .dashboard-card {
            border: none;
            box-shadow: 0 2px 12px rgba(0,0,0,0.09);
            border-radius: 1.1rem;
        }
        .dashboard-icon {
            font-size: 2.4rem;
            margin-right: 1.2rem;
            opacity: 0.91;
        }
        .summary-value {
            font-size: 2.1rem;
            font-weight: 700;
            letter-spacing: 1px;
            color: #224776;
        }
        .summary-label {
            font-size: 1.15rem;
            font-weight: 500;
            color: #555;
            opacity: 0.88;
        }
        .service-summary {
            display: flex;
            flex-wrap: wrap;
            gap: 1.2rem;
            margin-bottom: 2rem;
        }
        .service-summary-card {
            background: linear-gradient(120deg, #e3f2fd 60%, #fff 100%);
            border: 1.5px solid #d1e9fc;
            border-radius: 0.93rem;
            min-width: 210px;
            padding: 1.1rem 1.4rem;
            box-shadow: 0 2px 8px rgba(30, 78, 121, 0.08);
            margin-bottom: 0.2rem;
            transition: transform 0.12s;
        }
        .service-summary-card:hover { transform: translateY(-3px) scale(1.03);}
        .service-summary-card h5 {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1565c0;
            margin-bottom: 0.5rem;
        }
        .service-summary-card p {
            margin-bottom: 0;
            color: #444;
        }
        .tecnico-summary {
            display: flex;
            flex-wrap: wrap;
            gap: 1.2rem;
            margin-bottom: 2rem;
        }
        .tecnico-summary-card {
            background: linear-gradient(120deg, #f3e5f5 60%, #fff 100%);
            border: 1.5px solid #e1bee7;
            border-radius: 0.93rem;
            min-width: 220px;
            padding: 1.1rem 1.4rem;
            box-shadow: 0 2px 8px rgba(142, 68, 173, 0.09);
            margin-bottom: 0.2rem;
            transition: transform 0.12s;
        }
        .tecnico-summary-card:hover { transform: translateY(-3px) scale(1.03);}
        .tecnico-summary-card h5 {
            font-size: 1.08rem;
            font-weight: 600;
            color: #8e44ad;
            margin-bottom: 0.5rem;
        }
        .tecnico-summary-card p {
            margin-bottom: 0;
            color: #444;
        }
        .main-title {
            font-size: 2.1rem;
            color: #1e4e79;
            margin-bottom: 2.1rem;
            font-weight: 700;
            letter-spacing: 1px;
            text-shadow: 0 2px 6px #c5e1f7;
        }
        .modern-table-wrapper {
            border-radius: 1rem;
            overflow-x: auto;
            background: #fff;
            box-shadow: 0 2px 16px rgba(52, 73, 94, 0.11);
            margin-bottom: 2.5rem;
            padding: 0.5rem 0.5rem 1.2rem 0.5rem;
        }
        .modern-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 1.05rem;
            min-width: 700px;
            background: #fff;
            border-radius: 1rem;
            overflow: hidden;
        }
        .modern-table th, .modern-table td {
            padding: 0.7rem 0.7rem;
            border-right: 1px solid #e0e7ef;
            border-bottom: 1px solid #e0e7ef;
            vertical-align: middle;
            word-break: break-word;
            max-width: 180px;
        }
        .modern-table th:last-child, .modern-table td:last-child {
            border-right: none;
        }
        .modern-table thead th {
            background: #e3f2fd;
            color: #1e4e79;
            font-weight: 700;
            position: sticky;
            top: 0;
            z-index: 2;
            border-top: none;
            border-bottom: 2px solid #90caf9;
            letter-spacing: 0.03em;
        }
        .modern-table tbody tr {
            transition: background 0.15s;
        }
        .modern-table tbody tr:hover {
            background: #f1f8ff;
        }
        .modern-table td {
            color: #333;
            font-weight: 500;
            text-align: right;
        }
        .modern-table td:first-child, .modern-table th:first-child {
            text-align: left;
            font-weight: 600;
            color: #8e44ad;
            background: #ede7f6;
            min-width: 120px;
            border-left: none;
        }
        .modern-table tfoot td, .modern-table tfoot th,
        .modern-table tbody tr.total-row td {
            background: #f9fbe7;
            font-weight: 700;
            color: #388e3c;
            font-size: 1.08em;
            border-top: 2px solid #c5e1a5;
        }
        .valor-rs {
            color: #1565c0;
            font-weight: 700;
            font-size: 1.03em;
        }
        .modern-table .zero {
            color: #bbb;
            font-weight: 400;
        }
        @media (max-width: 900px) {
            .modern-table th, .modern-table td {
                font-size: 0.98rem;
                padding: 0.57rem 0.5rem;
            }
            .modern-table {
                min-width: 600px;
            }
        }
        .nav-buttons {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            display: flex;
            gap: 10px;
        }
        .refresh-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            border-radius: 50px;
            padding: 15px 20px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(0,0,0,0.14);
        }
        .floating-menu {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
        }
        .dropdown-menu {
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        .auto-refresh-indicator {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(40, 167, 69, 0.9);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            z-index: 1000;
            display: none;
        }
        /* Filtro moderno */
        .filter-card {
            background: #e9ecef;
            border-radius: 0.9rem;
            box-shadow: 0 2px 8px rgba(44, 62, 80, 0.09);
            padding: 1.2rem 1.1rem 0.6rem 1.1rem;
            margin-bottom: 2rem;
        }
        .filter-card .form-label { font-weight: 500; color: #224776; }
    </style>
</head>
<body>
    <!-- Menu flutuante -->
    <div class="floating-menu">
        <div class="dropdown">
            <button class="btn btn-primary dropdown-toggle" type="button" id="menuDropdown" data-bs-toggle="dropdown">
                <i class="bi bi-list"></i> Menu
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="index.php"><i class="bi bi-house"></i> Sistema Principal</a></li>
                <li><a class="dropdown-item" href="#" onclick="toggleAutoRefresh()"><i class="bi bi-arrow-repeat"></i> <span id="autoRefreshText">Ativar Auto-Refresh</span></a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="export_excel.php?dia=<?php echo $dia; ?>&mes=<?php echo $mes; ?>&ano=<?php echo $ano; ?>"><i class="bi bi-file-earmark-excel"></i> Exportar Excel</a></li>
            </ul>
        </div>
    </div>

    <!-- Indicador de auto-refresh -->
    <div class="auto-refresh-indicator" id="refreshIndicator">
        <i class="bi bi-arrow-repeat"></i> Dados atualizados automaticamente
    </div>

    <!-- Botão de atualização flutuante -->
    <button class="btn btn-success refresh-btn" onclick="refreshData()" title="Atualizar dados">
        <i class="bi bi-arrow-clockwise"></i> Atualizar
    </button>

    <div class="container py-5">
        <div id="dashboard-content">
            <h1 class="main-title"><i class="bi bi-graph-up-arrow"></i> Dashboard Financeiro</h1>

            <!-- FILTRO DE DATA -->
            <form class="filter-card" method="get" id="filtro-data-form">
                <div class="row g-2 align-items-end">
                    <div class="col-md-2 col-6">
                        <label class="form-label">Dia</label>
                        <select class="form-select" name="dia">
                            <option value="">Todos</option>
                            <?php for ($d = 1; $d <= 31; $d++): ?>
                                <option value="<?php echo $d; ?>" <?php if ($dia == $d) echo 'selected'; ?>><?php echo $d; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-2 col-6">
                        <label class="form-label">Mês</label>
                        <select class="form-select" name="mes">
                            <option value="">Todos</option>
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?php echo $m; ?>" <?php if ($mes == $m) echo 'selected'; ?>><?php echo $m; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-2 col-6">
                        <label class="form-label">Ano</label>
                        <select class="form-select" name="ano">
                            <?php for ($a = date('Y')-2; $a <= date('Y')+2; $a++): ?>
                                <option value="<?php echo $a; ?>" <?php if ($ano == $a) echo 'selected'; ?>><?php echo $a; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-2 col-12">
                        <button class="btn btn-primary w-100" type="submit"><i class="bi bi-search"></i> Filtrar</button>
                    </div>
                </div>
            </form>

            <div class="row g-4 mb-4">
                <div class="col-md-4 col-12">
                    <div class="card dashboard-card text-bg-primary">
                        <div class="card-body d-flex align-items-center">
                            <span class="dashboard-icon"><i class="bi bi-cash-coin"></i></span>
                            <div>
                                <div class="summary-label mb-1">Valor Produzido</div>
                                <div class="summary-value">R$ <?php echo number_format($totalProduced, 2, ',', '.'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-12">
                    <div class="card dashboard-card text-bg-dark">
                        <div class="card-body d-flex align-items-center">
                            <span class="dashboard-icon"><i class="bi bi-bank"></i></span>
                            <div>
                                <div class="summary-label mb-1">Saldo Total</div>
                                <div class="summary-value text-light">R$ <?php echo number_format($saldoTotal, 2, ',', '.'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quadros resumo dos serviços -->
            <h4 class="mb-3 mt-5"><i class="bi bi-box-seam"></i> Resumo por Serviço Executado</h4>
            <div class="service-summary">
                <?php foreach ($services as $service => $valor): ?>
                    <?php $qtd = $service_count[$service] ?? 0; ?>
                    <?php if ($qtd > 0): ?>
                        <div class="service-summary-card">
                            <h5><?php echo htmlspecialchars($service); ?></h5>
                            <p>
                                <b>Qtd:</b> <?php echo $qtd; ?><br>
                                <b>Unitário:</b> R$ <?php echo number_format($valor, 2, ',', '.'); ?><br>
                                <b>Total:</b> <span style="color:#1565c0">R$ <?php echo number_format($valor * $qtd, 2, ',', '.'); ?></span>
                            </p>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <!-- Quadros resumo dos técnicos -->
            <h4 class="mb-3 mt-5"><i class="bi bi-person-badge"></i> Resumo por Técnico</h4>
            <div class="tecnico-summary">
                <?php foreach ($tecnico_servico as $tecnico => $servicos): ?>
                    <?php $tecnicoTotal = $tecnico_total[$tecnico] ?? 0; ?>
                    <div class="tecnico-summary-card">
                        <h5><?php echo htmlspecialchars($tecnico ?: '(Sem técnico)'); ?></h5>
                        <p>
                            <b>Total produzido:</b> <span style="color:#8e44ad">R$ <?php echo number_format($tecnicoTotal, 2, ',', '.'); ?></span><br>
                            <b>Serviços:</b>
                            <ul style="margin-bottom:0.1rem;">
                                <?php foreach ($servicos as $servico => $qtd): ?>
                                    <?php if ($qtd > 0): ?>
                                        <li>
                                            <?php echo htmlspecialchars($servico); ?>: <?php echo $qtd; ?> x R$ <?php echo number_format($services[$servico], 2, ',', '.'); ?> = <b>R$ <?php echo number_format($services[$servico]*$qtd, 2, ',', '.'); ?></b>
                                        </li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Tabela dinâmica: Técnico x Serviços -->
            <h4 class="mb-3 mt-5"><i class="bi bi-table"></i> Relação Técnico x Serviços Executados</h4>
            <div class="d-flex justify-content-end mb-2">
                <a href="export_excel.php?dia=<?php echo $dia; ?>&mes=<?php echo $mes; ?>&ano=<?php echo $ano; ?>" class="btn btn-success btn-sm">
                    <i class="bi bi-file-earmark-excel"></i> Extrair em Excel
                </a>
            </div>
            <div class="modern-table-wrapper">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Técnico</th>
                            <?php foreach ($all_servicos as $service): ?>
                                <th><?php echo htmlspecialchars($service); ?></th>
                            <?php endforeach; ?>
                            <th>Total Técnico (R$)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_tecnicos as $tecnico): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($tecnico ?: '(Sem técnico)'); ?></td>
                                <?php foreach ($all_servicos as $service): ?>
                                    <?php
                                    $qtd = $tecnico_servico[$tecnico][$service] ?? 0;
                                    $valor = $services[$service] ?? 0;
                                    ?>
                                    <td>
                                        <?php if ($qtd > 0): ?>
                                            <?php echo $qtd; ?><br>
                                            <span class="valor-rs">R$ <?php echo number_format($qtd * $valor, 2, ',', '.'); ?></span>
                                        <?php else: ?>
                                            <span class="zero">-</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                                <td><span class="valor-rs">R$ <?php echo number_format($tecnico_total[$tecnico], 2, ',', '.'); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="total-row">
                            <td>Total por Serviço</td>
                            <?php foreach ($all_servicos as $service): ?>
                                <td>
                                    <?php $qtd = $service_count[$service] ?? 0; ?>
                                    <?php if ($qtd > 0): ?>
                                        <?php echo $qtd; ?><br>
                                        <span class="valor-rs">R$ <?php echo number_format($qtd * $services[$service], 2, ',', '.'); ?></span>
                                    <?php else: ?>
                                        <span class="zero">-</span>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                            <td><span class="valor-rs">R$ <?php echo number_format($totalProduced, 2, ',', '.'); ?></span></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
    <script>
        let autoRefreshInterval = null;
        let isAutoRefreshActive = false;

        function refreshData() {
            // Mostra indicador de carregamento
            const refreshBtn = document.querySelector('.refresh-btn');
            const originalContent = refreshBtn.innerHTML;
            refreshBtn.innerHTML = '<i class="bi bi-arrow-repeat" style="animation: spin 1s linear infinite;"></i> Atualizando...';
            refreshBtn.disabled = true;

            // Recarrega a página para buscar dados atualizados
            setTimeout(() => {
                location.reload();
            }, 500);
        }

        function toggleAutoRefresh() {
            const autoRefreshText = document.getElementById('autoRefreshText');
            const indicator = document.getElementById('refreshIndicator');
            if (isAutoRefreshActive) {
                clearInterval(autoRefreshInterval);
                autoRefreshInterval = null;
                isAutoRefreshActive = false;
                autoRefreshText.textContent = 'Ativar Auto-Refresh';
                indicator.style.display = 'none';
            } else {
                autoRefreshInterval = setInterval(() => {
                    indicator.style.display = 'block';
                    setTimeout(() => {
                        indicator.style.display = 'none';
                    }, 2000);
                    refreshData();
                }, 30000);
                isAutoRefreshActive = true;
                autoRefreshText.textContent = 'Desativar Auto-Refresh';
            }
        }

        // Animação de rotação para o ícone
        const style = document.createElement('style');
        style.textContent = `
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(style);

        // Timestamp na última atualização
        const now = new Date();
        const timestamp = document.createElement('div');
        timestamp.style.cssText = 'position: fixed; bottom: 80px; right: 20px; background: rgba(0,0,0,0.7); color: white; padding: 5px 10px; border-radius: 15px; font-size: 0.8rem; z-index: 1000;';
        timestamp.textContent = `Última atualização: ${now.toLocaleTimeString()}`;
        document.body.appendChild(timestamp);
    </script>
</body>
</html>