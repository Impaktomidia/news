<?php
// config/database.php - Versão melhorada
function isLocalEnvironment() {
    $indicators = [
        isset($_SERVER['HTTP_HOST']) && (
            strpos($_SERVER['HTTP_HOST'], 'localhost') !== false ||
            strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false ||
            strpos($_SERVER['HTTP_HOST'], 'xampp') !== false ||
            strpos($_SERVER['HTTP_HOST'], '.local') !== false
        ),
        isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] === 'localhost',
        !isset($_SERVER['HTTP_HOST']) // CLI
    ];
    
    return in_array(true, $indicators);
}

$isLocal = isLocalEnvironment();

if ($isLocal) {
    // Configuração para desenvolvimento local
    $config = [
        'host' => 'localhost',
        'db'   => 'ipk2024',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4'
    ];
} else {
    // Configuração para produção
    $config = [
        'host' => 'ipk2024.mysql.uhserver.com',
        'db'   => 'ipk2024', 
        'user' => 'ipk',
        'pass' => 'Ipk@12647',
        'charset' => 'utf8mb4'
    ];
}

function getDatabase() {
    global $config;
    static $connection = null;
    
    if ($connection === null) {
        $dsn = "mysql:host={$config['host']};dbname={$config['db']};charset={$config['charset']}";
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$config['charset']}"
        ];
        
        try {
            $connection = new PDO($dsn, $config['user'], $config['pass'], $options);
            error_log("Conexão DB estabelecida: {$config['host']}/{$config['db']}");
        } catch (PDOException $e) {
            error_log("ERRO DB: " . $e->getMessage());
            error_log("Config: " . json_encode($config));
            throw new Exception("Erro na conexão com o banco de dados");
        }
    }
    
    return $connection;
}

return $config;
