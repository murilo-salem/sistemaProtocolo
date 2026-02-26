<?php
require_once 'init.php';

echo "Script initialized.\n";

try {
    echo "Opening transaction...\n";
    TTransaction::open('database');
    $conn = TTransaction::get();
    
    // Check if table exists
    $exists = false;
    try {
        $conn->query("SELECT 1 FROM chat_messages LIMIT 1");
        $exists = true;
        echo "Table 'chat_messages' already exists.\n";
    } catch (Exception $e) {
        echo "Table 'chat_messages' not found. Code: " . $e->getCode() . "\n";
    }

    if (!$exists) {
        echo "Creating table...\n";
        $conn->query("CREATE TABLE chat_messages (
            id INTEGER PRIMARY KEY AUTO_INCREMENT,
            sender_id INTEGER NOT NULL,
            receiver_id INTEGER NOT NULL,
            message TEXT NOT NULL,
            is_read CHAR(1) DEFAULT 'N',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        echo "Table creation command sent.\n";
    }
    
    TTransaction::close();
    echo "Transaction closed (Table committed).\n";
    
    // Index
    TTransaction::open('database');
    $conn = TTransaction::get();
    try {
        $conn->query("CREATE INDEX idx_chat_receiver ON chat_messages(receiver_id)");
        echo "Index created.\n";
    } catch (Exception $e) {
        echo "Index creation skipped (may exist or failed: " . $e->getMessage() . ")\n";
    }
    TTransaction::close();
    echo "Index transaction closed.\n";

} catch (Exception $e) {
    echo "Fatal error: " . $e->getMessage() . "\n";
    try { TTransaction::rollback(); } catch(Exception $e2) {}
}
