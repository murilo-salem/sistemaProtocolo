<?php
header('Content-Type: text/html; charset=utf-8');

// Bootstrap Adianti
require_once 'init.php';

echo "<h2>Diagnóstico do Sistema de Notificações</h2>";
echo "<style>body{font-family:monospace;padding:20px}table{border-collapse:collapse;margin:10px 0}td,th{border:1px solid #ccc;padding:5px 10px}th{background:#eee}</style>";

try {
    TTransaction::open('database');
    $conn = TTransaction::get();
    
    // 1. Check if notification table exists
    echo "<h3>1. Tabela 'notification'</h3>";
    try {
        $result = $conn->query("SELECT COUNT(*) as total FROM notification");
        $row = $result->fetch(PDO::FETCH_ASSOC);
        echo "<p>✅ Tabela existe. Total de registros: <b>{$row['total']}</b></p>";
        
        // Show recent notifications
        $result = $conn->query("SELECT * FROM notification ORDER BY id DESC LIMIT 5");
        echo "<table><tr><th>ID</th><th>user_id</th><th>title</th><th>message</th><th>type</th><th>read_at</th><th>created_at</th></tr>";
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr><td>{$row['id']}</td><td>{$row['system_user_id']}</td><td>{$row['title']}</td><td>" . substr($row['message'],0,50) . "</td><td>{$row['type']}</td><td>{$row['read_at']}</td><td>{$row['created_at']}</td></tr>";
        }
        echo "</table>";
    } catch (Exception $e) {
        echo "<p>❌ Tabela 'notification' NÃO EXISTE: " . $e->getMessage() . "</p>";
    }
    
    // 2. Check system_notification table
    echo "<h3>2. Tabela 'system_notification'</h3>";
    try {
        $result = $conn->query("SELECT COUNT(*) as total FROM system_notification");
        $row = $result->fetch(PDO::FETCH_ASSOC);
        echo "<p>✅ Tabela existe. Total de registros: <b>{$row['total']}</b></p>";
        
        $result = $conn->query("SELECT * FROM system_notification ORDER BY id DESC LIMIT 5");
        echo "<table><tr><th>ID</th><th>user_id</th><th>title</th><th>message</th><th>checked</th><th>dt_message</th></tr>";
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr><td>{$row['id']}</td><td>{$row['system_user_id']}</td><td>{$row['title']}</td><td>" . substr($row['message'],0,50) . "</td><td>{$row['checked']}</td><td>{$row['dt_message']}</td></tr>";
        }
        echo "</table>";
    } catch (Exception $e) {
        echo "<p>❌ Tabela 'system_notification' NÃO EXISTE: " . $e->getMessage() . "</p>";
    }
    
    // 3. Check usuario table - gestores
    echo "<h3>3. Usuários gestores/admin (tabela 'usuario')</h3>";
    try {
        $result = $conn->query("SELECT id, nome, login, tipo, ativo FROM usuario WHERE tipo IN ('admin', 'gestor')");
        echo "<table><tr><th>ID</th><th>Nome</th><th>Login</th><th>Tipo</th><th>Ativo</th></tr>";
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr><td>{$row['id']}</td><td>{$row['nome']}</td><td>{$row['login']}</td><td>{$row['tipo']}</td><td>{$row['ativo']}</td></tr>";
        }
        echo "</table>";
    } catch (Exception $e) {
        echo "<p>❌ Erro: " . $e->getMessage() . "</p>";
    }
    
    // 4. Check system_user_group table
    echo "<h3>4. Tabela 'system_user_group' (usada por notifyManagers)</h3>";
    try {
        $result = $conn->query("SELECT * FROM system_user_group");
        echo "<table><tr><th>ID</th><th>system_user_id</th><th>system_group_id</th></tr>";
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr><td>{$row['id']}</td><td>{$row['system_user_id']}</td><td>{$row['system_group_id']}</td></tr>";
        }
        echo "</table>";
    } catch (Exception $e) {
        echo "<p>❌ Tabela 'system_user_group' NÃO EXISTE ou erro: " . $e->getMessage() . "</p>";
    }
    
    // 5. Check system_user table
    echo "<h3>5. Tabela 'system_user' (Adianti)</h3>";
    try {
        $result = $conn->query("SELECT id, name, login, active FROM system_user LIMIT 10");
        echo "<table><tr><th>ID</th><th>Name</th><th>Login</th><th>Active</th></tr>";
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr><td>{$row['id']}</td><td>{$row['name']}</td><td>{$row['login']}</td><td>{$row['active']}</td></tr>";
        }
        echo "</table>";
    } catch (Exception $e) {
        echo "<p>ℹ️ Tabela 'system_user' não encontrada: " . $e->getMessage() . "</p>";
    }
    
    // 6. Session info
    echo "<h3>6. Sessão Atual</h3>";
    echo "<p>userid: <b>" . TSession::getValue('userid') . "</b></p>";
    echo "<p>username: <b>" . TSession::getValue('username') . "</b></p>";
    echo "<p>login: <b>" . TSession::getValue('login') . "</b></p>";
    
    // 7. Test creating a notification directly
    echo "<h3>7. Teste de criação de notificação</h3>";
    $test_user_id = TSession::getValue('userid');
    if ($test_user_id) {
        try {
            $n = new Notification;
            $n->system_user_id = $test_user_id;
            $n->title = 'Teste Diagnóstico';
            $n->message = 'Notificação de teste criada em ' . date('H:i:s');
            $n->type = 'info';
            $n->icon = 'fa fa-bug';
            $n->created_at = date('Y-m-d H:i:s');
            $n->store();
            echo "<p>✅ Notificação de teste criada com ID: <b>{$n->id}</b> para user_id: <b>{$test_user_id}</b></p>";
        } catch (Exception $e) {
            echo "<p>❌ Falha ao criar notificação: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p>⚠️ Nenhuma sessão ativa. Faça login primeiro.</p>";
    }
    
    TTransaction::close();
    
} catch (Exception $e) {
    echo "<p>❌ Erro geral: " . $e->getMessage() . "</p>";
}
