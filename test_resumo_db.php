<?php
require_once 'init.php';

try {
    TTransaction::open('database');
    $entregas = Entrega::all();
    $found = false;
    foreach ($entregas as $e) {
        if (!empty($e->resumo_documentos)) {
            echo "Entrega ID: " . $e->id . " tem resumo:\n";
            echo substr($e->resumo_documentos, 0, 150) . "...\n";
            $found = true;
        }
    }
    if (!$found) {
        echo "Nenhuma entrega possui 'resumo_documentos' preenchido.\n";
    }
    TTransaction::close();
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
