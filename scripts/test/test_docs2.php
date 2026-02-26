<?php
require 'init.php';

$output = "";
try {
    TTransaction::open('database');
    $e = Entrega::last(); 
    $output .= "Entrega ID: {$e->id}\n";
    $docs = $e->get_documentos();
    $output .= "Total de documentos no JSON: " . count($docs) . "\n";
    
    foreach($docs as $nome => $caminho) {
        $output .= "[$nome] => $caminho\n";
        if (file_exists($caminho)) {
            $output .= "  -> Arquivo existe no disco.\n";
            if (strtolower(pathinfo($caminho, PATHINFO_EXTENSION)) === 'pdf') {
                $output .= "  -> É um PDF valido.\n";
            } else {
                $output .= "  -> NÃO é PDF.\n";
            }
        } else {
            $output .= "  -> ARQUIVO NAO ENCONTRADO.\n";
        }
    }
    TTransaction::close();
} catch (Exception $ex) {
    $output .= "Erro: " . $ex->getMessage() . "\n";
}

file_put_contents('test_docs_out.txt', $output);
