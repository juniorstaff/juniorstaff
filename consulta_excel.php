<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
session_start();
require_once 'db.php';

// Checa autenticação
if (!isset($_SESSION['usuario']) || empty($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

// Parâmetros de filtro
$limite = isset($_GET['limite']) && in_array((int)$_GET['limite'], [10, 100]) ? (int)$_GET['limite'] : 10;
$filtro_mac = isset($_GET['mac']) ? trim($_GET['mac']) : '';

// Consulta SQL igual à da tela
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
LEFT JOIN service_management.contracts AS c 
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
    die('Erro ao consultar dados: ' . htmlspecialchars($e->getMessage()));
}

// Cabeçalho para download do Excel/CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="consulta_instalacoes_' . date('Ymd_His') . '.csv"');

// Abre o output
$output = fopen('php://output', 'w');

// Cabeçalho das colunas
fputcsv($output, [
    'MAC Instalado',
    'Descrição',
    'Técnico Entrega',
    'Data Entrega',
    'Status Entrega',
    'Contrato Nº',
    'Cliente',
    'Técnico Contrato',
    'Serviço',
    'Status Contrato',
    'Repasse',
    'Bairro',
    'Cadastro',
    'Agendamento',
    'Executada',
    'Metragem',
    'ONU (Contrato)',
    'Obs Entrega',
    'Obs Contrato',
    'Status Cruzamento'
]);

// Linha dos dados
foreach ($registros as $r) {
    // Status cruzamento
    if ($r['onu_contrato'] && $r['onu_contrato'] === $r['mac']) {
        $status_cruzamento = 'OK';
    } elseif ($r['onu_contrato']) {
        $status_cruzamento = 'Pertence a outro';
    } else {
        $status_cruzamento = 'Não encontrado';
    }

    fputcsv($output, [
        $r['mac'],
        $r['descricao'],
        $r['tecnico_entrega'],
        $r['data_entrega'] ? date('d/m/Y', strtotime($r['data_entrega'])) : '',
        $r['status_entrega'],
        $r['numero_contrato'],
        $r['nome_cliente'],
        $r['tecnico_contrato'],
        $r['servico'],
        $r['status_contrato'],
        $r['repasse'],
        $r['bairro'],
        $r['cadastro'] ? date('d/m/Y', strtotime($r['cadastro'])) : '',
        $r['agendamento'] ? date('d/m/Y', strtotime($r['agendamento'])) : '',
        $r['executada'],
        $r['metragem'],
        $r['onu_contrato'],
        $r['obs_entrega'],
        $r['obs_contrato'],
        $status_cruzamento
    ]);
}
fclose($output);
exit;