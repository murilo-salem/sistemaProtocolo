<?php
require_once 'init.php';

try {
    TTransaction::open('database');
    $conn = TTransaction::get();
    
    echo "Sucesso! Conectado ao banco de dados: " . $conn->getAttribute(PDO::ATTR_DRIVER_NAME) . "\n";
    
    $result = $conn->query("SELECT * FROM usuario LIMIT 1");
    $row = $result->fetch(PDO::FETCH_ASSOC);
    
    if ($row) {
        echo "Usuário encontrado: " . $row['login'] . "\n";
    } else {
        echo "Nenhum usuário encontrado na tabela.\n";
    }
    
    TTransaction::close();
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
