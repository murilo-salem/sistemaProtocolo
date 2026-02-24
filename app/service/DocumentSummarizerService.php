<?php

class DocumentSummarizerService
{
    /**
     * Resuma os documentos de uma entrega usando o Ollama
     * 
     * @param int $entrega_id ID da entrega
     * @return array Resultado da operação ['success' => bool, 'message' => string]
     */
    public function resumirEntrega($entrega_id)
    {
        // Desativa o limite de tempo de execução do PHP para esta requisição, 
        // já que modelos locais (como o gemma2:2b) podem demorar vários minutos processando PDFs grandes.
        set_time_limit(0);
        
        try {
            TTransaction::open('database');
            $entrega = new Entrega($entrega_id);
            
            if (!$entrega) {
                throw new Exception('Entrega não encontrada.');
            }
            
            $documentos = $entrega->get_documentos();
            if (empty($documentos)) {
                throw new Exception('Nenhum documento encontrado nesta entrega.');
            }

            $texto_completo = '';
            $parser = new \Smalot\PdfParser\Parser();

            foreach ($documentos as $nome => $caminho) {
                if (file_exists($caminho)) {
                    $ext = strtolower(pathinfo($caminho, PATHINFO_EXTENSION));
                    if ($ext === 'pdf') {
                        try {
                            $pdf = $parser->parseFile($caminho);
                            $texto = $pdf->getText();
                            
                            $texto_limitado = substr($texto, 0, 10000); // Primeiros 10k chars do doc
                            
                            $texto_completo .= "--- Documento: {$nome} (PDF) ---\n";
                            $texto_completo .= $texto_limitado . "\n\n";
                        } catch (Exception $e) {
                             $texto_completo .= "--- Documento: {$nome} (PDF) ---\n";
                             $texto_completo .= "[Erro ao extrair texto deste arquivo PDF]\n\n";
                        }
                    } elseif (in_array($ext, ['png', 'jpg', 'jpeg', 'webp'])) {
                        try {
                            $base64 = base64_encode(file_get_contents($caminho));
                            $visao_texto = $this->extractImageTextOllama($base64);
                            
                            $texto_limitado = substr($visao_texto, 0, 10000); 
                            
                            $texto_completo .= "--- Documento: {$nome} (Imagem) ---\n";
                            $texto_completo .= $texto_limitado . "\n\n";
                        } catch (Exception $e) {
                             $texto_completo .= "--- Documento: {$nome} (Imagem) ---\n";
                             $texto_completo .= "[Erro ao processar visão computacional nesta imagem: " . $e->getMessage() . "]\n\n";
                        }
                    }
                }
            }

            if (empty(trim($texto_completo))) {
                throw new Exception('Não foi possível extrair texto dos documentos (apenas PDFs são suportados).');
            }

            TTransaction::close();

            // Interagir com Ollama
            $resumo = $this->callOllama($texto_completo);

            // Salvar no banco
            TTransaction::open('database');
            $entrega = new Entrega($entrega_id);
            $entrega->resumo_documentos = $resumo;
            $entrega->store();
            TTransaction::close();

            return ['success' => true, 'message' => 'Resumo gerado com sucesso.'];

        } catch (Exception $e) {
            TTransaction::rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function callOllama($texto)
    {
        $prompt = "Você é um assistente especializado em análise de documentos corporativos. " .
                  "Abaixo estão textos extraídos de UM OU MAIS arquivos enviados pelo cliente. " .
                  "Analise o texto e forneça um resumo claro e objetivo apontando os principais dados e a finalidade de CADA UM dos arquivos. " .
                  "É extremamente importante que você resuma TODOS os documentos passados na lista, um por um. " .
                  "Não invente informações e mantenha a formatação em Markdown usando tópicos curtos.\n\n" .
                  "Textos a serem resumidos:\n\n" . $texto;

        $host = getenv('OLLAMA_HOST') ?: '127.0.0.1';
        $url = "http://{$host}:11434/api/generate";
        
        $data = [
            'model' => 'gemma2:2b',
            'prompt' => $prompt,
            'stream' => false,
            'options' => [
                'num_ctx' => 8192
            ]
        ];
        
        $options = [
            'http' => [
                'header'  => "Content-type: application/json\r\n",
                'method'  => 'POST',
                'content' => json_encode($data),
                'timeout' => 900, // 15 minutes timeout for LLM generation
                'ignore_errors' => true
            ]
        ];
        
        $context  = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);
        
        if ($result === FALSE) {
            $error = error_get_last();
            throw new Exception("Falha ao comunicar com a API do Ollama local em {$url}. O serviço está rodando? Erro: " . ($error['message'] ?? 'Desconhecido'));
        }
        
        $response = json_decode($result, true);
        
        if (isset($response['error'])) {
             throw new Exception("Erro retornado pelo Ollama: " . $response['error']);
        }
        
        if (!isset($response['response']) || trim($response['response']) === '') {
            throw new Exception("Resposta vazia do Ollama.");
        }
        
        return trim($response['response']);
    }

    private function extractImageTextOllama($base64_image)
    {
        // Moondream works best with English prompts. Gemma2 will later translate this into the final Portuguese summary.
        $prompt = "Describe this image in detail and extract any text you see written in it. If there is a document in the image, transcribe its contents.";

        $host = getenv('OLLAMA_HOST') ?: '127.0.0.1';
        $url = "http://{$host}:11434/api/generate";
        
        $data = [
            'model' => 'moondream',
            'prompt' => $prompt,
            'images' => [$base64_image],
            'stream' => false,
            'options' => [
                'num_ctx' => 4096
            ]
        ];
        
        $options = [
            'http' => [
                'header'  => "Content-type: application/json\r\n",
                'method'  => 'POST',
                'content' => json_encode($data),
                'timeout' => 300, // 5 minutes timeout for image extraction
                'ignore_errors' => true
            ]
        ];
        
        $context  = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);
        
        if ($result === FALSE) {
            $error = error_get_last();
            throw new Exception("Falha ao comunicar com Ollama (Moondream). Erro: " . ($error['message'] ?? 'Desconhecido'));
        }
        
        $response = json_decode($result, true);
        
        if (isset($response['error'])) {
             throw new Exception("Erro do Ollama (visão): " . $response['error']);
        }
        
        return trim($response['response'] ?? '');
    }
}
