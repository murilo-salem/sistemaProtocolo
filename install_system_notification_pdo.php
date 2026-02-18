<?php
// install_system_notification_pdo.php

$host = 'localhost';
$db   = 'banco_completo';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    echo "Conectando ao banco '$db'...\n";
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    echo "Recriando tabela 'system_notification'...\n";
    $pdo->exec("DROP TABLE IF EXISTS system_notification");
    
    // $stmt = $pdo->query("SHOW TABLES LIKE 'system_notification'");
    // $exists = $stmt->fetch();
    
    // if (!$exists) {
    if (true) {
        echo "Tabela não encontrada. Criando (Engine: InnoDB)...\n";
        
        $sql = "CREATE TABLE system_notification (
            id INT AUTO_INCREMENT PRIMARY KEY,
            system_user_id INT,
            action_url TEXT,
            action_label TEXT,
            icon TEXT,
            title TEXT,
            message TEXT,
            dt_message DATETIME,
            checked CHAR(1) DEFAULT 'N'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $pdo->exec($sql);
        echo "Tabela 'system_notification' criada com sucesso!\n";
    } else {
        echo "Tabela 'system_notification' já existe.\n";
    }

} catch (\PDOException $e) {
    echo "Erro de Banco de Dados: " . $e->getMessage() . "\n";
    echo "Código: " . $e->getCode() . "\n";
    exit(1);
}
