<?php
require 'init.php';

TSession::setValue('usertype', 'gestor');

$page = new EntregaValidacao();
ob_start();
$page->onView(['id' => 1]); // Id 1 we know has summary
$page->show();
$output = ob_get_clean();

if (strpos($output, 'Resumo da Inteligência Artificial') !== false) {
    echo "ENCONTRADO!\n";
    // echo snippet around it
    $pos = strpos($output, 'Resumo da Inteligência Artificial');
    echo substr($output, max(0, $pos - 50), 200);
} else {
    echo "NAO ENCONTRADO!\n";
    // Check if Informações da Entrega is found
    if (strpos($output, 'Informações da Entrega') !== false) {
        echo "Informações da Entrega foi encontrado.\n";
    }
}
