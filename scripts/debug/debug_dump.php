<?php
require_once 'init.php';

$output = "";

try {
    TTransaction::open('database');
    $conn = TTransaction::get();
    
    // List all tables
    $output .= "=== TABLES ===\n";
    $tables = $conn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $output .= implode(", ", $tables) . "\n\n";

    // Check System User Group
    $output .= "=== SYSTEM_USER_GROUP ===\n";
    try {
        $res = $conn->query("SELECT * FROM system_user_group LIMIT 5");
        foreach ($res as $row) {
             $output .= print_r($row, true) . "\n";
        }
    } catch (Exception $e) {
        $output .= "Error: " . $e->getMessage() . "\n";
    }

    // Check Usuario
    $output .= "\n=== USUARIO ===\n";
    $res = $conn->query("SELECT id, nome, login, tipo FROM usuario LIMIT 5");
    foreach ($res as $row) {
        $output .= "ID: {$row['id']} | Login: {$row['login']} | Type: {$row['tipo']}\n";
    }

    // Check System User
    $output .= "\n=== SYSTEM_USER ===\n";
    try {
        $res = $conn->query("SELECT id, name, login, active FROM system_user LIMIT 5");
        foreach ($res as $row) {
            $output .= "ID: {$row['id']} | Login: {$row['login']} | Name: {$row['name']}\n";
        }
    } catch (Exception $e) {
        $output .= "Error: " . $e->getMessage() . "\n";
    }

    TTransaction::close();

} catch (Exception $e) {
    $output .= "FATAL: " . $e->getMessage() . "\n";
}

file_put_contents('debug_output.txt', $output);
echo "Done.";
