<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Coletar dados do formulário
    $id = $_POST['id'];
    $mac = $_POST['mac'];
    $equipamento_hfc = $_POST['equipamento_hfc'];
    $descricao = $_POST['descricao'];
    $tecn_origem = $_POST['tecn_origem'];
    $data_de_recebimento = $_POST['data_de_recebimento'];
    $nota_fiscal = $_POST['nota_fiscal'];
    $rma = $_POST['rma'];

    // Aqui você pode adicionar o código para salvar os dados no banco de dados
    // ou realizar outras ações com os dados do formulário.
    // Exemplo: Conectar ao banco de dados e inserir os dados

    // Conexão com o banco de dados (exemplo usando MySQLi)
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "sistema_servicos";

    // Criar conexão
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Checar conexão
    if ($conn->connect_error) {
        die("Falha na conexão: " . $conn->connect_error);
    }

    $sql = "INSERT INTO cadastro_onu (id, mac, equipamento_hfc, descricao, tecn_origem, data_de_recebimento, nota_fiscal, rma)
            VALUES ('$id', '$mac', '$equipamento_hfc', '$descricao', '$tecn_origem', '$data_de_recebimento', '$nota_fiscal', '$rma')";

    if ($conn->query($sql) === TRUE) {
        echo "Registro salvo com sucesso!";
    } else {
        echo "Erro: " . $sql . "<br>" . $conn->error;
    }

    $conn->close();
} else {
    echo "Método de requisição inválido.";
}
?>