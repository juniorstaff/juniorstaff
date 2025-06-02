<?php
require_once 'db.php';

header('Content-Type: application/json');

if (isset($_GET['term'])) {
    $term = trim($_GET['term']);

    if ($pdo) {
        try {
            $query = "
                SELECT mac
                FROM cadastro_onu
                WHERE mac LIKE :term
                ORDER BY mac ASC
            ";
            $stmt = $pdo->prepare($query);
            $stmt->execute([':term' => "%$term%"]);
            $onus = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($onus);
        } catch (PDOException $e) {
            echo json_encode(['error' => "Erro ao buscar ONUs: " . $e->getMessage()]);
        }
    } else {
        echo json_encode(['error' => "Erro ao conectar ao banco de dados."]);
    }
} else {
    echo json_encode(['error' => "Parâmetro 'term' não fornecido."]);
}
?>