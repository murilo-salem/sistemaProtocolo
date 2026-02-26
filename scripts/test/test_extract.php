<?php
require 'init.php';

$output = "";
try {
    TTransaction::open('database');
    $e = Entrega::last(); 
    $docs = $e->get_documentos();
    
    $parser = new \Smalot\PdfParser\Parser();
    $texto_completo = "";

    foreach($docs as $nome => $caminho) {
        if (file_exists($caminho) && strtolower(pathinfo($caminho, PATHINFO_EXTENSION)) === 'pdf') {
            $output .= "Extraindo de: $nome ($caminho)...\n";
            try {
                $pdf = $parser->parseFile($caminho);
                $texto = $pdf->getText();
                $length = strlen($texto);
                $output .= "  -> Sucesso! Tamanho: $length bytes\n";
                $texto_limitado = substr($texto, 0, 10000); // 10k max
                $texto_completo .= "--- Documento: {$nome} ---\n";
                $texto_completo .= $texto_limitado . "\n\n";
            } catch (Exception $ex) {
                $output .= "  -> Erro ao parsear: " . $ex->getMessage() . "\n";
            }
        } else {
            $output .= "Ignorando: $nome (Nao e PDF ou nao existe)\n";
        }
    }
    TTransaction::close();
    
    $output .= "\n\nTamanho total do texto enviado para IO: " . strlen($texto_completo) . " bytes\n";
    $output .= "\nPreview Texto:\n" . substr($texto_completo, 0, 300) . "\n...\n";
    
} catch (Exception $ex) {
    $output .= "Erro: " . $ex->getMessage() . "\n";
}

file_put_contents('test_extract.txt', $output);
