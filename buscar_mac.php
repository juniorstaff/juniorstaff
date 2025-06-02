<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

if (empty($_POST['macs'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Nenhum MAC informado']);
    exit;
}

// Pegando valores do POST ou definindo padrão
$hfc = isset($_POST['hfc']) ? $_POST['hfc'] : 'HFC padrão';
$descricao_padrao = isset($_POST['descricao']) ? $_POST['descricao'] : 'Sem registro no sistema';
$data_entrega_padrao = isset($_POST['data_entrega']) ? $_POST['data_entrega'] : date('Y-m-d');
$nota_fiscal_padrao = isset($_POST['nota_fiscal']) ? $_POST['nota_fiscal'] : null;
$rma_padrao = isset($_POST['rma']) ? $_POST['rma'] : null;

try {
    $pdo = new PDO($dsn, $username, $password, $options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $macsRaw = trim($_POST['macs']);
    $macs = explode("\n", $macsRaw);
    $macs = array_map('trim', $macs);
    $macs = array_filter($macs);
    
    $resultados = [];
    
    foreach ($macs as $mac) {
        $mac = preg_replace('/[^A-Fa-f0-9:]/', '', $mac);
        if (empty($mac)) continue;
        
        $stmt = $pdo->prepare("SELECT * FROM cadastro_onu WHERE mac = ?");
        $stmt->execute([$mac]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($item) {
            $resultados[] = $item;
        } else {
            $insert = $pdo->prepare("INSERT INTO cadastro_onu (mac, hfc, descricao, data_entrega, nota_fiscal, rma) VALUES (?, ?, ?, ?, ?, ?)");
            $insert->execute([
                $mac,
                $hfc,
                $descricao_padrao,
                $data_entrega_padrao,
                $nota_fiscal_padrao,
                $rma_padrao
            ]);
            
            $stmt2 = $pdo->prepare("SELECT * FROM cadastro_onu WHERE mac = ?");
            $stmt2->execute([$mac]);
            $item_inserido = $stmt2->fetch(PDO::FETCH_ASSOC);

            $resultados[] = $item_inserido;
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($resultados);
    
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Erro de banco de dados: ' . $e->getMessage()]);
    exit;
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}