<?php
require 'init.php';

try {
    $caminho = 'app/uploads/projetos/8/4/2026-02-23_15-00-46/A1.png';
    $base64 = base64_encode(file_get_contents($caminho));
    
    $url = 'http://127.0.0.1:11434/api/generate';
    $data = [
        'model' => 'moondream',
        'prompt' => 'Describe this image in detail and extract any text you see.',
        'images' => [$base64],
        'stream' => false,
    ];
    
    $options = [
        'http' => [
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'POST',
            'content' => json_encode($data),
            'timeout' => 300 
        ]
    ];
    
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    
    echo "SUCESSO! Resposta:\n";
    $json = json_decode($result, true);
    var_dump($json['response']);
    
} catch (Exception $ex) {
    echo "ERRO: " . $ex->getMessage() . "\n";
}
