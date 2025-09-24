<?php
// config/security.php - Versão corrigida

// Configurar sessão ANTES de iniciar
if (session_status() === PHP_SESSION_NONE) {
    // Configurações de segurança da sessão (antes de session_start)
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    
    // Só definir secure se estiver em HTTPS
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', 1);
    }
    
    // Iniciar sessão APÓS configurar
    session_start();
}

// Gerar token CSRF se não existir
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Função para validar CSRF
function validateCSRF($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Função para sanitizar strings
function sanitizeString($input, $maxLength = 255) {
    if (!is_string($input)) return '';
    $input = trim($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    return substr($input, 0, $maxLength);
}

// Função para validar ID
function validateId($id) {
    return filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
}

// Headers de segurança (só se não foram enviados ainda)
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
}