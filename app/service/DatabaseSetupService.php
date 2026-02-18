<?php
class DatabaseSetupService
{
    public function setup()
    {
        try {
            TTransaction::open('database');
            $conn = TTransaction::get();
            
            // Check if table exists
            $conn->query("CREATE TABLE IF NOT EXISTS chat_messages (
                id INTEGER PRIMARY KEY AUTO_INCREMENT,
                sender_id INTEGER NOT NULL,
                receiver_id INTEGER NOT NULL,
                message TEXT NOT NULL,
                is_read CHAR(1) DEFAULT 'N',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
            TTransaction::close(); // Commit table creation
            
            // Index creation in separate transaction
            try {
                TTransaction::open('database');
                $conn = TTransaction::get();
                $conn->query("CREATE INDEX idx_chat_receiver ON chat_messages(receiver_id)");
                TTransaction::close();
            } catch (Exception $e) {
                // Ignore index error (might already exist)
            }

            echo "Tabela chat_messages criada com sucesso!";
        } catch (Exception $e) {
            echo "Erro: " . $e->getMessage();
            TTransaction::rollback();
        }
    }
}
