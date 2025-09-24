<?php
// public/index.php - Versão corrigida

// Incluir configurações (security.php já inicia a sessão)
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/database.php';

// Verificar se já está logado
if (isset($_SESSION['usuario'])) {
    header("Location: gestor/index.php");
    exit;
}

$erro = false;
$debug = false; // Definir true para ver informações de debug

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar CSRF
    if (!validateCSRF($_POST['_token'] ?? '')) {
        $erro = "Token de segurança inválido";
        if ($debug) $erro .= " (Token esperado: " . ($_SESSION['csrf_token'] ?? 'nenhum') . ")";
    } else {
        $usuario = sanitizeString($_POST['usuario'] ?? '');
        $senha = $_POST['senha'] ?? '';

        // Validar campos obrigatórios
        if (empty($usuario) || empty($senha)) {
            $erro = "Usuário e senha são obrigatórios";
        } else {
            try {
                // Tentar obter conexão do banco
                $conn = getDatabase();
                
                if ($debug) {
                    echo "<!-- DEBUG: Conexão estabelecida -->";
                }

                // Consulta segura
                $sql = "SELECT id, usuario, senha FROM admins WHERE usuario = ? AND ativo = 1 LIMIT 1";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$usuario]);
                $user = $stmt->fetch();

                if ($debug) {
                    echo "<!-- DEBUG: Usuário encontrado: " . ($user ? 'SIM' : 'NÃO') . " -->";
                }

                if ($user && password_verify($senha, $user['senha'])) {
                    // Login bem-sucedido
                    session_regenerate_id(true);
                    $_SESSION['usuario'] = $user['usuario'];
                    $_SESSION['usuario_id'] = $user['id'];
                    $_SESSION['login_time'] = time();
                    
                    // Log do login
                    error_log("Login OK: " . $user['usuario'] . " IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
                    
                    header("Location: gestor/index.php?logado=1");
                    exit;
                } else {
                    // Log da tentativa inválida
                    error_log("Login inválido: $usuario IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
                    $erro = "Usuário ou senha incorretos";
                    
                    if ($debug && $user) {
                        $erro .= " (Usuário existe, senha incorreta)";
                    } elseif ($debug) {
                        $erro .= " (Usuário não encontrado)";
                    }
                }
            } catch (Exception $e) {
                error_log("Erro no login: " . $e->getMessage());
                $erro = "Erro interno. Tente novamente.";
                if ($debug) $erro .= " (" . $e->getMessage() . ")";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Impakto Mídia</title>
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { height: 100vh; margin: 0; display: flex; justify-content: center; align-items: center; background: #f5f5f5; }
        .login-container { background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 350px; text-align: center; }
        .logo { margin-bottom: 1.5rem; }
        .logo img { max-width: 120px; }
        input { width: 100%; padding: 12px; margin: 8px 0; border: 1px solid #ddd; border-radius: 4px; font-size: 16px; }
        input:focus { border-color: #C0392B; outline: none; }
        button { width: 100%; background: #C0392B; color: white; padding: 12px; border: none; border-radius: 4px; font-size: 16px; cursor: pointer; }
        button:hover { background: #a12a20; }
        .erro { color: #C0392B; margin-bottom: 15px; padding: 10px; background: #fee; border-radius: 4px; font-size: 14px; }
        .debug { background: #e8f4f8; padding: 10px; margin-bottom: 10px; font-size: 12px; text-align: left; }
    </style>
</head>
<body>

<div class="login-container">
    <div class="logo">       
       <img src="public/assets/img/logo.png" alt="Logo" />
    </div>

    <?php if ($debug): ?>
        <div class="debug">
            <strong>DEBUG INFO:</strong><br>
            Ambiente: <?= $GLOBALS['isLocalhost'] ? 'Local (XAMPP)' : 'Produção' ?><br>
            Sessão ativa: <?= session_status() === PHP_SESSION_ACTIVE ? 'SIM' : 'NÃO' ?><br>
            CSRF Token: <?= substr($_SESSION['csrf_token'] ?? 'nenhum', 0, 10) ?>...<br>
        </div>
    <?php endif; ?>

    <?php if ($erro): ?>
        <div class="erro"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <form method="POST">
        <!-- Token CSRF -->
        <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
        
        <input type="text" name="usuario" placeholder="Usuário" required 
               value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>" />
        <input type="password" name="senha" placeholder="Senha" required />
        <button type="submit">Entrar</button>
    </form>
    
    <?php if ($debug): ?>
        <div class="debug" style="margin-top: 15px;">
            <small>
                <strong>Para testar:</strong><br>
                1. Verifique se o banco 'ipk2024' existe no XAMPP<br>
                2. Verifique se a tabela 'admins' existe<br>
                3. Crie um usuário teste se necessário
            </small>
        </div>
    <?php endif; ?>
</div>

</body>
</html>