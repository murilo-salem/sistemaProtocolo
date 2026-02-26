<?php
// Configurações do banco de dados (mesmas do database.ini)
$host = 'localhost';
$port = '3306';
$name = 'banco_completo';
$user = 'root';
$pass = '';
$type = 'mysql';

try {
    // Conexão com PDO
    $conn = new PDO("mysql:host=$host;port=$port;dbname=$name", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Conectado com sucesso ao banco de dados '$name'.\n";
    
    // SQL para criar a tabela system_user_reset
    $sql = "CREATE TABLE IF NOT EXISTS system_user_reset (
        id INT PRIMARY KEY AUTO_INCREMENT,
        email VARCHAR(200) NOT NULL,
        token VARCHAR(200) NOT NULL,
        created_at DATETIME,
        expires_at DATETIME,
        used CHAR(1) DEFAULT 'N'
    )";
    
    // Executa o comando
    $conn->exec($sql);
    echo "Tabela 'system_user_reset' criada ou já existente com sucesso.\n";
    
} catch(PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?>
