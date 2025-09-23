<?php
class OptimizedDatabaseConfig extends DatabaseConfig {
    protected function connect() {
        $dsn = "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8mb4";
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            PDO::ATTR_PERSISTENT => true, // Conexões persistentes
            PDO::MYSQL_ATTR_COMPRESS => true, // Compressão
        ];
        
        // Pool de conexões simples
        $maxRetries = 3;
        $retryDelay = 1000000; // 1 segundo em microssegundos
        
        for ($i = 0; $i < $maxRetries; $i++) {
            try {
                $this->connection = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS'], $options);
                
                // Otimizações MySQL
                $this->connection->exec("SET SESSION sql_mode = 'NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
                $this->connection->exec("SET SESSION innodb_lock_wait_timeout = 5");
                
                break;
                
            } catch (PDOException $e) {
                if ($i === $maxRetries - 1) {
                    error_log("Database connection failed after {$maxRetries} attempts: " . $e->getMessage());
                    throw new Exception("Erro na conexão com banco de dados");
                }
                
                usleep($retryDelay);
                $retryDelay *= 2; // Backoff exponencial
            }
        }
    }
}
