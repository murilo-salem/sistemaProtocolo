<?php
require 'init.php';

try {
    TTransaction::open('database');
    $e = Entrega::last(); 
    echo "Entrega ID: {$e->id}\n";
    $docs = $e->get_documentos();
    echo "Total de documentos no JSON: " . count($docs) . "\n";
    
    foreach($docs as $nome => $caminho) {
        echo "[$nome] => $caminho\n";
        if (file_exists($caminho)) {
            echo "  -> Arquivo existe no disco.\n";
            if (strtolower(pathinfo($caminho, PATHINFO_EXTENSION)) === 'pdf') {
                echo "  -> É um PDF valido.\n";
            } else {
                echo "  -> NÃO é PDF.\n";
            }
        } else {
            echo "  -> ARQUIVO NAO ENCONTRADO.\n";
        }
    }
    TTransaction::close();
} catch (Exception $ex) {
    echo "Erro: " . $ex->getMessage() . "\n";
}
