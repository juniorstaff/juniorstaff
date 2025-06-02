<?php
header('Content-Type: application/json');

// Simulação de dados
$stats = [
    'usuarios' => 532,
    'entregas_pendentes' => 178,
    'taxa_conclusao' => 87,
    'contratos_ativos' => 35,
    'notificacoes' => 12,
    'modulos' => [
        'onu' => ['pendentes' => 3, 'progresso' => 80],
        'equipe' => ['pendentes' => 0, 'progresso' => 65],
        'entregas' => ['pendentes' => 7, 'progresso' => 90],
        'contratos' => ['pendentes' => 2, 'progresso' => 30]
    ]
];

// Se você tiver um banco de dados, pode buscar os dados aqui
// Exemplo:
// $usuarios = $conn->query("SELECT COUNT(*) as total FROM usuarios")->fetch_assoc()['total'];
// $stats['usuarios'] = $usuarios;

echo json_encode($stats);
?>
