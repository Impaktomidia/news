<?php
$servername = "localhost";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$servername", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Conexão MySQL funcionando perfeitamente!<br>";
    
    // Mostrar versão do MySQL
    $stmt = $pdo->query('SELECT VERSION()');
    $version = $stmt->fetchColumn();
    echo "Versão do MySQL: " . $version;
    
} catch(PDOException $e) {
    echo "Erro na conexão: " . $e->getMessage();
}
?>