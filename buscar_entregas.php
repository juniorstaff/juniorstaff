<?php
require_once 'db.php';

header('Content-Type: application/json');

if (isset($_GET['term'])) {
    $term = trim($_GET['term']);

    if ($pdo) {
        try {
            $query = "
                SELECT em.equipamento_mac, em.descricao_tecnico, em.tecnicos, em.data_rec
                FROM entregas_materiais em
                LEFT JOIN cadastro_onu eo ON em.equipamento_mac = eo.equipamento_mac
                WHERE em.equipamento_mac LIKE :term
                ORDER BY em.data_rec DESC
            ";
            $stmt = $pdo->prepare($query);
            $stmt->execute([':term' => "%$term%"]);
            $entregas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($entregas);
        } catch (PDOException $e) {
            echo json_encode(['error' => "Erro ao buscar entregas: " . $e->getMessage()]);
        }
    } else {
        echo json_encode(['error' => "Erro ao conectar ao banco de dados."]);
    }
} else {
    echo json_encode(['error' => "Parâmetro 'term' não fornecido."]);
}
?>