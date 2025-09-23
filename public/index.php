<?php
// No seu index.php, substituir a validação por:
require_once __DIR__ . '/config/security.php';

// Verificar se já está logado
if (isset($_SESSION['usuario'])) {
    header("Location: gestor/index.php");
    exit;
}

$erro = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar CSRF
    if (!validateCSRF($_POST['_token'] ?? '')) {
        $erro = "Token de segurança inválido";
    } else {
        $usuario = sanitizeString($_POST['usuario'] ?? '');
        $senha = $_POST['senha'] ?? '';

        // Validar campos obrigatórios
        if (empty($usuario) || empty($senha)) {
            $erro = "Usuário e senha são obrigatórios";
        } else {
            // Obter conexão do banco
            $conn = getDatabase();

            // Consulta segura
            $sql = "SELECT id, usuario, senha FROM admins WHERE usuario = ? AND ativo = 1 LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$usuario]);
            $user = $stmt->fetch();

            if ($user && password_verify($senha, $user['senha'])) {
                // Login bem-sucedido
                session_regenerate_id(true);
                $_SESSION['usuario'] = $user['usuario'];
                $_SESSION['usuario_id'] = $user['id'];
                $_SESSION['login_time'] = time();
                
                // Log do login
                error_log("Login bem-sucedido: " . $user['usuario'] . " IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
                
                header("Location: gestor/index.php?logado=1");
                exit;
            } else {
                // Log da tentativa inválida
                error_log("Tentativa de login inválida: $usuario IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
                $erro = "Usuário ou senha incorretos";
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
    <title>Login - Inpakto Mídia</title>
    <!-- Seu CSS atual aqui -->
</head>
<body>
<div class="login-container">
    <div class="logo">
        <img src="public/assets/img/logo_gestor.png" alt="Logomarca">
    </div>

    <?php if ($erro): ?>
        <div class="erro"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <form method="POST">
        <!-- Token CSRF -->
        <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?>">
        
        <input type="text" name="usuario" placeholder="Usuário" required 
               value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>">
        <input type="password" name="senha" placeholder="Senha" required>
        <button type="submit">Login</button>
    </form>
</div>
</body>
</html>