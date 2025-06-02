<?php
// Inclui arquivo de conexão com o banco de dados
include 'conexao.php';

// Verifica se o banco de dados existe, caso contrário cria
$sql = "CREATE DATABASE IF NOT EXISTS service_management";
if ($conn->query($sql) === TRUE) {
    echo "Banco de dados verificado ou criado com sucesso.<br>";
} else {
    echo "Erro ao criar banco de dados: " . $conn->error . "<br>";
    exit;
}

// Seleciona o banco de dados
$conn->select_db("service_management");

// Leitura do arquivo SQL
$sql_file = file_get_contents('script_banco_dados.sql');

// Divisão do arquivo SQL em comandos individuais
$queries = explode(';', $sql_file);

// Execução de cada comando SQL
$success = true;
foreach ($queries as $query) {
    $query = trim($query);
    if (empty($query)) continue;
    
    if ($conn->query($query) === FALSE) {
        echo "Erro ao executar o comando: " . $conn->error . "<br>";
        echo "Comando SQL problemático: " . $query . "<br><br>";
        $success = false;
    }
}

if ($success) {
    echo "Tabelas criadas com sucesso!<br>";
    
    // Verificar se já existem técnicos cadastrados
    $result = $conn->query("SELECT COUNT(*) as total FROM technicians");
    $row = $result->fetch_assoc();
    
    if ($row['total'] == 0) {
        // Inserir alguns técnicos para teste
        $sql_tecnicos = "INSERT INTO technicians (nome, email, telefone) VALUES 
            ('João Silva', 'joao@exemplo.com', '(11) 98765-4321'),
            ('Maria Oliveira', 'maria@exemplo.com', '(11) 91234-5678'),
            ('Carlos Santos', 'carlos@exemplo.com', '(11) 99876-5432')";
        
        if ($conn->query($sql_tecnicos) === TRUE) {
            echo "Técnicos de teste adicionados com sucesso.<br>";
        } else {
            echo "Erro ao adicionar técnicos: " . $conn->error . "<br>";
        }
    }
    
    // Verificar se arquivo de configuração precisa ser atualizado
    $config_file = 'conexao.php';
    $config_content = file_get_contents($config_file);
    
    if (strpos($config_content, 'gestao_equipamentos') !== false) {
        $updated_content = str_replace('gestao_equipamentos', 'service_management', $config_content);
        if (file_put_contents($config_file, $updated_content)) {
            echo "Arquivo de configuração atualizado automaticamente.<br>";
        } else {
            echo "Aviso: Não foi possível atualizar automaticamente o arquivo de configuração. Por favor, verifique o nome do banco de dados no arquivo conexao.php.<br>";
        }
    }
}

echo "<br><a href='cadastro_miscelaneas.php' class='btn btn-primary'>Ir para o Sistema</a>";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Instalação do Sistema</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <style>
        body {
            padding: 20px;
            font-family: Arial, sans-serif;
        }
        
        pre {
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="mt-4 mb-4">Assistente de Instalação do Sistema</h2>
        
        <div class="alert alert-info">
            <p><strong>Instruções:</strong></p>
            <p>1. Este script verifica ou cria o banco de dados e as tabelas necessárias.</p>
            <p>2. Verifique as mensagens acima para garantir que tudo foi instalado corretamente.</p>
            <p>3. Se houver erros, verifique as permissões do MySQL e as configurações no arquivo conexao.php.</p>
        </div>
        
        <div class="mt-4">
            <h4>Próximos passos:</h4>
            <ol>
                <li>Acesse a página de cadastro de miscelâneas.</li>
                <li>Cadastre os materiais necessários.</li>
                <li>Use a página de vinculação para associar materiais aos técnicos.</li>
                <li>Visualize os saldos por técnico na página correspondente.</li>
            </ol>
        </div>
    </div>
</body>
</html>