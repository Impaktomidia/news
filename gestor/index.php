<?php
// ============================================
// gestor/index.php - VERSÃO COMPLETA CORRIGIDA
// ============================================

session_start();

// Processar logout primeiro
if (isset($_GET['logout']) && $_GET['logout'] == '1') {
    // Log do logout
    if (isset($_SESSION['usuario'])) {
        error_log("Logout realizado para usuário: {$_SESSION['usuario']}");
    }
    
    // Processar logout - VERSÃO CORRIGIDA
if (isset($_GET['logout']) && $_GET['logout'] == '1') {
    // Redirecionar para o script de logout dedicado
    header("Location: /impaktonew/logout.php");
    exit;
}

// Verificar autenticação
if (!isset($_SESSION['usuario'])) {
    header("Location: /impaktonew/public/index.php?erro=nao_logado");
    exit;
}

// Verificar se o login foi bem-sucedido
$loginSucesso = isset($_GET['logado']) && $_GET['logado'] == '1';

// Obter página atual para navegação ativa
$paginaAtual = $_GET['page'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Impakto Mídia</title>
    <style>
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
        
        body { 
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif; 
            background: #f8f9fa;
            min-height: 100vh;
            line-height: 1.6;
        }
        
        /* ========== HEADER ========== */
        .header { 
            background: linear-gradient(135deg, #ee8170ff 0%, #f40b0bff 100%);
            color: white;
            padding: 1rem 0; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.15); 
            margin-bottom: 2rem;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .header-content { 
            max-width: 1200px; 
            margin: 0 auto; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding: 0 1.5rem;
            gap: 2rem;
        }
        
        /* Logo */
        .logo {
            flex-shrink: 0;
        }
        
        .logo img {
            height: 40px;
            vertical-align: middle;
            filter: brightness(0) invert(1);
        }
        
        .logo h1 { 
            margin: 0; 
            color: white; 
            font-size: 1.8rem;
            font-weight: 300;
            letter-spacing: -1px;
        }
        
        .logo .red { 
            color: #ffeb3b; 
            font-weight: 700; 
        }
        
        .logo .subtitle {
            font-size: 0.8rem; 
            color: rgba(255,255,255,0.8); 
            display: block;
            margin-top: -2px;
        }
        
        /* Menu de navegação */
        .main-nav {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            padding: 0.6rem 1rem;
            border-radius: 20px;
            transition: all 0.3s ease;
            font-weight: 500;
            font-size: 0.9rem;
            position: relative;
            overflow: hidden;
        }
        
        .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transition: left 0.5s;
        }
        
        .nav-link:hover::before {
            left: 100%;
        }
        
        .nav-link:hover {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            transform: translateY(-1px);
        }
        
        .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }
        
        .nav-icon {
            font-size: 1.1rem;
        }
        
        /* User info */
        .user-info { 
            display: flex; 
            align-items: center; 
            gap: 1.5rem;
            flex-shrink: 0;
        }
        
        .welcome-text { 
            color: rgba(255,255,255,0.95); 
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .btn-logout { 
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255,255,255,0.15); 
            color: white; 
            padding: 0.6rem 1.2rem; 
            border: 2px solid rgba(255,255,255,0.2);
            border-radius: 25px; 
            text-decoration: none; 
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
        }
        
        .btn-logout::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transition: left 0.5s;
        }
        
        .btn-logout:hover::before {
            left: 100%;
        }
        
        .btn-logout:hover {
            background: rgba(255,255,255,0.25);
            border-color: rgba(255,255,255,0.4);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        
        .logout-icon {
            font-size: 1rem;
        }
        
        /* ========== CONTAINER ========== */
        .container { 
            max-width: 1200px; 
            margin: 0 auto; 
            padding: 0 1.5rem; 
        }
        
        /* ========== ALERTS ========== */
        .success-alert { 
            background: linear-gradient(45deg, #00C851, #007E33);
            color: white;
            padding: 1.2rem; 
            border-radius: 12px; 
            margin-bottom: 2rem; 
            box-shadow: 0 4px 20px rgba(0, 200, 81, 0.3);
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
            animation: slideInDown 0.5s ease;
        }
        
        /* ========== WELCOME SECTION ========== */
        .welcome { 
            background: white; 
            padding: 3rem; 
            border-radius: 16px; 
            box-shadow: 0 8px 32px rgba(0,0,0,0.08); 
            margin-bottom: 3rem; 
            text-align: center;
            animation: fadeInUp 0.6s ease;
        }
        
        .welcome h2 { 
            color: #2c3e50; 
            margin-bottom: 1rem; 
            font-size: 2.2rem;
            font-weight: 600;
        }
        
        .welcome p { 
            color: #7f8c8d; 
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
        }
        
        /* ========== QUICK MENU ========== */
        .quick-menu { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); 
            gap: 2rem; 
        }
        
        .menu-card { 
            background: white; 
            padding: 2.5rem; 
            border-radius: 16px; 
            box-shadow: 0 8px 32px rgba(0,0,0,0.08); 
            text-align: center; 
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.6s ease;
        }
        
        .menu-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #C0392B, #E74C3C);
            transition: left 0.4s;
        }
        
        .menu-card:hover::before {
            left: 0;
        }
        
        .menu-card:hover { 
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        
        .menu-card a { 
            text-decoration: none; 
            color: inherit;
            display: block;
        }
        
        .menu-card .icon { 
            font-size: 3rem; 
            margin-bottom: 1.5rem; 
            display: block;
            filter: grayscale(0.3);
            transition: all 0.3s ease;
        }
        
        .menu-card:hover .icon {
            filter: grayscale(0);
            transform: scale(1.1);
        }
        
        .menu-card h3 { 
            margin-bottom: 0.8rem; 
            color: #2c3e50;
            font-size: 1.3rem;
            font-weight: 600;
        }
        
        .menu-card p { 
            color: #7f8c8d; 
            font-size: 0.95rem; 
            line-height: 1.5;
        }
        
        .menu-card.disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .menu-card.disabled:hover {
            transform: none;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
        }
        
        .menu-card.disabled .icon {
            filter: grayscale(1);
        }
        
        /* ========== ANIMAÇÕES ========== */
        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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
        
        /* Adicionar delay nas animações dos cards */
        .menu-card:nth-child(1) { animation-delay: 0.1s; }
        .menu-card:nth-child(2) { animation-delay: 0.2s; }
        .menu-card:nth-child(3) { animation-delay: 0.3s; }
        .menu-card:nth-child(4) { animation-delay: 0.4s; }
        
        /* ========== RESPONSIVO ========== */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
                padding: 1rem;
            }
            
            .main-nav {
                order: 2;
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .user-info {
                order: 3;
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .welcome {
                padding: 2rem;
            }
            
            .welcome h2 {
                font-size: 1.8rem;
            }
            
            .container {
                padding: 0 1rem;
            }
        }
        
        @media (max-width: 480px) {
            .header {
                padding: 0.8rem 0;
            }
            
            .nav-link {
                padding: 0.4rem 0.8rem;
                font-size: 0.8rem;
            }
            
            .nav-icon {
                display: none;
            }
            
            .btn-logout {
                padding: 0.5rem 1rem;
                font-size: 0.8rem;
            }
            
            .welcome {
                padding: 1.5rem;
            }
            
            .welcome h2 {
                font-size: 1.5rem;
            }
            
            .menu-card {
                padding: 2rem;
            }
            
            .quick-menu {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
        }
        
        /* ========== LOADING STATE ========== */
        .loading {
            opacity: 0.7;
            pointer-events: none;
        }
        
        /* ========== DARK MODE SUPPORT ========== */
        @media (prefers-color-scheme: dark) {
            body {
                background: #1a1a1a;
            }
            
            .welcome {
                background: #2d2d2d;
                color: #e0e0e0;
            }
            
            .welcome h2 {
                color: #ffffff;
            }
            
            .menu-card {
                background: #2d2d2d;
                color: #e0e0e0;
            }
            
            .menu-card h3 {
                color: #ffffff;
            }
        }
    </style>
</head>
<body>

<div class="header">
    <div class="header-content">
        <div class="logo">
            <?php 
            $logoPath = '/impaktonew/public/assets/img/logo.png';
            ?>
            <h1>
                impa<span class="red">k</span>to
            </h1>
            <span class="subtitle">mídia OOH</span>
        </div>
        
        <!-- Menu de navegação -->
        <nav class="main-nav">
            <a href="/impaktonew/gestor/index.php" class="nav-link <?= $paginaAtual === 'dashboard' ? 'active' : '' ?>">
                <span class="nav-icon">🏠</span>
                Dashboard
            </a>
            <a href="/impaktonew/app/Views/gestor/listar_ponto.php" class="nav-link">
                <span class="nav-icon">📋</span>
                Pontos
            </a>
            <a href="/impaktonew/app/Views/gestor/relatorios/pre_selecao.php" class="nav-link">
                <span class="nav-icon">📊</span>
                Pré-Seleção
            </a>
        </nav>
        
        <div class="user-info">
            <span class="welcome-text">
                👋 Bem-vindo, <strong><?= htmlspecialchars($_SESSION['usuario']) ?></strong>
            </span>
            <a href="/impaktonew/logout.php" class="btn-logout" onclick="return confirm('Tem certeza que deseja sair?')">
                <span class="logout-icon">🚪</span>
                Sair
            </a>
        </div>
    </div>
</div>

<div class="container">
    <?php if ($loginSucesso): ?>
        <div class="success-alert">
            <span style="font-size: 1.5rem;">🎉</span>
            <div>Login realizado com sucesso! Bem-vindo ao sistema de gestão.</div>
        </div>
    <?php endif; ?>
    
    <div class="welcome">
        <h2>🎯 Sistema de Gestão de Pontos</h2>
        <p>Gerencie seus pontos de mídia exterior com eficiência e precisão. Acesse as funcionalidades através dos cards abaixo.</p>
    </div>
    
    <div class="quick-menu">
        <div class="menu-card">
            <a href="/impaktonew/app/Views/gestor/listar_ponto.php">
                <div class="icon">📋</div>
                <h3>Lista de Pontos</h3>
                <p>Visualizar e gerenciar todos os pontos cadastrados no sistema com filtros avançados</p>
            </a>
        </div>
        
        <div class="menu-card">
            <a href="/impaktonew/app/Views/gestor/relatorios/pre_selecao.php">
                <div class="icon">📊</div>
                <h3>Pré-Seleção</h3>
                <p>Fazer pré-seleção de pontos através da numeração específica e gerar relatórios</p>
            </a>
        </div>
        
        <div class="menu-card disabled" title="Em desenvolvimento">
            <div class="icon">📈</div>
            <h3>Relatórios</h3>
            <p>Relatórios avançados e estatísticas detalhadas (em desenvolvimento)</p>
        </div>
        
        <div class="menu-card disabled" title="Em desenvolvimento">
            <div class="icon">⚙️</div>
            <h3>Configurações</h3>
            <p>Configurações do sistema e preferências do usuário (em desenvolvimento)</p>
        </div>
    </div>
</div>

<script>
// ========== JAVASCRIPT MELHORADO ==========

document.addEventListener('DOMContentLoaded', function() {
    // Remover parâmetro logado da URL após mostrar a mensagem
    if (window.location.search.includes('logado=1')) {
        setTimeout(() => {
            const url = new URL(window.location);
            url.searchParams.delete('logado');
            window.history.replaceState({}, '', url);
        }, 3000);
    }
    
    // Adicionar efeitos de hover nos cards
    const menuCards = document.querySelectorAll('.menu-card:not(.disabled)');
    menuCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-8px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
    
    // Adicionar loading state nos links
    const links = document.querySelectorAll('a[href]:not([href^="#"]):not([href^="javascript:"])');
    links.forEach(link => {
        link.addEventListener('click', function() {
            if (!this.href.includes('logout')) {
                document.body.classList.add('loading');
            }
        });
    });
    
    // Confirmação de logout
    const logoutBtn = document.querySelector('.btn-logout');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (confirm('Tem certeza que deseja sair do sistema?')) {
                document.body.classList.add('loading');
                window.location.href = this.href;
            }
        });
    }
    
    // Verificar conectividade
    window.addEventListener('online', () => {
        console.log('Conexão restaurada');
    });
    
    window.addEventListener('offline', () => {
        console.log('Conexão perdida');
        alert('Conexão com a internet foi perdida. Algumas funcionalidades podem não funcionar corretamente.');
    });
});

// Função para atualizar o status da sessão
function checkSession() {
    fetch('/impaktonew/check_session.php')
        .then(response => response.json())
        .then(data => {
            if (!data.logged_in) {
                alert('Sua sessão expirou. Você será redirecionado para a página de login.');
                window.location.href = '/impaktonew/public/index.php?mensagem=sessao_expirada';
            }
        })
        .catch(error => {
            console.log('Erro ao verificar sessão:', error);
        });
}

// Verificar sessão a cada 5 minutos
setInterval(checkSession, 5 * 60 * 1000);
</script>

</body>
</html>