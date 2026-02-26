<?php

class InstallNotificationTable
{
    public static function createTable()
    {
        try {
            TTransaction::open('database');
            $conn = TTransaction::get();
            
            // Check if table exists
            $exists = false;
            try {
                $conn->query("SELECT 1 FROM notification LIMIT 1");
                $exists = true;
            } catch (Exception $e) {
                // Table doesn't exist
            }
            
            if (!$exists) {
                $sql = "CREATE TABLE notification (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    system_user_id INTEGER NOT NULL,
                    type TEXT,
                    title TEXT,
                    message TEXT,
                    reference_type TEXT,
                    reference_id INTEGER,
                    action_url TEXT,
                    action_label TEXT,
                    icon TEXT,
                    read_at DATETIME,
                    created_at DATETIME
                )";
                
                // Adjust for MySQL if needed (Adianti typically handles both but explicit SQL might need adjustment)
                // Assuming SQLite for dev/test based on 'AUTOINCREMENT', but if it's MySQL/Postgres logic might vary.
                // Given the project seems to be on MySQL (XAMPP), I should use MySQL syntax.
                
                 $sql_mysql = "CREATE TABLE IF NOT EXISTS notification (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    system_user_id INT NOT NULL,
                    type VARCHAR(20),
                    title VARCHAR(200),
                    message TEXT,
                    reference_type VARCHAR(50),
                    reference_id INT,
                    action_url VARCHAR(255),
                    action_label VARCHAR(50),
                    icon VARCHAR(50),
                    read_at DATETIME,
                    created_at DATETIME,
                    INDEX(system_user_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
                
                $conn->exec($sql_mysql);
                echo "Tabela 'notification' criada com sucesso.<br>";
            } else {
                echo "Tabela 'notification' já existe.<br>";
            }
            
            TTransaction::close();
        } catch (Exception $e) {
            echo "Erro: " . $e->getMessage();
            TTransaction::rollback();
        }
    }
}

// Execute immediately if run via CLI or browser
if (php_sapi_name() == 'cli' || isset($_SERVER['REQUEST_METHOD'])) {
    if (file_exists('init.php')) {
        require_once 'init.php';
    } elseif (file_exists('../init.php')) {
        require_once '../init.php';
    }
    InstallNotificationTable::createTable();
}
