<?php
// Configuração de exibição de erros no PHP (apenas para desenvolvimento)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db.php';
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$show_success_message = false;
$error_message = '';
$success_message = '';

// Função para sanitizar entradas
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Debug: Log de requisições POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("POST recebido: " . print_r($_POST, true));
}

// Exportação para Excel
if (isset($_POST['export'])) {
    try {
        $stmt = $conn->prepare("SELECT * FROM cadastro_onu ORDER BY data_recebimento DESC");
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($results)) {
            $error_message = "Nenhum registro encontrado para exportação!";
        } else {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Definir cabeçalhos
            $headers = ['ID', 'Equipamento MAC', 'Equipamento HFC', 'Descrição', 'Técnico Origem', 'Data Recebimento', 'RMA'];
            foreach (range('A', 'G') as $index => $column) {
                $sheet->setCellValue($column . '1', $headers[$index]);
                $sheet->getStyle($column . '1')->getFont()->setBold(true);
            }

            // Preencher dados
            $row = 2;
            foreach ($results as $data) {
                $sheet->setCellValue('A' . $row, $data['id'] ?? '');
                $sheet->setCellValue('B' . $row, $data['mac'] ?? '');
                $sheet->setCellValue('C' . $row, $data['hfc'] ?? '');
                $sheet->setCellValue('D' . $row, $data['descricao'] ?? '');
                $sheet->setCellValue('E' . $row, $data['tecn_origem'] ?? '');
                $sheet->setCellValue('F' . $row, $data['data_recebimento'] ?? '');
                $sheet->setCellValue('G' . $row, $data['rma'] ?? '');
                $row++;
            }

            // Autoajustar colunas
            foreach (range('A', 'G') as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }

            // Configurar cabeçalho para download
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="cadastro_onu_' . date('Y-m-d') . '.xlsx"');
            header('Cache-Control: max-age=0');

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;
        }
    } catch (Exception $e) {
        $error_message = "Erro ao exportar para Excel: " . $e->getMessage();
        error_log("Erro Excel: " . $e->getMessage());
    }
}

// Exclusão de Registro
if (isset($_POST['delete']) && isset($_POST['id'])) {
    $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
    if ($id) {
        try {
            $stmt = $conn->prepare("DELETE FROM cadastro_onu WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                header('Location: ' . $_SERVER['PHP_SELF'] . '?deleted=1');
                exit();
            } else {
                $error_message = "Registro não encontrado para exclusão.";
            }
        } catch (PDOException $e) {
            $error_message = "Erro ao excluir registro: " . $e->getMessage();
            error_log("Erro exclusão: " . $e->getMessage());
        }
    } else {
        $error_message = "ID inválido para exclusão.";
    }
}

// Edição de Registro
if (isset($_POST['update']) && isset($_POST['id'])) {
    $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
    if ($id) {
        $mac = sanitize($_POST['mac_edit']);
        $hfc = sanitize($_POST['hfc_edit']);
        $descricao = sanitize($_POST['descricao_edit']);
        $tecn_origem = sanitize($_POST['tecn_origem_edit']);
        $data_recebimento = !empty($_POST['data_recebimento_edit']) ? $_POST['data_recebimento_edit'] : null;
        $rma = sanitize($_POST['rma_edit']);

        // Validação básica
        if (empty($mac) || empty($hfc) || empty($descricao) || empty($tecn_origem)) {
            $error_message = "Todos os campos obrigatórios devem ser preenchidos.";
        } else {
            try {
                $stmt = $conn->prepare(
                    "UPDATE cadastro_onu SET mac = :mac, hfc = :hfc, descricao = :descricao, tecn_origem = :tecn_origem, data_recebimento = :data_recebimento, rma = :rma WHERE id = :id"
                );
                $stmt->bindParam(':mac', $mac);
                $stmt->bindParam(':hfc', $hfc);
                $stmt->bindParam(':descricao', $descricao);
                $stmt->bindParam(':tecn_origem', $tecn_origem);
                $stmt->bindParam(':data_recebimento', $data_recebimento);
                $stmt->bindParam(':rma', $rma);
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    header('Location: ' . $_SERVER['PHP_SELF'] . '?updated=1');
                    exit();
                } else {
                    $error_message = "Nenhum registro foi alterado. Verifique se os dados foram modificados.";
                }
            } catch (PDOException $e) {
                $error_message = "Erro ao atualizar registro: " . $e->getMessage();
                error_log("Erro atualização: " . $e->getMessage());
            }
        }
    } else {
        $error_message = "ID inválido para atualização.";
    }
}

// Inserção de Registro - CORRIGIDO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mac']) && isset($_POST['hfc']) && !isset($_POST['update']) && !isset($_POST['delete']) && !isset($_POST['export'])) {
    
    // Debug: Log dos dados recebidos
    error_log("Dados de inserção recebidos: MAC=" . $_POST['mac'] . ", HFC=" . $_POST['hfc']);
    
    $mac_input = trim($_POST['mac'] ?? '');
    $hfc_input = trim($_POST['hfc'] ?? '');
    $descricao = sanitize($_POST['descricao'] ?? '');
    $tecn_origem = sanitize($_POST['tecn_origem'] ?? '');
    $data_recebimento = !empty($_POST['data_recebimento']) ? $_POST['data_recebimento'] : null;
    $rma = sanitize($_POST['rma'] ?? '');

    // Validação básica
    if (empty($mac_input) || empty($hfc_input) || empty($descricao) || empty($tecn_origem)) {
        $error_message = "Todos os campos obrigatórios devem ser preenchidos.";
    } else {
        // Processar MACs e HFCs
        $macs = array_filter(array_map('trim', explode("\n", $mac_input)), function($mac) {
            return !empty($mac);
        });
        
        $hfcs = array_filter(array_map('trim', explode("\n", $hfc_input)), function($hfc) {
            return !empty($hfc);
        });

        // Debug: Log dos arrays processados
        error_log("MACs processados: " . print_r($macs, true));
        error_log("HFCs processados: " . print_r($hfcs, true));

        if (count($macs) !== count($hfcs)) {
            $error_message = "O número de MACs (" . count($macs) . ") e HFCs (" . count($hfcs) . ") deve ser igual!";
        } else {
            try {
                $conn->beginTransaction();

                $stmt = $conn->prepare(
                    "INSERT INTO cadastro_onu (mac, hfc, descricao, tecn_origem, data_recebimento, rma) VALUES (:mac, :hfc, :descricao, :tecn_origem, :data_recebimento, :rma)"
                );

                $inserted_count = 0;
                foreach ($macs as $index => $mac) {
                    $mac = sanitize($mac);
                    $hfc = isset($hfcs[$index]) ? sanitize($hfcs[$index]) : '';

                    if (!empty($mac) && !empty($hfc)) {
                        $stmt->bindParam(':mac', $mac);
                        $stmt->bindParam(':hfc', $hfc);
                        $stmt->bindParam(':descricao', $descricao);
                        $stmt->bindParam(':tecn_origem', $tecn_origem);
                        $stmt->bindParam(':data_recebimento', $data_recebimento);
                        $stmt->bindParam(':rma', $rma);

                        if ($stmt->execute()) {
                            $inserted_count++;
                            error_log("Registro inserido: MAC=$mac, HFC=$hfc");
                        } else {
                            error_log("Falha ao inserir: MAC=$mac, HFC=$hfc");
                        }
                    }
                }

                if ($inserted_count > 0) {
                    $conn->commit();
                    header('Location: ' . $_SERVER['PHP_SELF'] . '?success=1&count=' . $inserted_count);
                    exit();
                } else {
                    $conn->rollBack();
                    $error_message = "Nenhum registro foi inserido. Verifique os dados informados.";
                }
            } catch (Exception $e) {
                $conn->rollBack();
                $error_message = "Erro ao salvar os dados: " . $e->getMessage();
                error_log("Erro inserção: " . $e->getMessage());
            }
        }
    }
}

// Verifica mensagens de status
if (isset($_GET['success'])) {
    $count = isset($_GET['count']) ? (int)$_GET['count'] : 1;
    $success_message = "Dados salvos com sucesso! $count registro(s) inserido(s).";
    $show_success_message = true;
} elseif (isset($_GET['deleted'])) {
    $success_message = "Registro excluído com sucesso!";
    $show_success_message = true;
} elseif (isset($_GET['updated'])) {
    $success_message = "Registro atualizado com sucesso!";
    $show_success_message = true;
}

// Carregar técnicos da base de dados
$tecnicos = ['DC TELECOM, JUNIOR']; // Valor padrão
try {
    $stmt = $conn->prepare("SELECT DISTINCT tecn_origem FROM cadastro_onu WHERE tecn_origem != '' AND tecn_origem IS NOT NULL ORDER BY tecn_origem");
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if ($results) {
        $tecnicos = array_unique(array_merge($tecnicos, $results));
    }
} catch (PDOException $e) {
    error_log("Erro ao carregar técnicos: " . $e->getMessage());
}

// Carregar dados para a tabela
try {
    $stmt = $conn->prepare("SELECT * FROM cadastro_onu ORDER BY data_recebimento DESC, id DESC LIMIT 100");
    $stmt->execute();
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Erro ao carregar registros: " . $e->getMessage();
    $registros = [];
    error_log("Erro carregar registros: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Equipamentos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #27ae60;
            --danger-color: #e74c3c;
            --warning-color: #f1c40f;
            --text-color: #333;
            --light-bg: #f5f7fa;
            --border-color: #ddd;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-bg);
            margin: 0;
            padding: 20px;
            color: var(--text-color);
        }

        .page-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            max-width: 2000px;
            margin: 0 auto;
        }

        .container, .spreadsheet {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            min-width: 300px;
        }

        .container {
            flex: 2;
        }

        .spreadsheet {
            flex: 4;
            min-width: 700px;
            max-width: 1400px;
            width: 100%;
        }

        h2 {
            color: var(--primary-color);
            margin-bottom: 20px;
            border-bottom: 2px solid var(--secondary-color);
            padding-bottom: 10px;
        }
        
        form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            grid-column: span 2;
        }
        
        .form-row {
            display: flex;
            gap: 15px;
            grid-column: span 2;
        }
        
        .form-row .form-group {
            flex: 1;
            grid-column: span 1;
        }
        
        label {
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--primary-color);
        }
        
        input[type="text"],
        input[type="date"],
        select,
        textarea {
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            width: 100%;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }
        
        textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            flex-wrap: wrap;
            grid-column: span 2;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            padding: 10px 15px;
            background-color: var(--secondary-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.2s;
            min-width: 120px;
        }
        
        .btn-primary {
            background-color: var(--secondary-color);
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
        }
        
        .btn-success {
            background-color: var(--success-color);
        }
        
        .btn-success:hover {
            background-color: #219653;
        }
        
        .btn-danger {
            background-color: var(--danger-color);
        }
        
        .btn-danger:hover {
            background-color: #c0392b;
        }
        
        .btn-warning {
            background-color: var(--warning-color);
            color: #333;
        }
        
        .btn-warning:hover {
            background-color: #f39c12;
        }

        .btn-info {
            background-color: #17a2b8;
        }

        .btn-info:hover {
            background-color: #138496;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 14px;
        }
        
        thead {
            background-color: var(--primary-color);
            color: white;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        tbody tr:hover {
            background-color: rgba(52, 152, 219, 0.1);
        }
        
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background-color: rgba(39, 174, 96, 0.1);
            border-left: 4px solid var(--success-color);
            color: var(--success-color);
        }
        
        .alert-danger {
            background-color: rgba(231, 76, 60, 0.1);
            border-left: 4px solid var(--danger-color);
            color: var(--danger-color);
        }
        
        .search-container {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }
        
        .search-input {
            flex: 1;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
        }
        
        .action-btns {
            display: flex;
            gap: 5px;
        }
        
        .btn-icon {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            padding: 5px;
            color: var(--primary-color);
            transition: color 0.2s;
        }
        
        .btn-icon:hover {
            color: var(--secondary-color);
        }
        
        .btn-edit:hover {
            color: var(--warning-color);
        }
        
        .btn-delete:hover {
            color: var(--danger-color);
        }

        /* Modal para edição de registro */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            overflow: auto;
        }
        
        .modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
            width: 80%;
            max-width: 600px;
            animation: modalFadeIn 0.3s;
        }
        
        @keyframes modalFadeIn {
            from {opacity: 0; transform: translateY(-50px);}
            to {opacity: 1; transform: translateY(0);}
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .modal-header h3 {
            margin: 0;
            color: var(--primary-color);
        }
        
        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: var(--danger-color);
        }
        
        .back-to-top {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: var(--primary-color);
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease-in-out;
            z-index: 999;
        }

        .back-to-top.visible {
            opacity: 1;
            visibility: visible;
            transform: scale(1.1);
        }

        .back-to-top:hover {
            background-color: var(--secondary-color);
            transform: scale(1.2);
        }

        .responsive-table {
            overflow-x: auto;
        }

        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
            }
            
            .btn-group {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
            
            .page-container {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="page-container">
        <div class="container">
            <h2><i class="fas fa-cogs"></i> Cadastro de Equipamentos</h2>
            
            <?php if ($show_success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success_message); ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
            <?php endif; ?>
            
            <form id="equipmentForm" method="POST" action="">
                <div class="form-group">
                    <label for="mac"><i class="fas fa-barcode"></i> Equipamento MAC: *</label>
                    <textarea id="mac" name="mac" placeholder="Digite um MAC por linha" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="hfc"><i class="fas fa-network-wired"></i> Equipamento HFC: *</label>
                    <textarea id="hfc" name="hfc" placeholder="Digite um HFC por linha (na mesma ordem dos MACs)" required></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="descricao"><i class="fas fa-tag"></i> Descrição Técnico: *</label>
                        <select id="descricao" name="descricao" required>
                            <option value="">Escolha um item</option>
                            <option value="INTELBRAS FIBER">INTELBRAS FIBER</option>
                            <option value="IPTV">IPTV</option>
                            <option value="NOKIA KIT">NOKIA KIT</option>
                            <option value="NOKIA REUSO">NOKIA REUSO</option>
                            <option value="ONU FIBER HOME">ONU FIBER HOME</option>
                            <option value="REUSO">REUSO</option>
                            <option value="SWITCH">SWITCH</option>
                            <option value="DROP 1KM">DROP 1KM</option>
                            <option value="TAG">TAG</option>
                            <option value="CONECTOR">CONECTOR</option>
                            <option value="ESTICADOR">ESTICADOR</option>
                            <option value="CORDÃO">CORDÃO</option>
                            <option value="ROSETA">ROSETA</option>
                            <option value="ABRAÇADEIRA">ABRAÇADEIRA</option>
                            <option value="FIXA CABO">FIXA CABO</option>
                            <option value="CONECTOR RJ 45">CONECTOR RJ 45</option>
                            <option value="CABO LAN">CABO LAN</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="tecn_origem"><i class="fas fa-user-hard-hat"></i> Técnico Origem: *</label>
                        <select id="tecn_origem" name="tecn_origem" required>
                            <option value="">Escolha um técnico</option>
                            <?php foreach ($tecnicos as $tecnico): ?>
                                <option value="<?php echo htmlspecialchars($tecnico); ?>"><?php echo htmlspecialchars($tecnico); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="data_recebimento"><i class="fas fa-calendar-alt"></i> Data de Recebimento:</label>
                        <input type="date" id="data_recebimento" name="data_recebimento" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="rma"><i class="fas fa-hashtag"></i> RMA:</label>
                        <input type="text" id="rma" name="rma" placeholder="RMA">
                    </div>
                </div>
                
                <div class="btn-group">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Salvar
                    </button>
                    <button type="button" class="btn btn-primary" onclick="exportExcel()">
                        <i class="fas fa-file-excel"></i> Exportar Excel
                    </button>
                    <button type="reset" class="btn btn-warning">
                        <i class="fas fa-undo"></i> Limpar
                    </button>
                    <button type="button" class="btn btn-primary" onclick="scrollToTable()">
                        <i class="fas fa-table"></i> Ver Registros
                    </button>
                    <button type="button" class="btn btn-info" onclick="window.location.href='index.php';">
                        <i class="fas fa-arrow-up"></i> Voltar ao Início
                    </button>
                </div>
            </form>
            
            <!-- Formulário oculto para exportação -->
            <form id="exportForm" method="POST" style="display: none;">
                <input type="hidden" name="export" value="1">
            </form>
        </div>
        
        <div class="spreadsheet">
            <h2><i class="fas fa-table"></i> Registros de Equipamentos (<?php echo count($registros); ?>)</h2>
            
            <div class="search-container">
                <input type="text" id="searchInput" class="search-input" placeholder="Pesquisar por MAC, HFC, descrição...">
                <button class="btn btn-primary" onclick="searchTable()">
                    <i class="fas fa-search"></i> Buscar
                </button>
            </div>
            
            <div class="responsive-table">
                <table id="equipmentTable">
                    <thead>
                        <tr>
                            <th>MAC</th>
                            <th>HFC</th>
                            <th>Descrição</th>
                            <th>Técnico</th>
                            <th>Data</th>
                            <th>RMA</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody id="dataBody">
                        <?php foreach ($registros as $row): ?>
                            <tr data-id="<?php echo $row['id']; ?>">
                                <td><?php echo htmlspecialchars($row['mac'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['hfc'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['descricao'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['tecn_origem'] ?? ''); ?></td>
                                <td><?php echo !empty($row['data_recebimento']) ? date('d/m/Y', strtotime($row['data_recebimento'])) : ''; ?></td>
                                <td><?php echo htmlspecialchars($row['rma'] ?? ''); ?></td>
                                <td class="action-btns">
                                    <button class="btn-icon btn-edit" onclick="openEditModal(<?php echo $row['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-icon btn-delete" onclick="confirmDelete(<?php echo $row['id']; ?>)">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if (count($registros) > 10): ?>
            <div class="pagination">
                <button onclick="changePage(1)" class="active">1</button>
                <button onclick="changePage(2)">2</button>
                <button onclick="changePage(3)">3</button>
                <button>...</button>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Botão Voltar ao Início -->
    <a href="#" class="back-to-top" id="backToTop">
        <i class="fas fa-arrow-up"></i>
    </a>
    
    <!-- Modal de edição -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Editar Registro</h3>
                <span class="close" onclick="closeEditModal()">&times;</span>
            </div>
            <form id="editForm" method="POST">
                <input type="hidden" id="id_edit" name="id">
                <input type="hidden" name="update" value="1">
                
                <div class="form-group">
                    <label for="mac_edit"><i class="fas fa-barcode"></i> Equipamento MAC:</label>
                    <input type="text" id="mac_edit" name="mac_edit" required>
                </div>
                
                <div class="form-group">
                    <label for="hfc_edit"><i class="fas fa-network-wired"></i> Equipamento HFC:</label>
                    <input type="text" id="hfc_edit" name="hfc_edit" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                    <div class="form-group">
                        <label for="descricao_edit"><i class="fas fa-tag"></i> Descrição Técnico:</label>
                        <select id="descricao_edit" name="descricao_edit" required>
                            <option value="">Escolha um item</option>
                            <option value="INTELBRAS FIBER">INTELBRAS FIBER</option>
                            <option value="IPTV">IPTV</option>
                            <option value="NOKIA KIT">NOKIA KIT</option>
                            <option value="NOKIA REUSO">NOKIA REUSO</option>
                            <option value="ONU FIBER HOME">ONU FIBER HOME</option>
                            <option value="REUSO">REUSO</option>
                            <option value="SWITCH">SWITCH</option>
                            <option value="DROP 1KM">DROP 1KM</option>
                            <option value="TAG">TAG</option>
                            <option value="CONECTOR">CONECTOR</option>
                            <option value="ESTICADOR">ESTICADOR</option>
                            <option value="CORDÃO">CORDÃO</option>
                            <option value="ROSETA">ROSETA</option>
                            <option value="ABRAÇADEIRA">ABRAÇADEIRA</option>
                            <option value="FIXA CABO">FIXA CABO</option>
                            <option value="CONECTOR RJ 45">CONECTOR RJ 45</option>
                            <option value="CABO LAN">CABO LAN</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="tecn_origem_edit"><i class="fas fa-user-hard-hat"></i> Técnico Origem:</label>
                        <select id="tecn_origem_edit" name="tecn_origem_edit" required>
                            <option value="">Escolha um técnico</option>
                            <?php foreach ($tecnicos as $tecnico): ?>
                                <option value="<?php echo htmlspecialchars($tecnico); ?>"><?php echo htmlspecialchars($tecnico); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="data_recebimento_edit"><i class="fas fa-calendar-alt"></i> Data de Recebimento:</label>
                        <input type="date" id="data_recebimento_edit" name="data_recebimento_edit">
                    </div>
                    
                    <div class="form-group">
                        <label for="rma_edit"><i class="fas fa-hashtag"></i> RMA:</label>
                        <input type="text" id="rma_edit" name="rma_edit" placeholder="RMA">
                    </div>
                </div>
                
                <div class="btn-group">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Salvar Alterações
                    </button>
                    <button type="button" class="btn btn-danger" onclick="closeEditModal()">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Formulário para exclusão -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" id="delete_id" name="id">
        <input type="hidden" name="delete" value="1">
    </form>
    
    <script>
    // Abrir modal de edição
    function openEditModal(id) {
        const modal = document.getElementById('editModal');
        const row = document.querySelector(`tr[data-id="${id}"]`);
        
        if (row) {
            document.getElementById('id_edit').value = id;
            document.getElementById('mac_edit').value = row.cells[0].textContent.trim();
            document.getElementById('hfc_edit').value = row.cells[1].textContent.trim();
            
            const descricaoDropdown = document.getElementById('descricao_edit');
            const tecnicoDropdown = document.getElementById('tecn_origem_edit');
            const descricaoValue = row.cells[2].textContent.trim();
            const tecnicoValue = row.cells[3].textContent.trim();
            
            // Selecionar descrição no dropdown
            Array.from(descricaoDropdown.options).forEach(option => {
                option.selected = option.value === descricaoValue;
            });
            
            // Selecionar técnico no dropdown
            Array.from(tecnicoDropdown.options).forEach(option => {
                option.selected = option.value === tecnicoValue;
            });
            
            // Formatar data para input type="date"
            const dataParts = row.cells[4].textContent.trim().split('/');
            if (dataParts.length === 3) {
                const formattedDate = `${dataParts[2]}-${dataParts[1]}-${dataParts[0]}`;
                document.getElementById('data_recebimento_edit').value = formattedDate;
            }
            
            document.getElementById('rma_edit').value = row.cells[5].textContent.trim();
        }
        
        modal.style.display = 'block';
    }
    
    // Fechar modal
    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
    }
    
    // Confirmar exclusão
    function confirmDelete(id) {
        if (confirm('Deseja realmente excluir este registro?')) {
            document.getElementById('delete_id').value = id;
            document.getElementById('deleteForm').submit();
        }
    }
    
    // Função para exportar Excel
    function exportExcel() {
        document.getElementById('exportForm').submit();
    }
    
    // Função para scroll até registros
    function scrollToTable() {
        const tableSection = document.querySelector('.spreadsheet');
        if (tableSection) {
            window.scrollTo({
                top: tableSection.offsetTop,
                behavior: 'smooth'
            });
        }
    }
    
    // Função para voltar ao início
    function backToTop() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }
    
    // Exibir botão "Voltar ao Início"
    window.addEventListener('scroll', function() {
        const backToTopButton = document.getElementById('backToTop');
        if (window.pageYOffset > 300) {
            backToTopButton.classList.add('visible');
        } else {
            backToTopButton.classList.remove('visible');
        }
    });

       
        // Fechar o modal ao clicar fora dele
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('editModal');
            if (event.target === modal) {
                closeEditModal();
            }
        });
        
        // Verificar número de linhas de MAC e HFC
        document.getElementById('equipmentForm').addEventListener('submit', function(e) {
            const macLines = document.getElementById('mac').value.trim().split('\n').filter(line => line.trim() !== '');
            const hfcLines = document.getElementById('hfc').value.trim().split('\n').filter(line => line.trim() !== '');
            
            if (macLines.length !== hfcLines.length) {
                e.preventDefault();
                alert('O número de linhas de MAC e HFC deve ser o mesmo!');
            }
        });
        
        // Destacar linhas da tabela ao passar o mouse
        const tableRows = document.querySelectorAll('#equipmentTable tbody tr');
        tableRows.forEach(row => {
            row.addEventListener('mouseover', function() {
                this.style.backgroundColor = 'rgba(52, 152, 219, 0.1)';
            });
            
            row.addEventListener('mouseout', function() {
                this.style.backgroundColor = '';
            });
        });
    </script>
</body>
</html>