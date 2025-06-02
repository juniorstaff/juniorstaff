<?php
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="relatorio_tecnico_servicos_' . date('Y-m-d') . '.xls"');
header('Cache-Control: max-age=0');

$host = "localhost";
$username = "root";
$password = "";
$database = "sistema_servicos";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

// Pega todos os serviços para referência de valor
$services = [];
$serviceResult = $conn->query("SELECT nome, valor FROM services");
if ($serviceResult && $serviceResult->num_rows > 0) {
    while ($row = $serviceResult->fetch_assoc()) {
        $services[$row['nome']] = (float)$row['valor'];
    }
}

// Consulta todos os contratos executados, trazendo técnico e serviço
$sql = "SELECT tecnico, servico FROM contracts WHERE executada = 1";
$result = $conn->query($sql);

$totalProduced = 0.00;
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

// Monta lista de todos técnicos e serviços presentes para tabela dinâmica
$all_tecnicos = array_keys($tecnico_servico);
$all_servicos = array_keys($services);

// Início da tabela
echo "<table border='1' style='border-collapse:collapse'>";
echo "<tr style='background:#f3e5f5'>";
echo "<th>Técnico</th>";
foreach ($all_servicos as $service) {
    echo "<th>" . htmlspecialchars($service) . "</th>";
}
echo "<th>Total Técnico (R$)</th>";
echo "</tr>";

// Linhas por técnico
foreach ($all_tecnicos as $tecnico) {
    echo "<tr>";
    echo "<td><b>" . htmlspecialchars($tecnico ?: '(Sem técnico)') . "</b></td>";
    foreach ($all_servicos as $service) {
        $qtd = $tecnico_servico[$tecnico][$service] ?? 0;
        $valor = $services[$service] ?? 0;
        if ($qtd > 0) {
            echo "<td>$qtd<br>R$ " . number_format($qtd*$valor, 2, ',', '.') . "</td>";
        } else {
            echo "<td style='color:#bbb;'>-</td>";
        }
    }
    echo "<td><b>R$ " . number_format($tecnico_total[$tecnico], 2, ',', '.') . "</b></td>";
    echo "</tr>";
}

// Linha de totais por serviço
echo "<tr style='background:#f9fbe7'>";
echo "<td><b>Total por Serviço</b></td>";
foreach ($all_servicos as $service) {
    $qtd = $service_count[$service] ?? 0;
    if ($qtd > 0) {
        echo "<td><b>$qtd</b><br>R$ " . number_format($qtd*$services[$service], 2, ',', '.') . "</td>";
    } else {
        echo "<td style='color:#bbb;'>-</td>";
    }
}
echo "<td><b>R$ " . number_format($totalProduced, 2, ',', '.') . "</b></td>";
echo "</tr>";

echo "</table>";
exit;
?>