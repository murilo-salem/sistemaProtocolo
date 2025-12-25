<?php
require_once 'init.php';

try {
    TTransaction::open('database');
    $conn = TTransaction::get();
    
    $sql = "
    CREATE TABLE IF NOT EXISTS mensagem (
        id INT PRIMARY KEY AUTO_INCREMENT,
        system_user_id INT NOT NULL,
        system_user_to_id INT NOT NULL,
        subject VARCHAR(200),
        message TEXT NOT NULL,
        dt_message DATETIME,
        checked CHAR(1) DEFAULT 'N',
        FOREIGN KEY (system_user_id) REFERENCES usuario(id),
        FOREIGN KEY (system_user_to_id) REFERENCES usuario(id)
    )";
    
    $conn->query($sql);
    TTransaction::close();
    echo "Table 'mensagem' created successfully.";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
