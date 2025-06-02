<?php
require_once 'db.php';

header('Content-Type: application/json');

if (isset($_GET['term'])) {
    $term = $_GET['term'] . '%';

    try {
        $stmt = $pdo->prepare("SELECT mac FROM cadastro_onu WHERE mac LIKE ? ORDER BY mac ASC");
        $stmt->execute([$term]);
        $macs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(array_column($macs, 'mac'));
    } catch (PDOException $e) {
        // Retornar uma mensagem de erro em JSON para facilitar a depuração
        echo json_encode(['error' => "Erro ao buscar MACs: " . $e->getMessage()]);
    }
} else {
    // Retornar uma mensagem de erro se o parâmetro 'term' não for fornecido
    echo json_encode(['error' => "Parâmetro 'term' não fornecido."]);
}
?>