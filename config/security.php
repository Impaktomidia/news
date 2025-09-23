<?php
// ============================================
// 1. SEGURANÇA BÁSICA - config/security.php
// Criar este arquivo novo
// ============================================

<?php
// config/security.php
session_start();

// Configurações de segurança da sessão
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
if (isset($_SERVER['HTTPS'])) {
    ini_set('session.cookie_secure', 1);
}

// Gerar token CSRF
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

// Headers de segurança
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
}

// ============================================
// 2. MELHORAR config/database.php ATUAL
// Substituir seu arquivo existente
// ============================================

<?php
// config/database.php - Versão melhorada

// Mover credenciais para arquivo separado (mais seguro)
$credentials = [
    'production' => [
        'host' => 'ipk2024.mysql.uhserver.com',
        'db'   => 'ipk2024',
        'user' => 'ipk',
        'pass' => 'Ipk@12647',
    ],
    'local' => [
        'host' => 'localhost',
        'db'   => 'ipk2024',
        'user' => 'root',
        'pass' => '',
    ]
];

// Detectar ambiente
$env = (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) ? 'local' : 'production';
$config = $credentials[$env];

// Função para obter conexão otimizada
function getDatabase() {
    global $config;
    static $connection = null;
    
    if ($connection === null) {
        $dsn = "mysql:host={$config['host']};dbname={$config['db']};charset=utf8mb4";
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        try {
            $connection = new PDO($dsn, $config['user'], $config['pass'], $options);
            
            // Log conexão bem-sucedida
            error_log("Database connected successfully");
            
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            
            // Em produção, não mostrar detalhes do erro
            if ($env === 'production') {
                die("Erro na conexão com banco de dados");
            } else {
                die("Erro na conexão: " . $e->getMessage());
            }
        }
    }
    
    return $connection;
}

// Retornar configuração para compatibilidade
return $config;

// ============================================
// 3. MELHORAR app/Controller/PontoController.php
// Adicionar no topo do seu arquivo existente
// ============================================
