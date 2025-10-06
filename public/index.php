<?php
session_start();

// Verificar se já está logado
if (isset($_SESSION['usuario'])) {
    header("Location: /impaktonew/gestor/");
    exit;
}

// INICIALIZAR VARIÁVEIS
$erro = false;
$debug_info = [];

// PROCESSAR LOGIN
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (empty($usuario) || empty($senha)) {
        $erro = "Usuário e senha são obrigatórios";
    } else {
        try {
            require_once __DIR__ . '/../config/database.php';
            $pdo = getDatabase();
            
            // Usar SELECT * para evitar problemas com colunas
            $sql = "SELECT * FROM admins WHERE usuario = ? LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$usuario]);
            $user = $stmt->fetch();
            
            // Verificar ativo (se coluna existir)
            $isAtivo = !isset($user['ativo']) || $user['ativo'] == 1 || $user['ativo'] === '1';
            
            if ($user && $isAtivo && password_verify($senha, $user['senha'])) {
                session_regenerate_id(true);
                $_SESSION['usuario'] = $user['usuario'];
                $_SESSION['usuario_id'] = $user['id'];
                
                header("Location: /impaktonew/gestor/?logado=1");
                exit;
            } else {
                $erro = "Usuário ou senha incorretos";
            }
            
        } catch (Exception $e) {
            $erro = "Erro de conexão: " . $e->getMessage();
            $debug_info['erro'] = $e->getMessage();
        }
    }
}

// Gerar token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Impakto Mídia</title>
    <style>
        * { 
            box-sizing: border-box; 
            margin: 0; 
            padding: 0; 
        }
        
        body { 
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif; 
            min-height: 100vh; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            background-attachment: fixed;
            padding: 20px;
        }
        
        .login-container { 
            background: rgba(255, 255, 255, 0.95); 
            backdrop-filter: blur(10px);
            padding: 3rem; 
            border-radius: 20px; 
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2); 
            width: 100%;
            max-width: 420px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .logo { 
            margin-bottom: 2rem; 
        }
        
        .logo h1 { 
            font-size: 3rem; 
            color: #2c3e50; 
            margin-bottom: 0.5rem; 
            font-weight: 300; 
            letter-spacing: -2px;
        }
        
        .logo .red { 
            color: #C0392B; 
            font-weight: 700; 
        }
        
        .subtitle { 
            color: #7f8c8d; 
            font-size: 0.95rem; 
            margin-bottom: 2.5rem; 
            text-transform: uppercase; 
            letter-spacing: 3px; 
            font-weight: 600;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }
        
        input { 
            width: 100%; 
            padding: 18px 20px; 
            border: 2px solid #e0e6ed; 
            border-radius: 12px; 
            font-size: 16px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            background: rgba(248, 249, 250, 0.8);
            color: #2c3e50;
        }
        
        input:focus { 
            border-color: #C0392B; 
            outline: none; 
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 0 0 4px rgba(192, 57, 43, 0.1);
            transform: translateY(-2px);
        }
        
        input::placeholder {
            color: #bdc3c7;
        }
        
        button { 
            width: 100%; 
            background: linear-gradient(135deg, #C0392B 0%, #E74C3C 100%); 
            color: white; 
            padding: 18px 20px; 
            border: none; 
            border-radius: 12px; 
            font-size: 16px; 
            font-weight: 700;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            text-transform: uppercase;
            letter-spacing: 2px;
            box-shadow: 0 8px 25px rgba(192, 57, 43, 0.3);
        }
        
        button:hover { 
            background: linear-gradient(135deg, #A93226 0%, #C0392B 100%);
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(192, 57, 43, 0.4);
        }
        
        button:active {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(192, 57, 43, 0.3);
        }
        
        .erro { 
            color: #e74c3c; 
            margin-bottom: 1.5rem; 
            padding: 15px 20px; 
            background: rgba(231, 76, 60, 0.1); 
            border: 1px solid rgba(231, 76, 60, 0.2);
            border-radius: 12px; 
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .connection-info {
            margin-top: 1rem;
            padding: 15px;
            background: rgba(46, 204, 113, 0.1);
            border: 1px solid rgba(46, 204, 113, 0.2);
            border-radius: 12px;
            font-size: 12px;
            color: #27ae60;
            text-align: left;
        }
        
        .test-info {
            margin-top: 2rem;
            padding: 20px;
            background: rgba(52, 152, 219, 0.1);
            border: 1px solid rgba(52, 152, 219, 0.2);
            border-radius: 12px;
            font-size: 13px;
            color: #2980b9;
        }
        
        .test-info strong {
            color: #1abc9c;
        }
        
        .debug-info {
            margin-top: 2rem;
            padding: 20px;
            background: rgba(255, 193, 7, 0.1);
            border: 1px solid rgba(255, 193, 7, 0.2);
            border-radius: 12px;
            font-size: 12px;
            color: #856404;
            text-align: left;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .login-container {
            animation: fadeInUp 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        @media (max-width: 480px) {
            .login-container {
                width: 95%;
                padding: 2rem;
            }
            
            .logo h1 {
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="logo">
        <h1>impa<span class="red">k</span>to</h1>
        <div class="subtitle">mídia OOH</div>
    </div>

    <?php if ($erro): ?>
        <div class="erro">
            <span>⚠️</span>
            <?= htmlspecialchars($erro) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?>">
        
        <div class="form-group">
            <input type="text" 
                   name="usuario" 
                   placeholder="Nome de usuário" 
                   required 
                   autocomplete="username"
                   value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>" />
        </div>
        
        <div class="form-group">
            <input type="password" 
                   name="senha" 
                   placeholder="Senha" 
                   required 
                   autocomplete="current-password" />
        </div>
        
        <button type="submit">Entrar no Sistema</button>
    </form>
    
    <div class="connection-info">
        <strong>🌐 Conexão:</strong> Servidor Remoto<br>
        <strong>🗄️ Host:</strong> ipk2024.mysql.uhserver.com<br>
        <strong>📊 Database:</strong> ipk2024
    </div>
    
    <div class="test-info">
        <div style="margin-bottom: 10px;">
            <strong>🧪 Dados para Teste:</strong>
        </div>
        <div>Usuário: <strong>master</strong></div>
        <div>Senha: <strong>123456</strong></div>
    </div>
    
    <?php if (!empty($debug_info)): ?>
    <div class="debug-info">
        <h4>🔍 Debug:</h4>
        <pre><?= htmlspecialchars(print_r($debug_info, true)) ?></pre>
    </div>
    <?php endif; ?>
</div>

</body>
</html>