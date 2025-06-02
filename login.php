<?php
require_once 'config.php'; // Adicionado para garantir uso de sessão
// Redireciona para index.php se o usuário já estiver logado
if (isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

$erro = "";
$sucesso = "";

// Função de sanitização básica, caso não exista
if (!function_exists('sanitize')) {
    function sanitize($conn, $string) {
        return htmlspecialchars(trim($string));
    }
}

// Processar o formulário de login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = sanitize($conn, $_POST['usuario']);
    $senha = $_POST['senha'];
    
    // Verificar se os campos estão preenchidos
    if (empty($usuario) || empty($senha)) {
        $erro = "Por favor, preencha todos os campos.";
    } else {
        // Consultar o banco de dados para o usuário
        $query = "SELECT id, usuario, senha, nivel_acesso, nome, status FROM usuarios WHERE usuario = ? OR email = ? LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $usuario, $usuario);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            
            // Verificar se o usuário está ativo
            if ($row['status'] == 'bloqueado') {
                $erro = "Sua conta está bloqueada. Entre em contato com o administrador.";
            } elseif ($row['status'] == 'inativo') {
                $erro = "Sua conta está inativa. Entre em contato com o administrador.";
            } else {
                // Verificar a senha
                if (password_verify($senha, $row['senha'])) {
                    // Login bem-sucedido
                    $_SESSION['usuario_id'] = $row['id'];
                    $_SESSION['usuario'] = $row['usuario'];
                    $_SESSION['nivel_acesso'] = $row['nivel_acesso'];
                    $_SESSION['nome'] = $row['nome'];
                    
                    // Registrar o login no log
                    $ip = $_SERVER['REMOTE_ADDR'];
                    $log_query = "INSERT INTO logs_acesso (usuario_id, ip, acao) VALUES (?, ?, 'login')";
                    $log_stmt = $conn->prepare($log_query);
                    $log_stmt->bind_param("is", $row['id'], $ip);
                    $log_stmt->execute();
                    
                    // Atualizar último acesso
                    $update_query = "UPDATE usuarios SET ultimo_acesso = NOW() WHERE id = ?";
                    $update_stmt = $conn->prepare($update_query);
                    $update_stmt->bind_param("i", $row['id']);
                    $update_stmt->execute();
                    
                    header("Location: login.php");
                    exit;
                } else {
                    $erro = "Senha incorreta.";
                }
            }
        } else {
            $erro = "Usuário não encontrado.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema Integrado de Gerenciamento</title>
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
            --color-text: #333;
            --color-text-light: #7f8c8d;
            --transition: all 0.3s ease;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { 
            background: linear-gradient(135deg, #f6f8fa 0%, #e9eef2 100%);
            min-height: 100vh; 
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .login-container {
            background-color: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 420px;
            padding: 2rem;
            position: relative;
            border-top: 7px solid var(--color-accent1);
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-logo {
            font-size: 3rem;
            color: var(--color-accent1);
            margin-bottom: 1rem;
        }
        .login-title {
            color: var(--color-primary);
            font-size: 1.5rem;
            font-weight: 700;
        }
        .login-subtitle {
            color: var(--color-text-light);
            font-size: 1rem;
            margin-top: 0.5rem;
        }
        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .alert-danger {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--color-accent3);
            border: 1px solid rgba(231, 76, 60, 0.3);
        }
        .alert-success {
            background-color: rgba(46, 204, 113, 0.1);
            color: var(--color-accent2);
            border: 1px solid rgba(46, 204, 113, 0.3);
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--color-text);
            font-weight: 600;
            font-size: 0.95rem;
        }
        .form-control {
            display: block;
            width: 100%;
            padding: 0.8rem 1rem;
            font-size: 1rem;
            line-height: 1.5;
            color: var(--color-text);
            background-color: #f8f9fa;
            background-clip: padding-box;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            transition: var(--transition);
        }
        .form-control:focus {
            border-color: var(--color-accent1);
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }
        .btn {
            display: inline-block;
            font-weight: 600;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            user-select: none;
            border: none;
            padding: 0.8rem 1.5rem;
            font-size: 1rem;
            line-height: 1.5;
            border-radius: 10px;
            transition: var(--transition);
            cursor: pointer;
            width: 100%;
        }
        .btn-primary {
            color: #fff;
            background: linear-gradient(90deg, var(--color-accent1), var(--color-accent2));
        }
        .btn-primary:hover {
            background: linear-gradient(90deg, var(--color-accent2), var(--color-accent1));
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
        }
        .register-link {
            display: block;
            text-align: center;
            margin-top: 1.5rem;
            color: var(--color-text-light);
        }
        .register-link a {
            color: var(--color-accent1);
            text-decoration: none;
            font-weight: 600;
        }
        .register-link a:hover {
            text-decoration: underline;
        }
        .input-group {
            position: relative;
        }
        .input-icon {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            right: 15px;
            color: var(--color-text-light);
            cursor: pointer;
        }
        .toggle-password:hover {
            color: var(--color-accent1);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="login-logo">
                <i class="fas fa-project-diagram"></i>
            </div>
            <h1 class="login-title">Sistema Integrado de Gerenciamento</h1>
            <p class="login-subtitle">Faça login para acessar o sistema</p>
        </div>
        
        <?php if (!empty($erro)): ?>
            <div class="alert alert-danger">
                <?php echo $erro; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($sucesso)): ?>
            <div class="alert alert-success">
                <?php echo $sucesso; ?>
            </div>
        <?php endif; ?>
        
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label class="form-label" for="usuario">Usuário ou E-mail</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="usuario" name="usuario" placeholder="Digite seu usuário ou e-mail" required>
                    <div class="input-icon">
                        <i class="fas fa-user"></i>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="senha">Senha</label>
                <div class="input-group">
                    <input type="password" class="form-control" id="senha" name="senha" placeholder="Digite sua senha" required>
                    <div class="input-icon toggle-password" onclick="togglePasswordVisibility()">
                        <i class="fas fa-eye"></i>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-sign-in-alt"></i> Entrar
            </button>
        </form>
        
        <div class="register-link">
            Não tem uma conta? <a href="criar_usuario.php">Registre-se</a>
        </div>
    </div>

    <script>
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('senha');
            const toggleIcon = document.querySelector('.toggle-password i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>