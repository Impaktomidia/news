<?php
// ============================================
// 2. CORRIGIR gestor/index.php (CRIAR ARQUIVO)
// ============================================
// gestor/index.php - Novo arquivo principal do gestor
session_start();

// Verificar autenticação
if (!isset($_SESSION['usuario'])) {
    header("Location: ../public/index.php?erro=nao_logado");
    exit;
}

// Incluir dependências
require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/../config/security.php';

// Roteamento simples
$page = $_GET['page'] ?? 'dashboard';
$id = $_GET['id'] ?? null;

// Headers de segurança
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
}

try {
    switch ($page) {
        case 'dashboard':
            require_once __DIR__ . '/../app/Controllers/PontoController.php';
            $controller = new PontoController();
            $controller->dashboard();
            break;
            
        case 'pontos':
        case 'listar':
            require_once __DIR__ . '/../app/Views/gestor/listar_ponto.php';
            break;
            
        case 'ponto':
            if ($id) {
                require_once __DIR__ . '/../app/Views/gestor/pontos/show.php';
            } else {
                header("Location: ?page=pontos");
            }
            break;
            
        case 'pre_selecao':
            require_once __DIR__ . '/../app/Views/gestor/relatorios/pre_selecao.php';
            break;
            
        case 'pre_selecao_gerar':
            require_once __DIR__ . '/../app/Controllers/PontoController.php';
            $controller = new PontoController();
            $controller->buscarPorNumeros();
            break;
            
        case 'logout':
            require_once __DIR__ . '/../app/Controllers/AuthController.php';
            $controller = new AuthController();
            $controller->logout();
            break;
            
        default:
            // Dashboard como padrão
            require_once __DIR__ . '/../app/Controllers/PontoController.php';
            $controller = new PontoController();
            $controller->dashboard();
    }
    
} catch (Exception $e) {
    error_log("Erro no roteamento: " . $e->getMessage());
    include __DIR__ . '/../app/Views/errors/500.php';
}