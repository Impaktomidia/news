<?php
session_start();

// Remove todas as variáveis da sessão
$_SESSION = [];

// Se desejar, remove o cookie da sessão
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000,
        $params["path"], 
        $params["domain"],
        $params["secure"], 
        $params["httponly"]
    );
}

// Destroi a sessão
session_destroy();

// Redireciona para tela de login
header("Location: ../index.php?logout=1");
exit;
