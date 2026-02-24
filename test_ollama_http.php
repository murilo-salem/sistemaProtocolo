<?php
require_once 'init.php';

$host = getenv('OLLAMA_HOST') ?: '127.0.0.1';
$url = "http://{$host}:11434/api/generate";
        
$data = [
    'model' => 'gemma2:2b',
    'prompt' => 'Diga apenas: Teste concluído com sucesso.',
    'stream' => false
];

$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => json_encode($data),
        'timeout' => 60,
        'ignore_errors' => true,
    ]
];

$context  = stream_context_create($options);

echo "Iniciando teste de conexao com Ollama...\n";
$result = @file_get_contents($url, false, $context);

if ($result === FALSE) {
    $error = error_get_last();
    echo "Erro: " . ($error['message'] ?? 'Desconhecido') . "\n";
} else {
    $response = json_decode($result, true);
    echo "Sucesso!\n";
    echo "Resposta: " . $response['response'] . "\n";
}
