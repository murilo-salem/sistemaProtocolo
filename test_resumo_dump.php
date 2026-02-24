<?php
require_once 'init.php';

try {
    TTransaction::open('database');
    $entregas = Entrega::all();
    $found = false;
    foreach ($entregas as $e) {
        if (!empty($e->resumo_documentos)) {
            echo "--- Entrega ID: " . $e->id . " ---\n";
            echo "Tamanho do resumo: " . strlen($e->resumo_documentos) . " bytes\n";
            echo "Conteúdo bruto:\n";
            var_dump($e->resumo_documentos);
            echo "\n-----------------------\n";
            $found = true;
        }
    }
    
    if (!$found) {
        echo "NENHUMA entrega possui resumo_documentos preenchido no banco de dados.\n";
        
        // Vamos checar a ultima entrega pra ver se o status
        $ultima = Entrega::last();
        if ($ultima) {
            echo "A última entrega no banco é a ID: " . $ultima->id . "\n";
            echo "Tamanho do resumo dela: " . strlen((string)$ultima->resumo_documentos) . " bytes\n";
            var_dump($ultima->resumo_documentos);
        }
    }
    
    TTransaction::close();
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
