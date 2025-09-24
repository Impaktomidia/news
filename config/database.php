<?php
// config/database.php - Versão corrigida para XAMPP

// Detectar ambiente automaticamente
$isLocalhost = isset($_SERVER['HTTP_HOST']) && 
               (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || 
                strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false ||
                strpos($_SERVER['HTTP_HOST'], 'xampp') !== false);

if ($isLocalhost) {
    // Configuração para XAMPP/desenvolvimento local
    $config = [
        'host' => 'localhost',
        'db'   => 'ipk2024',  // Certifique-se que este DB existe
        'user' => 'root',
        'pass' => '',         // XAMPP normalmente não tem senha para root
    ];
} else {
    // Configuração para produção
    $config = [
        'host' => 'ipk2024.mysql.uhserver.com',
        'db'   => 'ipk2024',
        'user' => 'ipk',
        'pass' => 'Ipk@12647',
    ];
}

// Função para obter conexão
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
            
            // Log da conexão bem-sucedida
            error_log("Conexão com banco OK: {$config['host']}/{$config['db']}");
            
        } catch (PDOException $e) {
            // Log do erro detalhado
            error_log("Erro de conexão DB: " . $e->getMessage());
            error_log("Tentando conectar: {$config['host']}/{$config['db']} com usuário '{$config['user']}'");
            
            // Em desenvolvimento, mostrar erro detalhado
            if ($GLOBALS['isLocalhost'] ?? false) {
                die("Erro de conexão: " . $e->getMessage() . 
                    "<br>Host: {$config['host']}<br>DB: {$config['db']}<br>User: {$config['user']}");
            } else {
                die("Erro na conexão com banco de dados");
            }
        }
    }
    
    return $connection;
}

// Tornar variável global acessível
$GLOBALS['isLocalhost'] = $isLocalhost;

// Retornar configuração para compatibilidade
return $config;