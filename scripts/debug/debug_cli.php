<?php
// CLI Debug Script
require_once 'init.php';

try {
    TTransaction::open('database');
    $conn = TTransaction::get();
    
    echo "\n=== NOTIFICATION TABLE ===\n";
    $res = $conn->query("SELECT id, system_user_id, title, created_at FROM notification ORDER BY id DESC LIMIT 5");
    if ($res) {
        foreach ($res as $row) {
            echo "ID: {$row['id']} | User: {$row['system_user_id']} | Title: {$row['title']} | Date: {$row['created_at']}\n";
        }
    } else {
        echo "Error querying notification table.\n";
    }

    echo "\n=== SYSTEM_NOTIFICATION TABLE ===\n";
    $res = $conn->query("SELECT id, system_user_id, title, checked, dt_message FROM system_notification ORDER BY id DESC LIMIT 5");
    if ($res) {
        foreach ($res as $row) {
            echo "ID: {$row['id']} | User: {$row['system_user_id']} | Title: {$row['title']} | Checked: {$row['checked']} | Date: {$row['dt_message']}\n";
        }
    }

    echo "\n=== USUARIO vs SYSTEM_USER Comparison ===\n";
    
    echo "-- Table: usuario --\n";
    $res = $conn->query("SELECT id, nome, login, tipo FROM usuario LIMIT 10");
    if ($res) {
        foreach ($res as $row) {
            echo "ID: {$row['id']} | Login: {$row['login']} | Nome: {$row['nome']} | Tipo: {$row['tipo']}\n";
        }
    }

    echo "\n-- Table: system_user --\n";
    try {
        $res = $conn->query("SELECT id, name, login, active FROM system_user LIMIT 10");
        if ($res) {
            foreach ($res as $row) {
                echo "ID: {$row['id']} | Login: {$row['login']} | Name: {$row['name']} | Active: {$row['active']}\n";
            }
        }
    } catch (Exception $e) {
        echo "Table system_user not found or error: " . $e->getMessage() . "\n";
    }

    echo "\n=== SYSTEM_USER_GROUP ===\n";
    try {
        $res = $conn->query("SELECT system_user_id, system_group_id FROM system_user_group LIMIT 10");
        if ($res) {
            foreach ($res as $row) {
                echo "User ID: {$row['system_user_id']} | Group ID: {$row['system_group_id']}\n";
            }
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }

    TTransaction::close();

} catch (Exception $e) {
    echo "General Error: " . $e->getMessage() . "\n";
}
