<?php
// =================== CONFIGURAÇÕES DO BANCO ===================
$host = "localhost";
$username = "root";
$password = "";
$database = "sistema_servicos";

// =================== CONEXÃO E CRIAÇÃO DO BANCO ===================
$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// =================== ALTERAÇÃO DO BANCO: repasse vira DATE ===================
$conn->query("ALTER TABLE contracts CHANGE repasse repasse DATE NULL");

// =================== CRIAÇÃO DAS TABELAS SE NÃO EXISTEM ===================
$tables = [
    "contracts" => "CREATE TABLE IF NOT EXISTS contracts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        contrato VARCHAR(50) NOT NULL,
        nome VARCHAR(100) NOT NULL,
        cadastro DATE,
        agendamento DATETIME,
        repasse DATE,
        status VARCHAR(50),
        bairro VARCHAR(100),
        servico VARCHAR(100),
        tecnico VARCHAR(100),
        executada BOOLEAN DEFAULT 0,
        onu VARCHAR(50),
        observacao TEXT,
        codigos_miscelanea TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    "technicians" => "CREATE TABLE IF NOT EXISTS technicians (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "services" => "CREATE TABLE IF NOT EXISTS services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(100) NOT NULL,
        valor DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "neighborhoods" => "CREATE TABLE IF NOT EXISTS neighborhoods (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "miscelaneas" => "CREATE TABLE IF NOT EXISTS miscelaneas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        codigo VARCHAR(100) NOT NULL,
        descricao VARCHAR(100),
        quantidade FLOAT DEFAULT 0
    )"
];
foreach ($tables as $sql) {
    $conn->query($sql);
}

// =================== FUNÇÃO DE SANITIZAÇÃO ===================
function sanitize($conn, $data) {
    return $conn->real_escape_string(htmlspecialchars(trim($data)));
}

// =================== AJAX: Busca descrição da miscelânea ===================
if ($_SERVER["REQUEST_METHOD"] == "GET" && ($_GET["action"] ?? '') == "get_miscelanea_descricao" && isset($_GET["codigo"])) {
    $codigo = sanitize($conn, $_GET["codigo"]);
    $r = $conn->query("SELECT descricao FROM miscelaneas WHERE codigo='$codigo' LIMIT 1");
    echo $r && $r->num_rows > 0 ? $r->fetch_assoc()['descricao'] : '';
    exit;
}

// =================== AJAX: Busca valor do serviço ===================
if ($_SERVER["REQUEST_METHOD"] == "GET" && ($_GET["action"] ?? '') == "get_service_value" && isset($_GET["service_name"])) {
    $serviceName = sanitize($conn, $_GET["service_name"]);
    $result = $conn->query("SELECT valor FROM services WHERE nome = '$serviceName'");
    echo $result && $result->num_rows > 0 ? $result->fetch_assoc()['valor'] : "0.00";
    exit;
}

// =================== PROCESSAMENTO DE FORMULÁRIOS ===================
// Adicionar técnico
if ($_SERVER["REQUEST_METHOD"] == "POST" && ($_POST["action"] ?? '') == "add_technician") {
    $nome = sanitize($conn, $_POST["technician_name"]);
    $conn->query("INSERT INTO technicians (nome) VALUES ('$nome')");
}
// Excluir técnico
if ($_SERVER["REQUEST_METHOD"] == "GET" && ($_GET["action"] ?? '') == "delete_technician" && isset($_GET["id"])) {
    $id = sanitize($conn, $_GET["id"]);
    $conn->query("DELETE FROM technicians WHERE id = $id");
}
// Adicionar serviço
if ($_SERVER["REQUEST_METHOD"] == "POST" && ($_POST["action"] ?? '') == "add_service") {
    $nome = sanitize($conn, $_POST["service_name"]);
    $valor = sanitize($conn, $_POST["service_value"]);
    $conn->query("INSERT INTO services (nome, valor) VALUES ('$nome', '$valor')");
}
// Excluir serviço
if ($_SERVER["REQUEST_METHOD"] == "GET" && ($_GET["action"] ?? '') == "delete_service" && isset($_GET["id"])) {
    $id = sanitize($conn, $_GET["id"]);
    $conn->query("DELETE FROM services WHERE id = $id");
}
// Adicionar bairro
if ($_SERVER["REQUEST_METHOD"] == "POST" && ($_POST["action"] ?? '') == "add_neighborhood") {
    $nome = sanitize($conn, $_POST["neighborhood_name"]);
    $conn->query("INSERT INTO neighborhoods (nome) VALUES ('$nome')");
}
// Excluir bairro
if ($_SERVER["REQUEST_METHOD"] == "GET" && ($_GET["action"] ?? '') == "delete_neighborhood" && isset($_GET["id"])) {
    $id = sanitize($conn, $_GET["id"]);
    $conn->query("DELETE FROM neighborhoods WHERE id = $id");
}

// =================== CONTRATO: ADICIONAR E ATUALIZAR ===================
if ($_SERVER["REQUEST_METHOD"] == "POST" && ($_POST["action"] ?? '') == "add") {
    $contrato = sanitize($conn, $_POST["contrato"]);
    $nome = sanitize($conn, $_POST["nome"]);
    $cadastro = sanitize($conn, $_POST["cadastro"]);
    $agendamento = sanitize($conn, $_POST["agendamento"]);
    $repasse = sanitize($conn, $_POST["repasse"]); // YYYY-MM-DD
    $status = sanitize($conn, $_POST["status"]);
    $bairro = sanitize($conn, $_POST["bairro"]);
    $servico = sanitize($conn, $_POST["servico"]);
    $tecnico = sanitize($conn, $_POST["tecnico"]);
    $executada = isset($_POST["executada"]) ? 1 : 0;
    $onu = sanitize($conn, $_POST["onu"]);
    $observacao = sanitize($conn, $_POST["observacao"]);

    // Miscelâneas (tabela dinâmica)
    $codigos = $_POST['codigos_miscelanea'] ?? [];
    $descricoes = $_POST['descricoes_miscelanea'] ?? [];
    $quantidades = $_POST['quantidades_miscelanea'] ?? [];
    $pares = [];
    foreach ($codigos as $i => $codigo) {
        $codigo = sanitize($conn, $codigo);
        $descricao = isset($descricoes[$i]) ? sanitize($conn, $descricoes[$i]) : '';
        $quantidade = isset($quantidades[$i]) ? floatval($quantidades[$i]) : 0;
        if ($codigo && $quantidade > 0) {
            // Atualiza o estoque
            $stmt = $conn->prepare("SELECT id, quantidade FROM miscelaneas WHERE codigo = ? LIMIT 1");
            $stmt->bind_param("s", $codigo);
            $stmt->execute();
            $resultado = $stmt->get_result();
            if ($resultado && $row = $resultado->fetch_assoc()) {
                $nova_quantidade = max(0, floatval($row['quantidade']) - $quantidade);
                $upd = $conn->prepare("UPDATE miscelaneas SET quantidade = ? WHERE id = ?");
                $upd->bind_param("di", $nova_quantidade, $row['id']);
                $upd->execute();
                $upd->close();
            }
            $stmt->close();
            $pares[] = "$codigo,$quantidade";
        }
    }
    $codigos_miscelanea = implode(' | ', $pares);

    $conn->query("INSERT INTO contracts (contrato, nome, cadastro, agendamento, repasse, status, bairro, servico, tecnico, executada, onu, observacao, codigos_miscelanea)
            VALUES ('$contrato', '$nome', '$cadastro', '$agendamento', '$repasse', '$status', '$bairro', '$servico', '$tecnico', $executada, '$onu', '$observacao', '$codigos_miscelanea')");
    header("Location: ".$_SERVER['PHP_SELF']."?success=1");
    exit;
}

// Atualizar contrato
if ($_SERVER["REQUEST_METHOD"] == "POST" && ($_POST["action"] ?? '') == "update") {
    $id = sanitize($conn, $_POST["id"]);
    $contrato = sanitize($conn, $_POST["contrato"]);
    $nome = sanitize($conn, $_POST["nome"]);
    $cadastro = sanitize($conn, $_POST["cadastro"]);
    $agendamento = sanitize($conn, $_POST["agendamento"]);
    $repasse = sanitize($conn, $_POST["repasse"]); // YYYY-MM-DD
    $status = sanitize($conn, $_POST["status"]);
    $bairro = sanitize($conn, $_POST["bairro"]);
    $servico = sanitize($conn, $_POST["servico"]);
    $tecnico = sanitize($conn, $_POST["tecnico"]);
    $executada = isset($_POST["executada"]) ? 1 : 0;
    $onu = sanitize($conn, $_POST["onu"]);
    $observacao = sanitize($conn, $_POST["observacao"]);

    // Miscelâneas (tabela dinâmica)
    $codigos = $_POST['codigos_miscelanea'] ?? [];
    $descricoes = $_POST['descricoes_miscelanea'] ?? [];
    $quantidades = $_POST['quantidades_miscelanea'] ?? [];
    $pares = [];
    foreach ($codigos as $i => $codigo) {
        $codigo = sanitize($conn, $codigo);
        $descricao = isset($descricoes[$i]) ? sanitize($conn, $descricoes[$i]) : '';
        $quantidade = isset($quantidades[$i]) ? floatval($quantidades[$i]) : 0;
        if ($codigo && $quantidade > 0) {
            // Atualiza o estoque
            $stmt = $conn->prepare("SELECT id, quantidade FROM miscelaneas WHERE codigo = ? LIMIT 1");
            $stmt->bind_param("s", $codigo);
            $stmt->execute();
            $resultado = $stmt->get_result();
            if ($resultado && $row = $resultado->fetch_assoc()) {
                $nova_quantidade = max(0, floatval($row['quantidade']) - $quantidade);
                $upd = $conn->prepare("UPDATE miscelaneas SET quantidade = ? WHERE id = ?");
                $upd->bind_param("di", $nova_quantidade, $row['id']);
                $upd->execute();
                $upd->close();
            }
            $stmt->close();
            $pares[] = "$codigo,$quantidade";
        }
    }
    $codigos_miscelanea = implode(' | ', $pares);

    $conn->query("UPDATE contracts SET 
        contrato = '$contrato',
        nome = '$nome',
        cadastro = '$cadastro',
        agendamento = '$agendamento',
        repasse = '$repasse',
        status = '$status',
        bairro = '$bairro',
        servico = '$servico',
        tecnico = '$tecnico',
        executada = $executada,
        onu = '$onu',
        observacao = '$observacao',
        codigos_miscelanea = '$codigos_miscelanea'
        WHERE id = $id");
    header("Location: ".$_SERVER['PHP_SELF']."?success=1");
    exit;
}

// Excluir contrato
if ($_SERVER["REQUEST_METHOD"] == "GET" && ($_GET["action"] ?? '') == "delete" && isset($_GET["id"])) {
    $id = sanitize($conn, $_GET["id"]);
    $conn->query("DELETE FROM contracts WHERE id = $id");
}

// =================== DADOS PARA EDIÇÃO DE CONTRATO ===================
$editData = null;
if ($_SERVER["REQUEST_METHOD"] == "GET" && ($_GET["action"] ?? '') == "edit" && isset($_GET["id"])) {
    $id = sanitize($conn, $_GET["id"]);
    $result = $conn->query("SELECT * FROM contracts WHERE id = $id");
    if ($result && $result->num_rows > 0) {
        $editData = $result->fetch_assoc();
    }
}

// =================== BUSCA DOS DADOS BÁSICOS ===================
$technicians = [];
$techResult = $conn->query("SELECT * FROM technicians ORDER BY nome");
if ($techResult && $techResult->num_rows > 0) {
    while($row = $techResult->fetch_assoc()) {
        $technicians[] = $row;
    }
}
$services = [];
$serviceResult = $conn->query("SELECT * FROM services ORDER BY nome");
if ($serviceResult && $serviceResult->num_rows > 0) {
    while($row = $serviceResult->fetch_assoc()) {
        $services[] = $row;
    }
}
$neighborhoods = [];
$neighborhoodResult = $conn->query("SELECT * FROM neighborhoods ORDER BY nome");
if ($neighborhoodResult && $neighborhoodResult->num_rows > 0) {
    while($row = $neighborhoodResult->fetch_assoc()) {
        $neighborhoods[] = $row;
    }
}

// =================== BUSCA DOS CONTRATOS ===================
// $contracts = [];
// $sql = "SELECT * FROM contracts ORDER BY agendamento DESC";
// $result = $conn->query($sql);
// if ($result && $result->num_rows > 0) {
//     while($row = $result->fetch_assoc()) {
//         $contracts[] = $row;
//     }
// }
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Sistema de Gerenciamento de Contratos</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.2.3/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
.btn-gradient-primary {
    background: linear-gradient(90deg, #3b82f6 0%, #06b6d4 100%);
    color: #fff;
    border: none;
}
.btn-gradient-primary:hover, .btn-gradient-primary:focus {
    background: linear-gradient(90deg, #06b6d4 0%, #3b82f6 100%);
    color: #fff;
}
.btn-gradient-danger {
    background: linear-gradient(90deg, #ef4444 0%, #f59e42 100%);
    color: #fff;
    border: none;
}
.btn-gradient-danger:hover, .btn-gradient-danger:focus {
    background: linear-gradient(90deg, #f59e42 0%, #ef4444 100%);
    color: #fff;
}
</style>
</head>
<body>
<div class="container mt-4">
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Salvo com sucesso!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="top-actions d-flex justify-content-between align-items-center mb-3">
        <h1>Sistema de Gerenciamento de Contratos</h1>
        <a href="index.php" class="btn btn-primary" aria-label="Voltar ao Início">
            <i class="bi bi-house"></i> Voltar ao Início
        </a>
    </div>
    <div class="mb-4">
        <button type="button" class="btn btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#technicianModal">
            Gerenciar Técnicos
        </button>
        <button type="button" class="btn btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#serviceModal">
            Gerenciar Serviços
        </button>
        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#neighborhoodModal">
            Gerenciar Bairros
        </button>
    </div>

    <div class="card mt-2">
        <div class="card-header">
            <?php echo $editData ? 'Editar Contrato' : 'Adicionar Novo Contrato'; ?>
        </div>
        <div class="card-body">
            <form method="post" action="">
                <?php if($editData): ?>
                    <input type="hidden" name="id" value="<?php echo $editData['id']; ?>">
                    <input type="hidden" name="action" value="update">
                <?php else: ?>
                    <input type="hidden" name="action" value="add">
                <?php endif; ?>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="contrato" class="form-label">Contrato</label>
                        <input type="text" class="form-control" id="contrato" name="contrato" value="<?php echo $editData ? $editData['contrato'] : ''; ?>" required>
                    </div>
                    <div class="col-md-5 mb-3">
                        <label for="nome" class="form-label">Nome</label>
                        <input type="text" class="form-control" id="nome" name="nome" value="<?php echo $editData ? $editData['nome'] : ''; ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="cadastro" class="form-label">Cadastro</label>
                        <input type="date" class="form-control" id="cadastro" name="cadastro" value="<?php echo $editData ? $editData['cadastro'] : ''; ?>">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="agendamento" class="form-label">Agendamento</label>
                        <input type="datetime-local" class="form-control" id="agendamento" name="agendamento" value="<?php echo $editData ? str_replace(' ', 'T', $editData['agendamento']) : ''; ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="repasse" class="form-label">Repasse</label>
                        <input type="date" class="form-control" id="repasse" name="repasse" value="<?php echo $editData ? $editData['repasse'] : ''; ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">Selecione</option>
                            <option value="Pendente" <?php echo ($editData && $editData['status'] == 'Pendente') ? 'selected' : ''; ?>>Pendente</option>
                            <option value="Em Andamento" <?php echo ($editData && $editData['status'] == 'Em Andamento') ? 'selected' : ''; ?>>Em Andamento</option>
                            <option value="Concluído" <?php echo ($editData && $editData['status'] == 'Concluído') ? 'selected' : ''; ?>>Concluído</option>
                            <option value="Cancelado" <?php echo ($editData && $editData['status'] == 'Cancelado') ? 'selected' : ''; ?>>Cancelado</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="bairro" class="form-label">Bairro</label>
                        <select class="form-select" id="bairro" name="bairro">
                            <option value="">Selecione</option>
                            <?php foreach ($neighborhoods as $neighborhood): ?>
                                <option value="<?php echo $neighborhood['nome']; ?>" <?php echo ($editData && $editData['bairro'] == $neighborhood['nome']) ? 'selected' : ''; ?>>
                                    <?php echo $neighborhood['nome']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="servico" class="form-label">Serviço</label>
                        <select class="form-select" id="servico" name="servico" required onchange="atualizaValorServico()">
                            <option value="">Selecione</option>
                            <?php foreach ($services as $service): ?>
                                <option value="<?php echo $service['nome']; ?>" data-valor="<?php echo $service['valor']; ?>" <?php echo ($editData && $editData['servico'] == $service['nome']) ? 'selected' : ''; ?>>
                                    <?php echo $service['nome']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="valor_servico" class="form-label">Valor do Serviço</label>
                        <input type="text" class="form-control" id="valor_servico" name="valor_servico" readonly value="">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="tecnico" class="form-label">Técnico</label>
                        <select class="form-select" id="tecnico" name="tecnico">
                            <option value="">Selecione</option>
                            <?php foreach ($technicians as $technician): ?>
                                <option value="<?php echo $technician['nome']; ?>" <?php echo ($editData && $editData['tecnico'] == $technician['nome']) ? 'selected' : ''; ?>>
                                    <?php echo $technician['nome']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="onu" class="form-label">ONU</label>
                        <input type="text" class="form-control" id="onu" name="onu" value="<?php echo $editData ? $editData['onu'] : ''; ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" id="executada" name="executada" <?php echo ($editData && $editData['executada']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="executada">
                                Executada
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Tabela dinâmica de miscelâneas -->
                <div class="mb-3">
                    <label class="form-label">Miscelâneas utilizadas</label>
                    <table class="table table-bordered" id="miscelanea-table">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Descrição</th>
                                <th>Quantidade usada</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Preenche linhas existentes no modo edição -->
                            <?php
                            if ($editData && !empty($editData['codigos_miscelanea'])) {
                                $pares = explode('|', $editData['codigos_miscelanea']);
                                foreach ($pares as $par) {
                                    $par = trim($par);
                                    if (strpos($par, ',') !== false) {
                                        list($codigo, $quantidade) = array_map('trim', explode(',', $par, 2));
                                        // Busca descrição
                                        $desc = '';
                                        $rdesc = $conn->query("SELECT descricao FROM miscelaneas WHERE codigo='$codigo' LIMIT 1");
                                        if ($rdesc && $rdesc->num_rows > 0) $desc = $rdesc->fetch_assoc()['descricao'];
                                        echo "<tr>
                                        <td><input type='text' class='form-control codigo-miscelanea' name='codigos_miscelanea[]' value='" . htmlspecialchars($codigo) . "' onblur='fetchDescricao(this)'></td>
                                        <td><input type='text' class='form-control descricao-miscelanea' name='descricoes_miscelanea[]' value='" . htmlspecialchars($desc) . "' readonly></td>
                                        <td><input type='number' class='form-control quantidade-miscelanea' name='quantidades_miscelanea[]' value='" . htmlspecialchars($quantidade) . "' step='0.01' min='0'></td>
                                        <td><button type='button' class='btn btn-danger btn-sm' onclick='this.closest(\"tr\").remove()'>Remover</button></td>
                                        </tr>";
                                    }
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-success btn-sm" onclick="addMiscelaneaRow()">Adicionar Código</button>
                </div>

                <div class="mb-3">
                    <label for="observacao" class="form-label">Observação</label>
                    <textarea class="form-control" id="observacao" name="observacao" rows="3"><?php echo $editData ? $editData['observacao'] : ''; ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary">
                    <?php echo $editData ? 'Atualizar' : 'Salvar'; ?>
                </button>
                <?php if($editData): ?>
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-secondary">Cancelar</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Lista de Contratos REMOVIDA -->

</div>

<!-- Technician Modal -->
<div class="modal fade" id="technicianModal" tabindex="-1" aria-labelledby="technicianModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="technicianModalLabel">Gerenciar Técnicos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="">
                    <input type="hidden" name="action" value="add_technician">
                    <div class="mb-3">
                        <label for="technician_name" class="form-label">Nome do Técnico</label>
                        <input type="text" class="form-control" id="technician_name" name="technician_name" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Adicionar Técnico</button>
                </form>
                <hr>
                <h5>Técnicos Existentes</h5>
                <ul class="list-group">
                    <?php foreach ($technicians as $technician): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <?php echo $technician['nome']; ?>
                            <a href="?action=delete_technician&id=<?php echo $technician['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Excluir este técnico?')">Excluir</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Service Modal -->
<div class="modal fade" id="serviceModal" tabindex="-1" aria-labelledby="serviceModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="serviceModalLabel">Gerenciar Serviços</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="">
                    <input type="hidden" name="action" value="add_service">
                    <div class="mb-3">
                        <label for="service_name" class="form-label">Nome do Serviço</label>
                        <input type="text" class="form-control" id="service_name" name="service_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="service_value" class="form-label">Valor do Serviço</label>
                        <input type="number" step="0.01" class="form-control" id="service_value" name="service_value" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Adicionar Serviço</button>
                </form>
                <hr>
                <h5>Serviços Existentes</h5>
                <ul class="list-group">
                    <?php foreach ($services as $service): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <?php echo $service['nome']; ?> - R$ <?php echo number_format($service['valor'], 2, ',', '.'); ?>
                            <a href="?action=delete_service&id=<?php echo $service['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Excluir este serviço?')">Excluir</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Neighborhood Modal -->
<div class="modal fade" id="neighborhoodModal" tabindex="-1" aria-labelledby="neighborhoodModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="neighborhoodModalLabel">Gerenciar Bairros</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="">
                    <input type="hidden" name="action" value="add_neighborhood">
                    <div class="mb-3">
                        <label for="neighborhood_name" class="form-label">Nome do Bairro</label>
                        <input type="text" class="form-control" id="neighborhood_name" name="neighborhood_name" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Adicionar Bairro</button>
                </form>
                <hr>
                <h5>Bairros Existentes</h5>
                <ul class="list-group">
                    <?php foreach ($neighborhoods as $neighborhood): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <?php echo $neighborhood['nome']; ?>
                            <a href="?action=delete_neighborhood&id=<?php echo $neighborhood['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Excluir este bairro?')">Excluir</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
function addMiscelaneaRow(codigo='', descricao='', quantidade='') {
    let tbody = document.querySelector('#miscelanea-table tbody');
    let row = document.createElement('tr');
    row.innerHTML = `
        <td>
            <input type="text" class="form-control codigo-miscelanea" name="codigos_miscelanea[]" value="${codigo}" onblur="fetchDescricao(this)">
        </td>
        <td><input type="text" class="form-control descricao-miscelanea" name="descricoes_miscelanea[]" value="${descricao}" readonly></td>
        <td><input type="number" class="form-control quantidade-miscelanea" name="quantidades_miscelanea[]" value="${quantidade}" step="0.01" min="0"></td>
        <td><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove()">Remover</button></td>
    `;
    tbody.appendChild(row);

    // Evento: Ao pressionar ENTER no código, cria nova linha e foca no próximo
    let codigoInputs = tbody.querySelectorAll('.codigo-miscelanea');
    let ultimoInput = codigoInputs[codigoInputs.length - 1];
    ultimoInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            addMiscelaneaRow();
            setTimeout(() => {
                let novosInputs = tbody.querySelectorAll('.codigo-miscelanea');
                novosInputs[novosInputs.length-1].focus();
            }, 10);
        }
    });
}
// Busca descrição via AJAX quando o usuário digita/clica fora do campo código
function fetchDescricao(input) {
    let codigo = input.value.trim();
    let descInput = input.closest('tr').querySelector('.descricao-miscelanea');
    if (codigo !== '') {
        fetch('<?=$_SERVER['PHP_SELF']?>?action=get_miscelanea_descricao&codigo=' + encodeURIComponent(codigo))
        .then(response => response.text())
        .then(desc => descInput.value = desc)
        .catch(() => descInput.value = '');
    } else {
        descInput.value = '';
    }
}
// Atualiza valor do serviço ao selecionar serviço
function atualizaValorServico() {
    var select = document.getElementById("servico");
    var valor = "";
    if (select.selectedIndex > 0) {
        var option = select.options[select.selectedIndex];
        var val = option.getAttribute("data-valor");
        valor = val ? parseFloat(val).toLocaleString('pt-BR', {minimumFractionDigits:2}) : "";
    }
    document.getElementById("valor_servico").value = valor ? "R$ "+valor : "";
}
window.onload = function() {
    atualizaValorServico();
    // Ativa ENTER para inputs existentes ao carregar a página
    let tbody = document.querySelector('#miscelanea-table tbody');
    let codigoInputs = tbody.querySelectorAll('.codigo-miscelanea');
    codigoInputs.forEach(function(input) {
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                addMiscelaneaRow();
                setTimeout(() => {
                    let novosInputs = tbody.querySelectorAll('.codigo-miscelanea');
                    novosInputs[novosInputs.length-1].focus();
                }, 10);
            }
        });
    });
};
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.2.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>