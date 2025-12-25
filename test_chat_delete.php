<?php
// Test script to verify chat deletion functionality
// Run: php test_chat_delete.php

require_once 'init.php';

try {
    TTransaction::open('database');
    
    // Count messages
    $repo = new TRepository('Mensagem');
    $criteria = new TCriteria;
    $messages = $repo->load($criteria);
    
    echo "=== MENSAGENS NO BANCO ===\n";
    echo "Total: " . count($messages) . "\n\n";
    
    if ($messages) {
        foreach ($messages as $msg) {
            echo "ID: {$msg->id} | De: {$msg->system_user_id} | Para: {$msg->system_user_to_id} | Msg: " . substr($msg->message, 0, 30) . "...\n";
        }
    } else {
        echo "Nenhuma mensagem encontrada.\n";
    }
    
    TTransaction::close();
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}
