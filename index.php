<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
session_start();
require_once 'db.php';

// Timeout de sessão (30 minutos)
$timeout = 1800;
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $timeout)) {
    session_unset();
    session_destroy();
    header("Location: login.php?timeout=1");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();

if (
    !isset($_SESSION['usuario']) || empty($_SESSION['usuario']) ||
    !isset($_SESSION['usuario_id']) || !is_numeric($_SESSION['usuario_id']) || $_SESSION['usuario_id'] <= 0
) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// Regeneração de ID de sessão para reforçar segurança pós-login
if (!isset($_SESSION['REGENERATED'])) {
    session_regenerate_id(true);
    $_SESSION['REGENERATED'] = true;
}

// Controle de usuário
$usuarioLogado = $_SESSION['usuario'];
$nomeUsuario = $_SESSION['nome'] ?? $usuarioLogado;
$nivelAcesso = $_SESSION['nivel_acesso'] ?? 'operador';
$usuarioId = (int)$_SESSION['usuario_id'];
$isAdmin = ($nivelAcesso === 'admin');

// Buscar avatar do usuário com tratamento de erros (PDO)
$avatar = null;
if ($usuarioId > 0 && $conn instanceof PDO) {
    try {
        $sql = "SELECT avatar FROM usuarios WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$usuarioId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && !empty($row['avatar']) && @getimagesize($row['avatar'])) {
            $avatar = $row['avatar'];
        }
    } catch (PDOException $e) {
        error_log("Erro no banco: " . $e->getMessage());
    }
}

// Buscar contratos aguardando baixa
$contratos = [];
try {
    $stmt = $conn->query("SELECT id, numero_contrato, nome_assinante, nome_empresa, pdf_path, status_baixa FROM contratos WHERE status_baixa IS NULL OR status_baixa != 'baixado'");
    $contratos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $contratos = [];
    error_log("Erro ao buscar contratos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Integrado de Gerenciamento</title>
    <meta name="description" content="Sistema Integrado de Gerenciamento - Controle de ONUs, Equipes, Materiais e Contratos">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --color-primary: #2c3e50;
            --color-secondary: #ecf0f1;
            --color-accent1: #3498db;
            --color-accent2: #2ecc71;
            --color-accent3: #e74c3c;
            --color-accent4: #f39c12;
            --color-accent5: #8e44ad;
            --color-accent6: #1abc9c;
            --color-accent7: #34495e;
            --color-accent8: #16a085;
            --color-text: #333;
            --color-text-light: #7f8c8d;
            --transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background: linear-gradient(135deg, #f6f8fa 0%, #e9eef2 100%); min-height: 100vh; color: var(--color-text); }
        
        /* Mensagens de Alerta */
        .alert {
            padding: 12px 16px;
            margin: 10px 0;
            border-radius: 8px;
            font-weight: 500;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-detalhes {
            margin-top: 8px;
            font-size: 0.9em;
            max-height: 200px;
            overflow-y: auto;
            padding: 8px;
            background: rgba(255,255,255,0.3);
            border-radius: 4px;
        }
        .alert-detalhes div {
            margin: 2px 0;
            font-family: monospace;
            font-size: 0.85em;
        }
        
        .header { background-color: var(--color-primary); color: white; padding: 1.5rem 0; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);}
        .header-content { width: 90%; max-width: 1400px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center;}
        .logo { display: flex; align-items: center; gap: 15px;}
        .logo-icon { font-size: 2rem; color: var(--color-accent1);}
        .app-title { font-weight: 600; font-size: 1.5rem;}
        .header-actions {
            display: flex;
            align-items: center;
            gap: 24px;
        }
        .btn-relatorio-header {
            background: linear-gradient(90deg, var(--color-accent2), var(--color-accent1));
            color: #fff;
            border: none;
            border-radius: 22px;
            font-size: 1rem;
            font-weight: 600;
            padding: 0.7rem 1.6rem;
            cursor: pointer;
            transition: background .2s;
            box-shadow: 0 2px 10px rgba(44, 62, 80, 0.08);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }
        .btn-relatorio-header i {
            margin-right: 8px;
        }
        .btn-relatorio-header:hover {
            background: linear-gradient(90deg, var(--color-accent1), var(--color-accent2));
            color: #fff;
        }
        .user-info { display: flex; align-items: center; gap: 15px;}
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; background-color: var(--color-accent1); display: flex; align-items: center; justify-content: center; font-weight: bold; cursor: pointer;}
        .container { width: 90%; max-width: 1400px; margin: 2rem auto; }
        .menu-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(270px, 1fr));
            gap: 32px;
            margin-top: 2.5rem;
        }
        .menu-item { 
            background: white;
            border-radius: 18px;
            box-shadow: 0 8px 28px rgba(44, 62, 80, 0.09);
            transition: var(--transition);
            cursor: pointer;
            height: 210px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            border-top: 7px solid var(--color-accent1);
            border-bottom: 2.5px solid var(--color-accent2);
            position: relative;
            overflow: hidden;
        }
        .menu-item:hover {
            transform: translateY(-10px) scale(1.025);
            box-shadow: 0 18px 40px rgba(44, 62, 80, 0.13);
            border-color: var(--color-accent4);
        }
        .menu-header {
            padding: 1.4rem 1.2rem 0.5rem 1.2rem;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }
        .menu-icon {
            font-size: 2.2rem;
            margin-bottom: 9px;
        }
        .menu-onu .menu-icon { color: var(--color-accent1);}
        .menu-equipe .menu-icon { color: var(--color-accent2);}
        .menu-materiais .menu-icon { color: var(--color-accent4);}
        .menu-contratos .menu-icon { color: var(--color-accent5);}
        .menu-miscelaneas .menu-icon { color: var(--color-accent6);}
        .menu-consulta .menu-icon { color: var(--color-accent8);}
        .menu-title { font-size: 1.13rem; font-weight: 700; margin-bottom: 2px;}
        .menu-description { color: var(--color-text-light); font-size: 0.98rem; margin-bottom: 10px;}
        .action-button {
            background: linear-gradient(90deg, var(--color-accent2), var(--color-accent1));
            color: white;
            border: none;
            border-radius: 18px;
            padding: 8px 22px;
            font-size: 1rem;
            cursor: pointer;
            transition: background .2s;
            font-weight: 600;
            box-shadow: 0 2px 10px rgba(44, 62, 80, 0.06);
        }
        .action-button:hover {
            background: linear-gradient(90deg, var(--color-accent1), var(--color-accent2));
        }
        .logout-button {
            background: linear-gradient(90deg, var(--color-accent3), var(--color-accent5));
            color: white;
            border: none;
            border-radius: 18px;
            padding: 6px 14px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: background .2s;
            margin-left: 10px;
            text-decoration: none;
        }
        .logout-button:hover {
            background: linear-gradient(90deg, var(--color-accent5), var(--color-accent3));
        }
        .footer { background-color: var(--color-primary); color: white; padding: 1.5rem 0; margin-top: 3rem; text-align: center; font-size: 0.95rem;}
        
        /* Estilos para Upload e Contratos */
        .upload-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(44, 62, 80, 0.08);
            padding: 24px;
            margin: 32px 0;
        }
        .upload-form {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .upload-form label {
            font-weight: 600;
            color: var(--color-primary);
            margin-bottom: 8px;
            display: block;
            width: 100%;
        }
        .upload-form input[type="file"] {
            padding: 8px 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            background: #f8f9fa;
            font-size: 0.95rem;
            flex: 1;
            min-width: 250px;
        }
        .upload-form input[type="file"]:focus {
            border-color: var(--color-accent1);
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        .upload-btn {
            background: linear-gradient(90deg, var(--color-accent2), var(--color-accent1));
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }
        .upload-btn:hover {
            background: linear-gradient(90deg, var(--color-accent1), var(--color-accent2));
            transform: translateY(-1px);
        }
        
        .contrato-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            box-shadow: 0 4px 15px rgba(44,62,80,.08);
            border-radius: 12px;
            overflow: hidden;
            margin-top: 24px;
        }
        .contrato-table th, .contrato-table td {
            padding: 12px 8px;
            text-align: left;
        }
        .contrato-table th {
            background: var(--color-primary);
            color: #fff;
            font-weight: 600;
            font-size: 0.95rem;
        }
        .contrato-table tr:nth-child(even) {
            background: #f8f9fa;
        }
        .contrato-table tr:hover {
            background: #e3f2fd;
        }
        .contrato-table td {
            vertical-align: middle;
            font-size: 0.9rem;
        }
        
        .empresa-form {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .empresa-form select {
            padding: 4px 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.85rem;
            background: white;
        }
        .empresa-form button {
            background: var(--color-accent2);
            color: #fff;
            border: none;
            padding: 4px 8px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 28px;
            min-height: 28px;
        }
        .empresa-form button:hover {
            background: var(--color-accent1);
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 16px;
            font-size: 0.8rem;
            font-weight: 600;
            text-align: center;
        }
        .status-baixado {
            background: #d4edda;
            color: #155724;
        }
        .status-pendente {
            background: #f8d7da;
            color: #721c24;
        }
        
        .pdf-link {
            color: var(--color-accent1);
            text-decoration: none;
            font-weight: 500;
            padding: 4px 8px;
            border-radius: 4px;
            transition: all 0.2s;
        }
        .pdf-link:hover {
            background: var(--color-accent1);
            color: white;
        }
        
        .upload-individual {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .upload-individual input[type="file"] {
            font-size: 0.8rem;
            padding: 4px 6px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .upload-individual button {
            background: var(--color-accent2);
            color: #fff;
            border: none;
            padding: 4px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 500;
            align-self: flex-start;
        }
        .upload-individual button:hover {
            background: var(--color-accent1);
        }
        
        @media (max-width: 768px) {
            .header-content { flex-direction: column; gap: 15px;}
            .menu-container { grid-template-columns: 1fr; }
            .upload-form { flex-direction: column; align-items: stretch; }
            .upload-form input[type="file"] { min-width: auto; }
            .contrato-table { font-size: 0.8rem; }
            .contrato-table th, .contrato-table td { padding: 8px 4px; }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <span class="logo-icon"><i class="fas fa-project-diagram"></i></span>
                <span class="app-title">Sistema Integrado de Gerenciamento</span>
            </div>
            <div class="header-actions">
                <?php if ($isAdmin): ?>
                <a href="relatorio_financeiro.php" class="btn-relatorio-header" target="_blank">
                    <i class="fas fa-coins"></i> Relatório Financeiro
                </a>
                <a href="export_excel.php" class="btn-relatorio-header" style="background:linear-gradient(90deg, var(--color-accent4), var(--color-accent5));margin-left:5px;">
                    <i class="fas fa-file-excel"></i> Extrair Excel
                </a>
                <?php endif; ?>
                <div class="user-info">
                    <div class="user-avatar" title="Usuário logado">
                        <?php if ($avatar): ?>
                            <img src="<?php echo htmlspecialchars($avatar, ENT_QUOTES, 'UTF-8'); ?>" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                        <?php else: ?>
                            <span><?php echo strtoupper(htmlspecialchars(mb_substr($nomeUsuario, 0, 2))); ?></span>
                        <?php endif; ?>
                    </div>
                    <div style="display: flex; flex-direction: column; align-items: flex-start;">
                        <span style="font-size:1rem;"><?php echo htmlspecialchars($nomeUsuario); ?></span>
                        <span style="font-size:0.75rem; opacity: 0.7;"><?php echo htmlspecialchars($nivelAcesso); ?></span>
                    </div>
                    <a href="logout.php" class="logout-button" title="Sair do sistema">
                        <i class="fas fa-sign-out-alt"></i> Sair
                    </a>
                </div>
            </div>
        </div>
    </header>
    
    <main class="container" role="main">
        <!-- Mensagens de Alerta -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_SESSION['success']); ?>
                <?php if (isset($_SESSION['detalhes'])): ?>
                    <div class="alert-detalhes">
                        <?php foreach ($_SESSION['detalhes'] as $detalhe): ?>
                            <div><?php echo htmlspecialchars($detalhe); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php unset($_SESSION['success'], $_SESSION['detalhes']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($_SESSION['error']); ?>
                <?php if (isset($_SESSION['detalhes'])): ?>
                    <div class="alert-detalhes">
                        <?php foreach ($_SESSION['detalhes'] as $detalhe): ?>
                            <div><?php echo htmlspecialchars($detalhe); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php unset($_SESSION['error'], $_SESSION['detalhes']); ?>
        <?php endif; ?>
        
        <!-- Módulos do Sistema -->
        <section class="menu-container">
            <div class="menu-item menu-onu" onclick="window.location.href='cadastro_onu.php'" tabindex="0" role="button" aria-label="Cadastro ONU">
                <div class="menu-header">
                    <i class="menu-icon fas fa-globe-americas"></i>
                    <div class="menu-title">Cadastro ONU</div>
                </div>
                <div class="menu-description">Gerenciamento de cadastros e configurações de ONUs.</div>
                <div style="padding:0 1.2rem 1.2rem 1.2rem;">
                    <button class="action-button">Acessar</button>
                </div>
            </div>
            <div class="menu-item menu-equipe" onclick="window.location.href='cadastro_equipe.php'" tabindex="0" role="button" aria-label="Cadastro Equipe">
                <div class="menu-header">
                    <i class="menu-icon fas fa-users-cog"></i>
                    <div class="menu-title">Cadastro Equipe</div>
                </div>
                <div class="menu-description">Gestão de equipes, atribuições e competências.</div>
                <div style="padding:0 1.2rem 1.2rem 1.2rem;">
                    <button class="action-button">Acessar</button>
                </div>
            </div>
            <div class="menu-item menu-materiais" onclick="window.location.href='entrega_materiais.php'" tabindex="0" role="button" aria-label="Entrega de Materiais">
                <div class="menu-header">
                    <i class="menu-icon fas fa-truck-loading"></i>
                    <div class="menu-title">Entrega de Materiais</div>
                </div>
                <div class="menu-description">Controle de inventário e logística de entregas.</div>
                <div style="padding:0 1.2rem 1.2rem 1.2rem;">
                    <button class="action-button">Acessar</button>
                </div>
            </div>
            <div class="menu-item menu-miscelaneas" onclick="window.location.href='cadastro_miscelaneas.php'" tabindex="0" role="button" aria-label="Cadastro Miscelâneas">
                <div class="menu-header">
                    <i class="menu-icon fas fa-boxes-stacked"></i>
                    <div class="menu-title">Cadastro Miscelâneas</div>
                </div>
                <div class="menu-description">Gerencie itens diversos e avulsos do sistema.</div>
                <div style="padding:0 1.2rem 1.2rem 1.2rem;">
                    <button class="action-button">Acessar</button>
                </div>
            </div>
            <div class="menu-item menu-contratos" onclick="window.location.href='gerenciamento de contratos.php'" tabindex="0" role="button" aria-label="Gerenciamento de Contratos">
                <div class="menu-header">
                    <i class="menu-icon fas fa-file-signature"></i>
                    <div class="menu-title">Gerenciamento de Contratos</div>
                </div>
                <div class="menu-description">Administração de contratos e documentos legais.</div>
                <div style="padding:0 1.2rem 1.2rem 1.2rem;">
                    <button class="action-button">Acessar</button>
                </div>
            </div>
            <div class="menu-item menu-lista-contratos" onclick="window.location.href='menu_lista_contratos.php'" tabindex="0" role="button" aria-label="Lista de Contratos">
                <div class="menu-header">
                    <i class="menu-icon fas fa-table-list"></i>
                    <div class="menu-title">Lista de Contratos</div>
                </div>
                <div class="menu-description">Visualize, filtre e exporte todos os contratos cadastrados.</div>
                <div style="padding:0 1.2rem 1.2rem 1.2rem;">
                    <button class="action-button">Acessar</button>
                </div>
            </div>
            <div class="menu-item menu-consulta" onclick="window.location.href='consulta.php'" tabindex="0" role="button" aria-label="Consulta">
                <div class="menu-header">
                    <i class="menu-icon fas fa-search"></i>
                    <div class="menu-title">Consulta</div>
                </div>
                <div class="menu-description">Faça consultas rápidas e detalhadas no sistema.</div>
                <div style="padding:0 1.2rem 1.2rem 1.2rem;">
                    <button class="action-button">Acessar</button>
                </div>
            </div>
            <div class="menu-item menu-estoque" onclick="window.location.href='estoque_total.php'" tabindex="0" role="button" aria-label="Estoque Total">
                <div class="menu-header">
                    <i class="menu-icon fas fa-warehouse"></i>
                    <div class="menu-title">Estoque Total</div>
                </div>
                <div class="menu-description">Visualize todo o estoque atual de ONUs e miscelâneas, com baixas automáticas.</div>
                <div style="padding:0 1.2rem 1.2rem 1.2rem;">
                    <button class="action-button">Acessar</button>
                </div>
            </div>
        </section>

        <!-- Seção de Upload em Lote -->
        <section class="upload-section">
            <h2 style="margin-bottom: 16px; color: var(--color-primary);"><i class="fas fa-file-upload"></i> Upload em Lote de Contratos</h2>
            <form method="post" action="upload_pdf_lote.php" enctype="multipart/form-data" class="upload-form">
                <label for="pdf_lote">Selecione um PDF com múltiplas páginas (cada página será atribuída a um contrato pendente):</label>
                <input type="file" name="pdf_lote" id="pdf_lote" accept="application/pdf" required>
                <button type="submit" class="upload-btn">
                    <i class="fas fa-cloud-upload-alt"></i> Enviar PDF em Lote
                </button>
            </form>
            <div style="margin-top: 12px; padding: 12px; background: #e3f2fd; border-radius: 6px; font-size: 0.9rem; color: #1565c0;">
                <i class="fas fa-info-circle"></i> <strong>Importante:</strong> O sistema processará cada página do PDF como um contrato separado, na ordem dos contratos pendentes.
            </div>
        </section>

        <!-- Tabela de Contratos Pendentes -->
        <section class="upload-section">
            <h2 style="margin-bottom: 16px; color: var(--color-primary);"><i class="fas fa-file-contract"></i> Contratos Aguardando Baixa</h2>
            
            <?php if (!empty($contratos)): ?>
            <table class="contrato-table">
                <thead>
                    <tr>
                        <th>Contrato</th>
                        <th>Nome do Cliente</th>
                        <th>Empresa</th>
                        <th>Arquivo PDF</th>
                        <th>Status</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($contratos as $contrato): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($contrato['numero_contrato']); ?></strong></td>
                        <td><?php echo htmlspecialchars($contrato['nome_assinante']); ?></td>
                        <td>
                            <form class="empresa-form" method="post" action="update_empresa.php">
                                <input type="hidden" name="contrato_id" value="<?php echo $contrato['id']; ?>">
                                <select name="nome_empresa" required title="Selecione a empresa">
                                    <option value="">Selecione</option>
                                    <option value="FABIANA" <?php if($contrato['nome_empresa']=='FABIANA') echo 'selected'; ?>>FABIANA</option>
                                    <option value="JUNIOR" <?php if($contrato['nome_empresa']=='JUNIOR') echo 'selected'; ?>>JUNIOR</option>
                                </select>
                                <button type="submit" title="Salvar empresa">
                                    <i class="fas fa-check"></i>
                                </button>
                            </form>
                        </td>
                        <td>
                            <?php if ($contrato['pdf_path']): ?>
                                <a href="<?php echo htmlspecialchars($contrato['pdf_path']); ?>" target="_blank" class="pdf-link">
                                    <i class="fas fa-file-pdf"></i> Ver PDF
                                </a>
                            <?php else: ?>
                                <form method="post" action="upload_pdf.php" enctype="multipart/form-data" class="upload-individual">
                                    <input type="file" name="pdf_file" accept="application/pdf" required title="Selecione o arquivo PDF">
                                    <input type="hidden" name="contrato_id" value="<?php echo $contrato['id']; ?>">
                                    <button type="submit">
                                        <i class="fas fa-upload"></i> Enviar
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($contrato['pdf_path']): ?>
                                <span class="status-badge status-baixado">
                                    <i class="fas fa-check-circle"></i> Baixado
                                </span>
                            <?php else: ?>
                                <span class="status-badge status-pendente">
                                    <i class="fas fa-clock"></i> Aguardando
                                </span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: center;">
                            <?php if ($contrato['pdf_path']): ?>
                                <i class="fas fa-check-circle" style="color: var(--color-accent2); font-size: 1.2em;" title="Concluído"></i>
                            <?php else: ?>
                                <i class="fas fa-exclamation-triangle" style="color: var(--color-accent4); font-size: 1.2em;" title="Pendente"></i>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div style="text-align: center; padding: 40px; color: var(--color-text-light);">
                <i class="fas fa-check-circle" style="font-size: 3rem; margin-bottom: 16px; color: var(--color-accent2);"></i>
                <h3>Todos os contratos foram baixados!</h3>
                <p>Não há contratos aguardando upload de PDF no momento.</p>
            </div>
            <?php endif; ?>
        </section>
    </main>
    
    <footer class="footer">
        <div class="container">
            <p>© 2025 Sistema Integrado de Gerenciamento. Todos os direitos reservados.</p>
        </div>
    </footer>
</body>
</html>