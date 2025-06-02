<?php
// Configuração da conexão com o banco de dados
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sistema_servicos";

// Criar conexão
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexão
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

echo "<h2>Criação da tabela 'equipe' no banco de dados 'sistema_servicos'</h2>";

// Criar a tabela equipe se não existir
$sql_criar_tabela = "CREATE TABLE IF NOT EXISTS equipe (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    funcao VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    telefone VARCHAR(20),
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql_criar_tabela) === TRUE) {
    echo "<div style='color: green; padding: 15px; background-color: #f0fff0; border: 1px solid green; margin: 20px 0;'>
            Tabela 'equipe' criada com sucesso (ou já existe).
          </div>";
    
    echo "<p>Estrutura da tabela criada:</p>";
    echo "<ul>
            <li><strong>id</strong>: INT(11) AUTO_INCREMENT PRIMARY KEY</li>
            <li><strong>nome</strong>: VARCHAR(100) NOT NULL</li>
            <li><strong>funcao</strong>: VARCHAR(100) NOT NULL</li>
            <li><strong>email</strong>: VARCHAR(100) UNIQUE</li>
            <li><strong>telefone</strong>: VARCHAR(20)</li>
            <li><strong>data_cadastro</strong>: TIMESTAMP DEFAULT CURRENT_TIMESTAMP</li>
          </ul>";
} else {
    echo "<div style='color: red; padding: 15px; background-color: #fff0f0; border: 1px solid red; margin: 20px 0;'>
            Erro ao criar tabela: " . $conn->error . "
          </div>";
}

echo "<p><a href='equipe.php' style='background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>
        Ir para o Sistema de Cadastro de Equipe
      </a></p>";

// Fechar conexão
$conn->close();
?>