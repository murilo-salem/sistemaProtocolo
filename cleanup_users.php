<?php
require_once 'init.php';

echo "=== INICIANDO LIMPEZA DE DADOS ===\n";
echo "Preservando APENAS usuário ID 1 (login: admin)\n\n";

try {
    TTransaction::open('database');
    $conn = TTransaction::get();
    
    // 1. Identificar usuários a deletar
    $usersToDelete = [];
    $res = $conn->query("SELECT id, login FROM usuario WHERE id != 1");
    if ($res) {
        foreach ($res as $row) {
            $usersToDelete[] = $row['id'];
            echo "Marcado para deletar: ID {$row['id']} ({$row['login']})\n";
        }
    }
    
    if (empty($usersToDelete)) {
        echo "Nenhum usuário para deletar.\n";
        TTransaction::close();
        exit;
    }
    
    $ids = implode(',', $usersToDelete);
    
    // 2. Deletar Chat Messages (enviadas ou recebidas pelos usuários deletados)
    $count = $conn->exec("DELETE FROM chat_messages WHERE sender_id IN ($ids) OR receiver_id IN ($ids)");
    echo "Chat Messages deletadas: $count\n";
    
    // 3. Deletar Notifications
    $count = $conn->exec("DELETE FROM notification WHERE system_user_id IN ($ids)");
    echo "Notifications deletadas: $count\n";
    
    // 4. Deletar System Notifications
    $count = $conn->exec("DELETE FROM system_notification WHERE system_user_id IN ($ids)");
    echo "System Notifications deletadas: $count\n";
    
    // 5. Deletar Client Projects
    $count = $conn->exec("DELETE FROM cliente_projeto WHERE cliente_id IN ($ids)");
    echo "Cliente Project relations deletadas: $count\n";
    
    // 6. Deletar Entregas
    $count = $conn->exec("DELETE FROM entrega WHERE cliente_id IN ($ids)");
    echo "Entregas deletadas: $count\n";
    
    // 7. Deletar Usuários
    $count = $conn->exec("DELETE FROM usuario WHERE id IN ($ids)");
    echo "Usuários deletados: $count\n";
    
    echo "\n=== LIMPEZA CONCLUÍDA COM SUCESSO ===\n";
    
    TTransaction::close();
    
} catch (Exception $e) {
    echo "ERRO FATAL: " . $e->getMessage() . "\n";
    TTransaction::rollback();
}
