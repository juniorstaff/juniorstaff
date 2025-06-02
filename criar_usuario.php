<?php
// Ativar exibição de erros para debug (remova em produção)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config.php'; // session_start() está só aqui!

$erro = "";
$sucesso = "";

// Função para checar se o nível de acesso é válido
function acesso_valido($nivel) {
    $validos = ['administrador', 'supervisor', 'usuario'];
    return in_array($nivel, $validos, true);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = sanitize($conn, $_POST['usuario'] ?? '');
    $email = sanitize($conn, $_POST['email'] ?? '');
    $nome = sanitize($conn, $_POST['nome'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $senha2 = $_POST['senha2'] ?? '';
    $nivel_acesso = $_POST['nivel_acesso'] ?? '';

    // Validação dos campos
    if (!acesso_valido($nivel_acesso)) {
        $erro = "Nível de acesso inválido.";
    } elseif (empty($usuario) || empty($email) || empty($nome) || empty($senha) || empty($senha2) || empty($nivel_acesso)) {
        $erro = "Por favor, preencha todos os campos.";
    } elseif ($senha !== $senha2) {
        $erro = "As senhas não coincidem.";
    } else {
        $query = "SELECT id FROM usuarios WHERE usuario = ? OR email = ?";
        if ($stmt = $conn->prepare($query)) {
            $stmt->bind_param("ss", $usuario, $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $erro = "Usuário ou e-mail já cadastrado.";
            } else {
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                // GARANTINDO QUE vai para a coluna nivel_acesso
                $insert = "INSERT INTO usuarios (usuario, email, senha, nome, status, nivel_acesso) VALUES (?, ?, ?, ?, 'ativo', ?)";
                if ($stmt2 = $conn->prepare($insert)) {
                    $stmt2->bind_param("sssss", $usuario, $email, $senha_hash, $nome, $nivel_acesso);
                    if ($stmt2->execute()) {
                        $_SESSION['usuario'] = $usuario;
                        $_SESSION['nivel_acesso'] = $nivel_acesso;
                        // Redirecione para o painel correto
                        switch ($nivel_acesso) {
                            case 'administrador':
                                header("Location: dashboard.php");
                                exit();
                            case 'supervisor':
                                header("Location: supervisor_dashboard.php");
                                exit();
                            case 'usuario':
                                header("Location: usuario_dashboard.php");
                                exit();
                            default:
                                $sucesso = "Usuário criado com sucesso!";
                        }
                    } else {
                        $erro = "Erro ao criar usuário: " . $stmt2->error;
                    }
                    $stmt2->close();
                } else {
                    $erro = "Erro na preparação do cadastro: " . $conn->error;
                }
            }
            $stmt->close();
        } else {
            $erro = "Erro na preparação da consulta: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastro de Usuário</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
    body {
        min-height: 100vh;
        margin: 0;
        /* Fundo com imagem, overlay escuro para leitura */
        background: linear-gradient(rgba(33,33,33,0.62), rgba(0, 0, 0, 0.60)), url('https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=1200&q=80') no-repeat center center fixed;
        background-size: cover;
        font-family: 'Segoe UI', Arial, sans-serif;
    }
    .container {
        background: rgba(255,255,255,0.97);
        max-width: 430px;
        margin: 60px auto;
        padding: 34px 28px 26px 28px;
        border-radius: 18px;
        box-shadow: 0 4px 32px rgba(0,0,0,.18);
        position: relative;
        animation: fadein 0.7s;
    }
    @keyframes fadein {
        from { opacity: 0; transform: translateY(40px);}
        to { opacity: 1; transform: translateY(0);}
    }
    h2 {
        text-align: center;
        margin-bottom: 25px;
        color: #2193b0;
        letter-spacing: .5px;
        font-weight: 700;
    }
    label {
        display: block;
        margin-top: 14px;
        margin-bottom: 5px;
        color: #333;
        font-weight: 500;
    }
    input, select {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #bce0ee;
        border-radius: 7px;
        font-size: 15px;
        background: #f5fcff;
        transition: border-color .2s;
        margin-bottom: 6px;
    }
    input:focus, select:focus {
        border-color: #2193b0;
        outline: none;
        background: #fff;
    }
    button {
        width: 100%;
        padding: 13px;
        background: linear-gradient(90deg, #2193b0 0%, #6dd5ed 100%);
        color: #fff;
        border: none;
        border-radius: 7px;
        font-size: 16px;
        font-weight: 600;
        margin-top: 20px;
        cursor: pointer;
        box-shadow: 0 2px 8px rgba(33,147,176,0.10);
        transition: background .2s;
    }
    button:hover {
        background: linear-gradient(90deg, #6dd5ed 0%, #2193b0 100%);
    }
    .erro, .sucesso {
        padding: 10px 16px;
        border-radius: 6px;
        margin-bottom: 12px;
        text-align: center;
        font-weight: 500;
        letter-spacing: .5px;
        font-size: 15px;
    }
    .erro { background: #ffe0e3; color: #c00; border: 1px solid #ffb5bf; }
    .sucesso { background: #e1ffe3; color: #168b2d; border: 1px solid #7be7a5; }
    .nv-info {
        margin-top: 0.3em;
        margin-bottom: 0.5em;
        font-size: 13px;
        background: #f4faff;
        border-left: 4px solid #2193b0;
        padding: 8px 12px;
        border-radius: 6px;
        color: #2193b0;
    }
    .nv-list {
        margin: 0.3em 0 0 0.3em;
        padding: 0;
        font-size: 13px;
        color: #222;
    }
    .nv-list li { margin-bottom: 3px;}
    @media (max-width: 560px) {
        .container { padding: 16px 3vw 16px 3vw; }
        h2 { font-size: 1.2em; }
    }
    </style>
</head>
<body>
    <div class="container">
        <h2>Cadastro de Usuário</h2>
        <?php if(!empty($erro)): ?>
            <div class="erro"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>
        <?php if(!empty($sucesso)): ?>
            <div class="sucesso"><?= htmlspecialchars($sucesso) ?></div>
        <?php endif; ?>
        <form method="post" autocomplete="off">
            <label for="usuario">Usuário</label>
            <input type="text" id="usuario" name="usuario" maxlength="50" required value="<?= isset($_POST['usuario']) ? htmlspecialchars($_POST['usuario']) : '' ?>">

            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" maxlength="100" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">

            <label for="nome">Nome Completo</label>
            <input type="text" id="nome" name="nome" maxlength="100" required value="<?= isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : '' ?>">

            <label for="senha">Senha</label>
            <input type="password" id="senha" name="senha" maxlength="50" required autocomplete="new-password">

            <label for="senha2">Repita a Senha</label>
            <input type="password" id="senha2" name="senha2" maxlength="50" required autocomplete="new-password">

            <label for="nivel_acesso">Nível de Acesso</label>
            <select id="nivel_acesso" name="nivel_acesso" required>
                <option value="">Selecione</option>
                <option value="administrador" <?= (isset($_POST['nivel_acesso']) && $_POST['nivel_acesso'] == 'administrador') ? 'selected' : '' ?>>Administrador</option>
                <option value="supervisor" <?= (isset($_POST['nivel_acesso']) && $_POST['nivel_acesso'] == 'supervisor') ? 'selected' : '' ?>>Supervisor</option>
                <option value="usuario" <?= (isset($_POST['nivel_acesso']) && $_POST['nivel_acesso'] == 'usuario') ? 'selected' : '' ?>>Usuário</option>
            </select>
            <!-- Descrição dos níveis -->
            <div class="nv-info">
                <b>Funções de cada nível:</b>
                <ul class="nv-list">
                    <li><b>Administrador</b>: Gerencia todo o sistema, usuários e configurações.</li>
                    <li><b>Supervisor</b>: Acompanha serviços e relatórios, mas não gerencia usuários.</li>
                    <li><b>Usuário</b>: Solicita e acompanha serviços próprios.</li>
                </ul>
            </div>
            <button type="submit">Cadastrar</button>
        </form>
    </div>
</body>
</html>