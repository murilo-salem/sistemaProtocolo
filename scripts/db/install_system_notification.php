<?php
// install_system_notification.php

// Bootstrap Adianti
if (file_exists('init.php')) {
    require_once 'init.php';
} elseif (file_exists('../init.php')) {
    require_once '../init.php';
}

try {
    TTransaction::open('database');
    $conn = TTransaction::get();
    
    echo "Verificando tabela 'system_notification'...\n";
    
    // Check if table exists (MySQL specific)
    $exists = $conn->query("SHOW TABLES LIKE 'system_notification'")->fetch();
    
    if (!$exists) {
        echo "Tabela não encontrada. Criando...\n";
        
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
        )";
        
        $conn->exec($sql);
        echo "Tabela 'system_notification' criada com sucesso!\n";
    } else {
        echo "Tabela 'system_notification' já existe.\n";
    }
    
    TTransaction::close();
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
    TTransaction::rollback();
}
