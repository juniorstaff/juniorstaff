<?php
// =================== CONFIGURAÇÕES DO BANCO ===================
$host = "localhost";
$username = "root";
$password = "";
$database = "sistema_servicos";

// =================== CONEXÃO ===================
$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// =================== BUSCA DOS SERVIÇOS PARA VALORES ===================
$services = [];
$serviceResult = $conn->query("SELECT * FROM services ORDER BY nome");
if ($serviceResult && $serviceResult->num_rows > 0) {
    while($row = $serviceResult->fetch_assoc()) {
        $services[$row['nome']] = $row; // facilita busca pelo nome
    }
}

// =================== FILTRO DINÂMICO ===================
$where = [];
$params = [];
if (!empty($_GET['contrato'])) {
    $contrato = $conn->real_escape_string($_GET['contrato']);
    $where[] = "contrato LIKE '%$contrato%'";
}
if (!empty($_GET['nome'])) {
    $nome = $conn->real_escape_string($_GET['nome']);
    $where[] = "nome LIKE '%$nome%'";
}
if (!empty($_GET['status'])) {
    $status = $conn->real_escape_string($_GET['status']);
    $where[] = "status = '$status'";
}
if (!empty($_GET['bairro'])) {
    $bairro = $conn->real_escape_string($_GET['bairro']);
    $where[] = "bairro = '$bairro'";
}
if (!empty($_GET['servico'])) {
    $servico = $conn->real_escape_string($_GET['servico']);
    $where[] = "servico = '$servico'";
}
if (!empty($_GET['tecnico'])) {
    $tecnico = $conn->real_escape_string($_GET['tecnico']);
    $where[] = "tecnico = '$tecnico'";
}

// =================== FILTRO MODERNO POR MÊS E ANO ===================
// Busca anos disponíveis
$anosDisponiveis = [];
$resAnos = $conn->query("SELECT DISTINCT YEAR(agendamento) as ano FROM contracts WHERE agendamento IS NOT NULL ORDER BY ano DESC");
while ($row = $resAnos->fetch_assoc()) {
    if ($row['ano']) $anosDisponiveis[] = $row['ano'];
}
$mesSelecionado = $_GET['mes'] ?? '';
$anoSelecionado = $_GET['ano'] ?? '';
if ($mesSelecionado) {
    $mes = intval($mesSelecionado);
    $where[] = "MONTH(agendamento) = $mes";
}
if ($anoSelecionado) {
    $ano = intval($anoSelecionado);
    $where[] = "YEAR(agendamento) = $ano";
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT * FROM contracts $whereSQL ORDER BY agendamento DESC";
$result = $conn->query($sql);

$contracts = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $contracts[] = $row;
    }
}

// =================== BUSCA PARA FILTROS DINÂMICOS ===================
$neighborhoods = [];
$neighborhoodResult = $conn->query("SELECT nome FROM neighborhoods ORDER BY nome");
if ($neighborhoodResult && $neighborhoodResult->num_rows > 0) {
    while($row = $neighborhoodResult->fetch_assoc()) {
        $neighborhoods[] = $row['nome'];
    }
}
$allServices = array_keys($services);

$technicians = [];
$techResult = $conn->query("SELECT nome FROM technicians ORDER BY nome");
if ($techResult && $techResult->num_rows > 0) {
    while($row = $techResult->fetch_assoc()) {
        $technicians[] = $row['nome'];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Lista de Contratos</title>
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
    <div class="top-actions d-flex justify-content-between align-items-center mb-3">
        <h1>Lista de Contratos</h1>
        <a href="index.php" class="btn btn-primary" aria-label="Voltar ao Início">
            <i class="bi bi-house"></i> Voltar ao Início
        </a>
    </div>
    <!-- FILTRO ONLINE -->
    <form class="mb-3" method="get" id="filtroForm">
        <div class="row g-2 align-items-end">
            <div class="col-sm-2">
                <label for="contrato" class="form-label">Contrato</label>
                <input type="text" class="form-control" name="contrato" id="contrato" value="<?= htmlspecialchars($_GET['contrato'] ?? '') ?>">
            </div>
            <div class="col-sm-2">
                <label for="nome" class="form-label">Nome</label>
                <input type="text" class="form-control" name="nome" id="nome" value="<?= htmlspecialchars($_GET['nome'] ?? '') ?>">
            </div>
            <div class="col-sm-2">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" name="status" id="status">
                    <option value="">Todos</option>
                    <?php
                    $statusList = ['Pendente','Em Andamento','Concluído','Cancelado'];
                    foreach ($statusList as $st) {
                        echo "<option value=\"$st\" ".((isset($_GET['status']) && $_GET['status']==$st)?'selected':'').">$st</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-sm-2">
                <label for="bairro" class="form-label">Bairro</label>
                <select class="form-select" name="bairro" id="bairro">
                    <option value="">Todos</option>
                    <?php foreach ($neighborhoods as $bairro): ?>
                        <option value="<?= htmlspecialchars($bairro) ?>" <?= (isset($_GET['bairro']) && $_GET['bairro']==$bairro)?'selected':'' ?>>
                            <?= htmlspecialchars($bairro) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-2">
                <label for="servico" class="form-label">Serviço</label>
                <select class="form-select" name="servico" id="servico">
                    <option value="">Todos</option>
                    <?php foreach ($allServices as $service): ?>
                        <option value="<?= htmlspecialchars($service) ?>" <?= (isset($_GET['servico']) && $_GET['servico']==$service)?'selected':'' ?>>
                            <?= htmlspecialchars($service) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-2">
                <label for="tecnico" class="form-label">Técnico</label>
                <select class="form-select" name="tecnico" id="tecnico">
                    <option value="">Todos</option>
                    <?php foreach ($technicians as $tech): ?>
                        <option value="<?= htmlspecialchars($tech) ?>" <?= (isset($_GET['tecnico']) && $_GET['tecnico']==$tech)?'selected':'' ?>>
                            <?= htmlspecialchars($tech) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-2">
                <label for="mes" class="form-label">Mês</label>
                <select class="form-select" name="mes" id="mes">
                    <option value="">Todos</option>
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= ($mesSelecionado == $m ? 'selected' : '') ?>>
                            <?= str_pad($m, 2, '0', STR_PAD_LEFT) . ' - ' . DateTime::createFromFormat('!m', $m)->format('F') ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-sm-2">
                <label for="ano" class="form-label">Ano</label>
                <select class="form-select" name="ano" id="ano">
                    <option value="">Todos</option>
                    <?php foreach ($anosDisponiveis as $ano): ?>
                        <option value="<?= $ano ?>" <?= ($anoSelecionado == $ano ? 'selected' : '') ?>><?= $ano ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto" style="margin-top:24px;">
                <button class="btn btn-primary" type="submit">Filtrar</button>
                <a href="menu_lista_contratos.php" class="btn btn-secondary">Limpar</a>
            </div>
        </div>
    </form>
    <!-- TABELA DE CONTRATOS -->
    <div class="card">
        <div class="card-header">
            Resultados encontrados: <?= count($contracts); ?>
        </div>
        <div class="card-body">
            <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Contrato</th>
                        <th>Nome</th>
                        <th>Agendamento</th>
                        <th>Status</th>
                        <th>Bairro</th>
                        <th>Serviço</th>
                        <th>Valor Serviço</th>
                        <th>Técnico</th>
                        <th>Miscelâneas / Qtde</th>
                        <th>Metragem</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($contracts)): ?>
                    <tr><td colspan="11" class="text-center">Nenhum contrato encontrado.</td></tr>
                <?php else: foreach ($contracts as $contract): ?>
                    <tr>
                        <td><?= htmlspecialchars($contract['contrato']); ?></td>
                        <td><?= htmlspecialchars($contract['nome']); ?></td>
                        <td><?= htmlspecialchars($contract['agendamento']); ?></td>
                        <td><?= htmlspecialchars($contract['status']); ?></td>
                        <td><?= htmlspecialchars($contract['bairro']); ?></td>
                        <td><?= htmlspecialchars($contract['servico']); ?></td>
                        <td>
                            <?php
                            $valorServico = 'R$ 0,00';
                            if (isset($services[$contract['servico']])) {
                                $valorServico = 'R$ ' . number_format($services[$contract['servico']]['valor'], 2, ',', '.');
                            }
                            echo $valorServico;
                            ?>
                        </td>
                        <td><?= htmlspecialchars($contract['tecnico']); ?></td>
                        <td>
                            <?php
                            if (!empty($contract['codigos_miscelanea'])) {
                                $pares = explode('|', $contract['codigos_miscelanea']);
                                $exibir = [];
                                foreach ($pares as $par) {
                                    $par = trim($par);
                                    if (strpos($par, ',') !== false) {
                                        list($codigo, $metragem) = array_map('trim', explode(',', $par, 2));
                                        // Busca descrição
                                        $desc = '';
                                        $rdesc = $conn->query("SELECT descricao FROM miscelaneas WHERE codigo='$codigo' LIMIT 1");
                                        if ($rdesc && $rdesc->num_rows > 0) $desc = $rdesc->fetch_assoc()['descricao'];
                                        $exibir[] = "<b>$codigo</b> ($desc): $metragem";
                                    }
                                }
                                echo implode('<br>', $exibir);
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            // Soma total da metragem das miscelâneas
                            $soma_metragem = 0;
                            if (!empty($contract['codigos_miscelanea'])) {
                                $pares = explode('|', $contract['codigos_miscelanea']);
                                foreach ($pares as $par) {
                                    $par = trim($par);
                                    if (strpos($par, ',') !== false) {
                                        list($codigo, $metragem) = array_map('trim', explode(',', $par, 2));
                                        $soma_metragem += floatval($metragem);
                                    }
                                }
                            }
                            echo number_format($soma_metragem, 2, ',', '.');
                            ?>
                        </td>
                        <td>
                            <div class="d-flex gap-2">
                                <a href="index.php?action=edit&id=<?= $contract['id'] ?>" class="btn btn-sm btn-gradient-primary">Editar</a>
                                <a href="index.php?action=delete&id=<?= $contract['id'] ?>" class="btn btn-sm btn-gradient-danger" onclick="return confirm('Tem certeza que deseja excluir este contrato?')">Excluir</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.2.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>