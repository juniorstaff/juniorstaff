<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "sistema_servicos";

    try {
        // Criar conexão usando PDO
        $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Coletar dados do POST
        $equipamento_mac = $_POST['mac'] ?? null;
        $equipamento_hfc = $_POST['hfc'] ?? null;
        $descricao = $_POST['descricao'] ?? null;
        $data_entrega = $_POST['data_entrega'] ?? null;
        $tecnico = $_POST['tecnico'] ?? null;
        $quantidade = $_POST['quantidade'] ?? null;
        $tecn_origem = $_POST['tecn_origem'] ?? null;
        $data_recebimento = $_POST['data_recebimento'] ?? null;
        $nota_fiscal = $_POST['nota_fiscal'] ?? null;
        $rma = $_POST['rma'] ?? null;

        // Verificar se os campos obrigatórios estão preenchidos
        if (empty($equipamento_mac) || empty($equipamento_hfc) || empty($descricao) || empty($data_entrega) || empty($tecnico)) {
            die("Erro: Todos os campos obrigatórios devem ser preenchidos.");
        }

        // Preparar a declaração SQL
        $sql = "INSERT INTO cadastro_onu (mac, hfc, descricao, data_entrega, tecnico, quantidade, tecn_origem, data_recebimento, nota_fiscal, rma) 
                VALUES (:mac, :hfc, :descricao, :data_entrega, :tecnico, :quantidade, :tecn_origem, :data_recebimento, :nota_fiscal, :rma)";
        
        $stmt = $pdo->prepare($sql);

        // Vincular parâmetros
        $stmt->bindParam(':mac', $equipamento_mac);
        $stmt->bindParam(':hfc', $equipamento_hfc);
        $stmt->bindParam(':descricao', $descricao);
        $stmt->bindParam(':data_entrega', $data_entrega);
        $stmt->bindParam(':tecnico', $tecnico);
        $stmt->bindParam(':quantidade', $quantidade);
        $stmt->bindParam(':tecn_origem', $tecn_origem);
        $stmt->bindParam(':data_recebimento', $data_recebimento);
        $stmt->bindParam(':nota_fiscal', $nota_fiscal);
        $stmt->bindParam(':rma', $rma);

        // Executar a declaração
        $stmt->execute();

        echo "Novo registro criado com sucesso";
    } catch (PDOException $e) {
        echo "Erro: " . $e->getMessage();
    }

    // Fechar a conexão
    $pdo = null;
}
?>