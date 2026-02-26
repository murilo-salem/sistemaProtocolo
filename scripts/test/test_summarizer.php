<?php
require 'init.php';

try {
    // Let's test only the last delivery (where prompt failed to see 2nd doc)
    TTransaction::open('database');
    $e = Entrega::last(); 
    $id = $e->id;
    TTransaction::close();

    echo "Resumindo entrega $id...\n";
    $svc = new DocumentSummarizerService();
    $res = $svc->resumirEntrega($id);
    
    if ($res['success']) {
        TTransaction::open('database');
        $e = new Entrega($id);
        echo "Sucesso. Novo Resumo:\n" . substr($e->resumo_documentos, 0, 500) . "...\n";
        TTransaction::close();
    } else {
        echo "Erro: " . $res['message'];
    }
} catch (Exception $ex) {
    echo "Erro: " . $ex->getMessage() . "\n";
}
