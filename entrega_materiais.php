<?php
session_start();
require_once 'db.php';
$mensagem = '';
$mensagemTipo = '';
$tecnicos = [];
$materiais = [];

// Recupera valores do formulário da sessão
$valorMac = $_SESSION['form_equipamento_mac'] ?? '';
$valorTecnico = $_SESSION['form_tecnico'] ?? '';
unset($_SESSION['form_equipamento_mac'], $_SESSION['form_tecnico']);

try {
    $pdo = new PDO($dsn, $username, $password, $options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Carregar técnicos
    $stmt = $pdo->prepare("SELECT id, nome FROM equipe ORDER BY nome ASC");
    $stmt->execute();
    $tecnicos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mensagem flash
    if (isset($_SESSION['mensagem'])) {
        $mensagem = $_SESSION['mensagem'];
        $mensagemTipo = $_SESSION['mensagem_tipo'] ?? '';
        unset($_SESSION['mensagem'], $_SESSION['mensagem_tipo']);
    }

    // Processar formulário
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'salvar':
                if (empty($_POST['equipamento_mac']) || empty($_POST['tecnico'])) {
                    throw new Exception("Por favor, preencha todos os campos obrigatórios.");
                }
                $macsRaw = trim($_POST['equipamento_mac']);
                $macs = explode("\n", $macsRaw);
                $macs = array_map('trim', $macs);
                $macs = array_filter($macs);

                if (empty($macs)) {
                    throw new Exception("Por favor, informe pelo menos um MAC válido.");
                }
                $tecnico = trim(strip_tags($_POST['tecnico']));
                $stmtCheck = $pdo->prepare("SELECT id FROM equipe WHERE nome = ?");
                $stmtCheck->execute([$tecnico]);
                if (!$stmtCheck->fetch()) {
                    throw new Exception("Técnico não encontrado no sistema.");
                }
                $pdo->beginTransaction();
                $stmt = $pdo->prepare(
                    "INSERT INTO entregas_materiais (mac, tecnico, data_recebimento, hfc, descricao, nota_fiscal, rma) VALUES (?, ?, NOW(), ?, ?, ?, ?)"
                );
                foreach ($macs as $mac) {
                    $mac = strtoupper(preg_replace('/[^A-Fa-f0-9:]/', '', $mac));
                    if (empty($mac)) continue;
                    $stmtOnu = $pdo->prepare("SELECT hfc, descricao, nota_fiscal, rma FROM cadastro_onu WHERE mac = ?");
                    $stmtOnu->execute([$mac]);
                    $onu = $stmtOnu->fetch(PDO::FETCH_ASSOC);
                    $hfc = $onu['hfc'] ?? '';
                    $descricao = $onu['descricao'] ?? 'Sem registro no sistema';
                    $nota_fiscal = $onu['nota_fiscal'] ?? '';
                    $rma = $onu['rma'] ?? '';
                    $stmt->execute([$mac, $tecnico, $hfc, $descricao, $nota_fiscal, $rma]);
                }
                $pdo->commit();
                $_SESSION['mensagem'] = "Entrega salva com sucesso!";
                $_SESSION['mensagem_tipo'] = "success";
                // Salva campos do formulário na sessão para manter ao recarregar
                $_SESSION['form_equipamento_mac'] = $_POST['equipamento_mac'];
                $_SESSION['form_tecnico'] = $_POST['tecnico'];
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            case 'excluir':
                if (!empty($_POST['equipamento_mac'])) {
                    $macsRaw = trim($_POST['equipamento_mac']);
                    $macs = explode("\n", $macsRaw);
                    $macs = array_map('trim', $macs);
                    $macs = array_filter($macs);
                    if (!empty($macs)) {
                        $pdo->beginTransaction();
                        $stmt = $pdo->prepare("DELETE FROM entregas_materiais WHERE mac = ?");
                        foreach ($macs as $mac) {
                            $mac = preg_replace('/[^A-Fa-f0-9:]/', '', $mac);
                            if (!empty($mac)) {
                                $stmt->execute([$mac]);
                            }
                        }
                        $pdo->commit();
                        $_SESSION['mensagem'] = "Registros excluídos com sucesso!";
                        $_SESSION['mensagem_tipo'] = "success";
                    } else {
                        $_SESSION['mensagem'] = "Nenhum MAC válido para excluir.";
                        $_SESSION['mensagem_tipo'] = "danger";
                    }
                } else {
                    $_SESSION['mensagem'] = "Informe os MACs a serem excluídos.";
                    $_SESSION['mensagem_tipo'] = "danger";
                }
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            case 'exportar_excel':
                if (!empty($_POST['equipamento_mac'])) {
                    $macsRaw = trim($_POST['equipamento_mac']);
                    $macs = explode("\n", $macsRaw);
                    $macs = array_map('trim', $macs);
                    $macs = array_filter($macs);
                    if (!empty($macs)) {
                        header('Content-Type: application/vnd.ms-excel');
                        header('Content-Disposition: attachment;filename="entregas_materiais.xls"');
                        header('Cache-Control: max-age=0');
                        echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Dados de Entrega</title></head><body>";
                        echo "<table border='1'><thead><tr>
                            <th>MAC</th>
                            <th>HFC</th>
                            <th>Descrição</th>
                            <th>Data de Entrega</th>
                            <th>Técnico</th>
                            <th>Nota Fiscal</th>
                            <th>RMA</th>
                        </tr></thead><tbody>";
                        foreach ($macs as $mac) {
                            $mac = preg_replace('/[^A-Fa-f0-9:]/', '', $mac);
                            if (!empty($mac)) {
                                $stmt = $pdo->prepare("SELECT * FROM cadastro_onu WHERE mac = ?");
                                $stmt->execute([$mac]);
                                $item = $stmt->fetch(PDO::FETCH_ASSOC);
                                if ($item) {
                                    echo "<tr>
                                        <td>" . htmlspecialchars($item['mac']) . "</td>
                                        <td>" . htmlspecialchars($item['hfc'] ?? 'N/A') . "</td>
                                        <td>" . htmlspecialchars($item['descricao'] ?? 'Sem descrição') . "</td>
                                        <td>" . htmlspecialchars($item['data_recebimento'] ?? date('d/m/Y')) . "</td>
                                        <td>" . htmlspecialchars($_POST['tecnico'] ?? 'N/A') . "</td>
                                        <td>" . htmlspecialchars($item['nota_fiscal'] ?? 'N/A') . "</td>
                                        <td>" . htmlspecialchars($item['rma'] ?? 'N/A') . "</td>
                                    </tr>";
                                } else {
                                    echo "<tr>
                                        <td>" . htmlspecialchars($mac) . "</td>
                                        <td colspan='6'>Dados não encontrados</td>
                                    </tr>";
                                }
                            }
                        }
                        echo "</tbody></table></body></html>";
                        exit;
                    }
                }
                $_SESSION['mensagem'] = "Informe os MACs para exportar.";
                $_SESSION['mensagem_tipo'] = "danger";
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
        }
    }
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $mensagem = "Erro de banco de dados: " . $e->getMessage();
    $mensagemTipo = "danger";
} catch (Exception $e) {
    $mensagem = $e->getMessage();
    $mensagemTipo = "danger";
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Entrega de Materiais - DC TELECOM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0056b3;
            --secondary-color: #007bff;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --border-color: #e1e4e8;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
            background: #f4f6fb;
            color: #222;
            line-height: 1.5;
        }
        .navbar {
            background: var(--primary-color);
            color: #fff;
            padding: 1.2rem;
            text-align: center;
            font-weight: 600;
            font-size: 1.3rem;
            letter-spacing: 0.5px;
            margin-bottom: 18px;
        }
        .container {
            max-width: 1200px;
            margin: 30px auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 6px 24px rgba(0,0,0,0.08);
            padding: 24px;
            display: flex;
            gap: 24px;
            flex-wrap: wrap;
        }
        .form-container, .preview-container {
            flex: 1;
            min-width: 320px;
            border-radius: 12px;
            background: #fafbfc;
            box-shadow: 0 2px 10px rgba(0,0,0,0.07);
            padding: 20px;
        }
        .card-header {
            padding-bottom: 10px;
            margin-bottom: 10px;
            border-bottom: 1px solid var(--border-color);
        }
        .card-header h2 {
            color: var(--primary-color);
            font-size: 1.24rem;
        }
        .alert {
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 5px;
            font-weight: 600;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .form-group {
            margin-bottom: 14px;
        }
        .form-group label {
            display: block;
            margin-bottom: 4px;
            font-weight: 600;
            color: #444;
        }
        .form-control {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            font-size: 14px;
            background: #fff;
            transition: border-color 0.3s;
        }
        .form-control:focus {
            border-color: var(--secondary-color);
            outline: none;
            box-shadow: 0 0 0 2px rgba(0,123,255,0.13);
        }
        .btn-group {
            display: flex;
            gap: 9px;
            flex-wrap: wrap;
        }
        .btn {
            border-radius: 7px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.05);
            font-size: 14px;
            font-weight: 600;
            padding: 9px 17px;
            border: none;
            cursor: pointer;
            transition: background 0.2s, box-shadow 0.18s;
            margin-bottom: 2px;
            display: inline-block;
        }
        .btn-primary { background: var(--secondary-color); color: #fff; }
        .btn-primary:hover { background: var(--primary-color); }
        .btn-success { background: var(--success-color); color: #fff; }
        .btn-info { background: var(--info-color); color: #fff; }
        .btn-danger { background: var(--danger-color); color: #fff; }
        .btn-secondary { background: #bfc7d1; color: #333; }
        .btn:hover { box-shadow: 0 3px 12px rgba(0,0,0,0.11); filter: brightness(0.97); }
        .table-container { margin-top: 13px; overflow-x: auto; }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            background: #fff;
        }
        table th, table td {
            padding: 6px 8px;
            border: 1px solid var(--border-color);
            line-height: 1.3;
        }
        table th {
            background: var(--secondary-color);
            color: #fff;
            font-weight: 700;
            letter-spacing: 0.3px;
        }
        table tr {
            transition: background 0.18s;
        }
        table tr:hover {
            background: #eef6ff;
        }
        table tr:nth-child(even) {
            background: #f8fafd;
        }
        .header {
            text-align: center;
            margin-bottom: 14px;
        }
        .header h1 { font-size: 22px; margin-bottom: 3px; }
        .header h2 { font-size: 16px; color: #3c3c3c; margin-bottom: 2px; }
        .header h3 { font-size: 13px; color: #777; margin-bottom: 7px; }
        .footer { margin-top: 18px; text-align: center; }
        .signature {
            margin-top: 28px;
            display: flex;
            justify-content: space-between;
            gap: 8%;
        }
        .signature div {
            width: 44%;
            text-align: center;
        }
        .signature p {
            border-top: 1px solid #000;
            margin-top: 35px;
            padding-top: 5px;
            font-size: 13px;
        }
        .loading {
            display: none;
            text-align: center;
            padding: 16px;
        }
        @media (max-width: 900px) {
            .container { flex-direction: column; gap: 18px; }
            .form-container, .preview-container { width: 100%; }
            .signature { flex-direction: column; align-items: center; }
            .signature div { width: 80%; margin-bottom: 25px; }
        }
        @media print {
            body * { visibility: hidden; }
            .print-area, .print-area * { visibility: visible; }
            .print-area {
                position: absolute; left: 0; top: 0; width: 100%; padding: 15mm; background: #fff;
            }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="navbar">
        DC TELECOM - Sistema de Gestão de Materiais
    </div>
    <div class="container">
        <!-- Formulário -->
        <div class="form-container no-print">
            <div class="card-header">
                <h2><i class="fas fa-clipboard-list"></i> Cadastro de Entrega de Materiais</h2>
            </div>
            <?php if (!empty($mensagem)): ?>
                <div class="alert alert-<?= $mensagemTipo === 'success' ? 'success' : 'danger' ?>">
                    <i class="fas fa-<?= $mensagemTipo === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                    <?= htmlspecialchars($mensagem) ?>
                </div>
            <?php endif; ?>
            <form id="entregaForm" method="POST" action="">
                <div class="form-group">
                    <label for="equipamento_mac">
                        <i class="fas fa-barcode"></i> Equipamento MAC (um por linha):
                    </label>
                    <textarea id="equipamento_mac" name="equipamento_mac" class="form-control" rows="5" placeholder="Digite os MACs aqui, um por linha..." required></textarea>
                </div>
                <div class="form-group">
                    <label for="tecnico">
                        <i class="fas fa-user-gear"></i> Técnico Responsável:
                    </label>
                    <select id="tecnico" name="tecnico" class="form-control" required>
                        <option value="" disabled selected>Escolha um técnico</option>
                        <?php foreach ($tecnicos as $tecnico): ?>
                            <option value="<?= htmlspecialchars($tecnico['nome']) ?>"><?= htmlspecialchars($tecnico['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="btn-group">
                    <button type="submit" name="action" value="salvar" class="btn btn-primary">
                        <i class="fas fa-save"></i> Salvar Entrega
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="limparFormulario()">
                        <i class="fas fa-undo"></i> Limpar
                    </button>
                    <button type="submit" name="action" value="excluir" class="btn btn-danger" onclick="return confirmarExclusao()">
                        <i class="fas fa-trash"></i> Excluir
                    </button>
                </div>
            </form>
            <div class="btn-group" style="margin-top: 13px;">
                <button type="button" class="btn btn-success" onclick="imprimirDocumento()">
                    <i class="fas fa-print"></i> Imprimir
                </button>
                <button type="button" class="btn btn-info" onclick="exportarExcel()">
                    <i class="fas fa-file-excel"></i> Exportar Excel
                </button>
                <button type="button" class="btn btn-secondary" onclick="window.location.href='index.php'">
                    <i class="fas fa-home"></i> Voltar ao Início
                </button>
            </div>
        </div>
        <!-- Visualização da entrega -->
        <div class="preview-container print-area">
            <div class="header">
                <h1>DADOS DOS EQUIPAMENTOS</h1>
                <h2>Requisição de materiais / Vínculo ao Técnico</h2>
                <h3>DC TELECOM</h3>
                <p id="data-atual"><?= date("d/m/Y") ?></p>
                <p id="total-itens">TOTAL DE ITENS: 0</p>
            </div>
            <div id="loading" class="loading">
                <i class="fas fa-spinner fa-spin"></i> Carregando dados...
            </div>
            <div class="table-container">
                <table id="tabela-mac">
                    <thead>
                        <tr>
                            <th>MAC</th>
                            <th>HFC</th>
                            <th>Descrição</th>
                            <th>Data de Entrega</th>
                            <th>Nota Fiscal</th>
                            <th>RMA</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="6" style="text-align: center;">Digite os MACs para visualizar os dados.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="footer">
                <p id="tecnico-responsavel">Técnico Responsável: </p>
                <div class="signature">
                    <div>
                        <p>Assinatura do Técnico</p>
                    </div>
                    <div>
                        <p>Assinatura do Supervisor</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Formulário invisível para exportação Excel -->
    <form id="formExportar" method="POST" action="" style="display:none;">
        <input type="hidden" name="action" value="exportar_excel">
        <input type="hidden" id="export_macs" name="equipamento_mac" value="">
        <input type="hidden" id="export_tecnico" name="tecnico" value="">
    </form>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const macsInput = document.getElementById('equipamento_mac');
            const tecnicoSelect = document.getElementById('tecnico');
            verificarDados();
            macsInput.addEventListener('input', debounce(verificarDados, 480));
            tecnicoSelect.addEventListener('change', verificarDados);
            macsInput.focus();
        });

        function verificarDados() {
            const macs = document.getElementById('equipamento_mac').value.trim();
            const tecnico = document.getElementById('tecnico').value;
            document.getElementById('tecnico-responsavel').innerText = `Técnico Responsável: ${tecnico || ''}`;
            if (macs !== '') {
                buscarDados();
            } else {
                limparTabela();
            }
        }

        function buscarDados() {
            const macs = document.getElementById('equipamento_mac').value.trim();
            if (!macs) return;
            document.getElementById('loading').style.display = 'block';
            const formData = new FormData();
            formData.append('macs', macs);
            fetch('buscar_mac.php', {
                method: 'POST',
                body: formData,
            })
            .then(response => {
                if (!response.ok) throw new Error('Erro na resposta do servidor');
                return response.json();
            })
            .then(data => {
                document.getElementById('loading').style.display = 'none';
                atualizarTabela(data);
            })
            .catch(error => {
                document.getElementById('loading').style.display = 'none';
                const tabela = document.getElementById('tabela-mac').querySelector('tbody');
                tabela.innerHTML = `<tr><td colspan="6" style="text-align:center; color:red;">
                    <i class="fas fa-exclamation-triangle"></i> Erro ao buscar dados. Tente novamente.
                </td></tr>`;
                document.getElementById('total-itens').innerText = 'TOTAL DE ITENS: 0';
            });
        }

        function atualizarTabela(data) {
            const tabela = document.getElementById('tabela-mac').querySelector('tbody');
            tabela.innerHTML = '';
            if (Array.isArray(data) && data.length > 0) {
                data.forEach((item) => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${escapeHtml(item.mac)}</td>
                        <td>${escapeHtml(item.hfc || 'N/A')}</td>
                        <td>${escapeHtml(item.descricao || 'Sem descrição')}</td>
                        <td>${escapeHtml(item.data_entrega || formatDate(new Date()))}</td>
                        <td>${escapeHtml(item.nota_fiscal || 'N/A')}</td>
                        <td>${escapeHtml(item.rma || 'N/A')}</td>
                    `;
                    tabela.appendChild(row);
                });
                document.getElementById('total-itens').innerText = `TOTAL DE ITENS: ${data.length}`;
            } else {
                tabela.innerHTML = `<tr>
                    <td colspan="6" style="text-align:center;">
                        <i class="fas fa-search"></i> Nenhum dado encontrado para os MACs informados.
                    </td>
                </tr>`;
                document.getElementById('total-itens').innerText = 'TOTAL DE ITENS: 0';
            }
        }

        function limparTabela() {
            const tabela = document.getElementById('tabela-mac').querySelector('tbody');
            tabela.innerHTML = `<tr>
                <td colspan="6" style="text-align: center;">Digite os MACs para visualizar os dados.</td>
            </tr>`;
            document.getElementById('total-itens').innerText = 'TOTAL DE ITENS: 0';
        }

        function limparFormulario() {
            document.getElementById('equipamento_mac').value = '';
            document.getElementById('tecnico').selectedIndex = 0;
            limparTabela();
            document.getElementById('tecnico-responsavel').innerText = 'Técnico Responsável: ';
        }

        function confirmarExclusao() {
            const macs = document.getElementById('equipamento_mac').value.trim();
            if (!macs) {
                alert('Informe pelo menos um MAC para excluir.');
                return false;
            }
            return confirm('Tem certeza de que deseja excluir os registros dos MACs informados?');
        }

        function imprimirDocumento() {
            const tbody = document.getElementById('tabela-mac').querySelector('tbody');
            const temDados = !tbody.textContent.includes('Digite os MACs');
            if (!temDados) {
                alert('Adicione pelo menos um MAC antes de imprimir.');
                return;
            }
            const tecnico = document.getElementById('tecnico').value;
            if (!tecnico) {
                alert('Selecione um técnico responsável antes de imprimir.');
                return;
            }
            window.print();
        }

        function exportarExcel() {
            const macs = document.getElementById('equipamento_mac').value.trim();
            const tecnico = document.getElementById('tecnico').value;
            if (!macs) {
                alert('Adicione pelo menos um MAC antes de exportar para Excel.');
                return;
            }
            document.getElementById('export_macs').value = macs;
            document.getElementById('export_tecnico').value = tecnico;
            document.getElementById('formExportar').submit();
        }

        function formatDate(date) {
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            return `${day}/${month}/${year}`;
        }

        function escapeHtml(text) {
            if (text === null || text === undefined) return '';
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;',
            };
            return text.toString().replace(/[&<>"']/g, m => map[m]);
        }

        function debounce(func, wait) {
            let timeout;
            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), wait);
            };
        }
    </script>
</body>
</html>