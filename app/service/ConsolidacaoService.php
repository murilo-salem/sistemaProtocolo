<?php
/**
 * ConsolidacaoService
 *
 * Serviço responsável pela consolidação de documentos de uma entrega em um único PDF.
 * Gera sumário automático com indicação de páginas.
 *
 * @version    1.0
 * @package    service
 * @author     Antigravity
 * @copyright  Copyright (c) 2024
 */

// Require FPDI if available
if (file_exists('vendor/setasign/fpdi/src/autoload.php')) {
    require_once 'vendor/setasign/fpdi/src/autoload.php';
}

use setasign\Fpdi\Fpdi;

class ConsolidacaoService
{
    private $entrega;
    private $cliente;
    private $projeto;
    private $documentos;
    private $paginacao = [];
    private $erros = [];
    
    /**
     * Consolida todos os documentos de uma entrega em um único PDF
     *
     * @param int $entrega_id ID da entrega
     * @return array ['success' => bool, 'arquivo' => string, 'erros' => array]
     */
    public function consolidarEntrega($entrega_id)
    {
        try {
            TTransaction::open('database');
            
            $this->entrega = new Entrega($entrega_id);
            
            if ($this->entrega->status != 'aprovado') {
                throw new Exception('Apenas entregas aprovadas podem ser consolidadas');
            }
            
            $this->cliente = new Usuario($this->entrega->cliente_id);
            $this->projeto = new Projeto($this->entrega->projeto_id);
            $this->documentos = $this->entrega->get_documentos();
            
            if (empty($this->documentos)) {
                throw new Exception('Nenhum documento encontrado para consolidar.');
            }
            
            // Verificar se FPDI está disponível
            $useFPDI = class_exists('setasign\Fpdi\Fpdi');
            
            // Criar diretório de destino
            $pasta_consolidado = "files/consolidados/{$this->entrega->ano_referencia}/{$this->entrega->mes_referencia}";
            if (!is_dir($pasta_consolidado)) {
                mkdir($pasta_consolidado, 0777, true);
            }
            
            // Arquivo temporário
            $temp_file = 'tmp/pdf_consolidado_' . uniqid() . '.pdf';
            
            // Calcular paginação prévia
            $this->calcularPaginacao($useFPDI);
            
            // Gerar PDF
            ob_start();
            try {
                if ($useFPDI) {
                    $pdf = new Fpdi('P', 'mm', 'A4');
                } else {
                    $pdf = new FPDF('P', 'mm', 'A4');
                }
                $pdf->SetAutoPageBreak(true, 10);
                
                // Página de capa
                $this->adicionarCapa($pdf);
                
                // Página de sumário
                $this->adicionarSumario($pdf);
                
                // Processar documentos
                $this->processarDocumentos($pdf, $useFPDI);
                
                $pdf->Output($temp_file, 'F');
                
            } catch (Exception $e) {
                $this->erros[] = "Erro ao gerar PDF: " . $e->getMessage();
            }
            ob_end_clean();
            
            // Mover para destino final
            $nome_arquivo = "Consolidado_{$this->entrega->cliente_id}_{$this->entrega->projeto_id}_" . time() . ".pdf";
            $caminho_completo = "{$pasta_consolidado}/{$nome_arquivo}";
            
            if (file_exists($temp_file)) {
                if (file_exists($caminho_completo)) {
                    unlink($caminho_completo);
                }
                rename($temp_file, $caminho_completo);
            } else {
                throw new Exception('Erro ao gerar arquivo temporário PDF.');
            }
            
            // Atualizar entrega e salvar
            $this->entrega->consolidado = 1;
            $this->entrega->arquivo_consolidado = $caminho_completo;
            $this->entrega->store();
            
            // Notificar usuário
            $this->notificarCliente();
            
            // Notificar cliente via Sistema de Notificação Customizado
            NotificationService::notifyClient(
                $this->entrega->cliente_id,
                'Documentos Consolidados',
                "A consolidação dos documentos do projeto " . $this->entrega->projeto->nome . " foi concluída. O PDF está disponível para download.",
                'success',
                'entrega',
                $this->entrega->id,
                "class=ConsolidarEntregaV2&method=onDownload&id={$this->entrega->id}"
            );
            
            // Notificar via Barra Superior (Legacy/System)
            if (class_exists('SystemNotification')) {
                $subject = "Consolidação Disponível: " . str_pad($this->entrega->mes_referencia, 2, '0', STR_PAD_LEFT) . "/" . $this->entrega->ano_referencia;
                $msg_body = "O relatório consolidado da sua entrega referente a " . str_pad($this->entrega->mes_referencia, 2, '0', STR_PAD_LEFT) . "/" . $this->entrega->ano_referencia . " já está disponível para download.";
                
                SystemNotification::register(
                    $this->entrega->cliente_id,
                    $subject,
                    $msg_body,
                    "class=ConsolidarEntregaV2&method=onDownload&id={$this->entrega->id}",
                    'Baixar PDF',
                    'fa fa-download'
                );
            }
            
            TTransaction::close();
            
            return [
                'success' => true,
                'arquivo' => $caminho_completo,
                'erros' => $this->erros
            ];
            
        } catch (Exception $e) {
            TTransaction::rollback();
            return [
                'success' => false,
                'arquivo' => null,
                'erros' => [$e->getMessage()]
            ];
        }
    }
    
    /**
     * Calcula a paginação prévia de todos os documentos
     */
    private function calcularPaginacao($useFPDI)
    {
        // Página 1: Capa
        // Página 2: Sumário (assumindo 1 página por enquanto)
        $paginaAtual = 3;
        
        foreach ($this->documentos as $doc_nome => $doc_arquivo) {
            $this->paginacao[$doc_nome] = [
                'arquivo' => $doc_arquivo,
                'pagina_inicial' => $paginaAtual,
                'paginas' => 1 // Default
            ];
            
            if (file_exists($doc_arquivo)) {
                $extensao = strtolower(pathinfo($doc_arquivo, PATHINFO_EXTENSION));
                
                if ($extensao == 'pdf') {
                    // Contar páginas do PDF usando método robusto
                    $pageCount = $this->contarPaginasPdf($doc_arquivo);
                    $this->paginacao[$doc_nome]['paginas'] = $pageCount;
                    $paginaAtual += $pageCount;
                } elseif (in_array($extensao, ['jpg', 'jpeg', 'png', 'gif'])) {
                    // Imagens = 1 página cada
                    $paginaAtual += 1;
                } else {
                    // Outros formatos = 1 página placeholder
                    $paginaAtual += 1;
                }
            } else {
                $this->erros[] = "Arquivo não encontrado: {$doc_arquivo}";
            }
        }
    }
    
    /**
     * Adiciona página de capa ao PDF
     */
    private function adicionarCapa($pdf)
    {
        $pdf->AddPage();
        
        // Borda decorativa
        $pdf->SetDrawColor(0, 102, 153);
        $pdf->SetLineWidth(1);
        $pdf->Rect(10, 10, 190, 277, 'D');
        $pdf->SetLineWidth(0.5);
        $pdf->Rect(12, 12, 186, 273, 'D');
        
        // Título principal
        $pdf->SetFont('Arial', 'B', 28);
        $pdf->SetTextColor(0, 51, 102);
        $pdf->Ln(50);
        $pdf->Cell(0, 15, $this->utf8('Relatório Consolidado'), 0, 1, 'C');
        
        // Subtítulo
        $pdf->SetFont('Arial', '', 18);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Ln(5);
        $pdf->Cell(0, 10, $this->utf8('Entrega de Documentos'), 0, 1, 'C');
        
        // Linha divisória
        $pdf->Ln(15);
        $pdf->SetDrawColor(0, 102, 153);
        $pdf->Line(50, $pdf->GetY(), 160, $pdf->GetY());
        $pdf->Ln(15);
        
        // Informações
        $pdf->SetTextColor(0, 0, 0);
        $this->adicionarInfoCapa($pdf, 'Cliente:', $this->cliente->nome ?? 'N/A');
        $this->adicionarInfoCapa($pdf, 'Projeto:', $this->projeto->nome ?? 'N/A');
        $this->adicionarInfoCapa($pdf, $this->utf8('Período:'), str_pad($this->entrega->mes_referencia, 2, '0', STR_PAD_LEFT) . '/' . $this->entrega->ano_referencia);
        
        if ($this->entrega->data_aprovacao) {
            $this->adicionarInfoCapa($pdf, $this->utf8('Aprovação:'), date('d/m/Y H:i', strtotime($this->entrega->data_aprovacao)));
        }
        
        $this->adicionarInfoCapa($pdf, 'Total de Documentos:', count($this->documentos));
        
        // Rodapé
        $pdf->SetY(260);
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->SetTextColor(128, 128, 128);
        $pdf->Cell(0, 10, $this->utf8('Gerado automaticamente pelo Sistema de Protocolo em ' . date('d/m/Y H:i')), 0, 1, 'C');
    }
    
    /**
     * Adiciona uma linha de informação na capa
     */
    private function adicionarInfoCapa($pdf, $label, $valor)
    {
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(60, 10, $this->utf8($label), 0, 0, 'R');
        $pdf->SetFont('Arial', '', 14);
        $pdf->Cell(0, 10, '  ' . $this->utf8($valor), 0, 1);
    }
    
    /**
     * Adiciona página de sumário ao PDF
     */
    private function adicionarSumario($pdf)
    {
        $pdf->AddPage();
        
        // Título
        $pdf->SetFont('Arial', 'B', 20);
        $pdf->SetTextColor(0, 51, 102);
        $pdf->Cell(0, 15, $this->utf8('Sumário'), 0, 1, 'C');
        $pdf->Ln(10);
        
        // Linha divisória
        $pdf->SetDrawColor(0, 102, 153);
        $pdf->Line(20, $pdf->GetY(), 190, $pdf->GetY());
        $pdf->Ln(10);
        
        // Lista de documentos
        $pdf->SetTextColor(0, 0, 0);
        $contador = 1;
        
        foreach ($this->paginacao as $doc_nome => $info) {
            $pdf->SetFont('Arial', '', 12);
            
            // Número e nome do documento
            $texto = "{$contador}. " . $doc_nome;
            $pagina = "pág. {$info['pagina_inicial']}";
            
            // Calcular largura disponível para pontos
            $larguraTexto = $pdf->GetStringWidth($texto);
            $larguraPagina = $pdf->GetStringWidth($pagina);
            $larguraDisponivel = 170 - $larguraTexto - $larguraPagina;
            
            // Criar linha pontilhada
            $pontos = '';
            $larguraPonto = $pdf->GetStringWidth('.');
            $qtdPontos = max(3, floor($larguraDisponivel / $larguraPonto));
            $pontos = str_repeat('.', $qtdPontos);
            
            $pdf->Cell($larguraTexto + 5, 8, $this->utf8($texto), 0, 0);
            $pdf->Cell($larguraDisponivel, 8, $pontos, 0, 0, 'C');
            $pdf->Cell($larguraPagina, 8, $pagina, 0, 1, 'R');
            
            $contador++;
            
            // Verificar se precisa nova página
            if ($pdf->GetY() > 270) {
                $pdf->AddPage();
                $pdf->SetFont('Arial', 'B', 16);
                $pdf->Cell(0, 10, $this->utf8('Sumário (continuação)'), 0, 1, 'C');
                $pdf->Ln(5);
            }
        }
        
        // Nota sobre formatos
        if (!empty($this->erros)) {
            $pdf->Ln(15);
            $pdf->SetFont('Arial', 'I', 10);
            $pdf->SetTextColor(150, 100, 0);
            $pdf->MultiCell(0, 5, $this->utf8('Nota: Alguns documentos podem não ter sido incorporados completamente. Verifique os arquivos originais se necessário.'));
        }
    }
    
    /**
     * Processa e adiciona todos os documentos ao PDF
     */
    private function processarDocumentos($pdf, $useFPDI)
    {
        foreach ($this->documentos as $doc_nome => $doc_arquivo) {
            if (!file_exists($doc_arquivo)) {
                $this->adicionarPaginaErro($pdf, $doc_nome, "Arquivo não encontrado");
                continue;
            }
            
            $extensao = strtolower(pathinfo($doc_arquivo, PATHINFO_EXTENSION));
            $nome_arquivo = basename($doc_arquivo);
            
            if ($extensao == 'pdf') {
                // Importar páginas do PDF
                $pdfImportado = false;
                $arquivoParaImportar = $doc_arquivo;
                $arquivoTemp = null;
                
                if ($useFPDI) {
                    // Tentativa 1: importar diretamente com FPDI
                    try {
                        $this->importarPaginasPdf($pdf, $arquivoParaImportar, $doc_nome);
                        $pdfImportado = true;
                    } catch (Exception $e) {
                        // FPDI falhou — tentar pré-processar com Ghostscript
                        $arquivoTemp = $this->preprocessarPdfComGhostscript($doc_arquivo);
                        
                        if ($arquivoTemp) {
                            // Tentativa 2: importar o PDF pré-processado
                            try {
                                $this->importarPaginasPdf($pdf, $arquivoTemp, $doc_nome);
                                $pdfImportado = true;
                            } catch (Exception $e2) {
                                $pdfImportado = false;
                            }
                        }
                    }
                }
                
                // Fallback final: renderizar páginas placeholder
                if (!$pdfImportado) {
                    $totalPaginas = $this->contarPaginasPdf($doc_arquivo);
                    
                    for ($p = 1; $p <= $totalPaginas; $p++) {
                        $pdf->AddPage();
                        
                        if ($p == 1) {
                            $pdf->SetFont('Arial', 'B', 12);
                            $pdf->SetTextColor(0, 51, 102);
                            $pdf->Cell(0, 8, $this->utf8("Documento: {$doc_nome}"), 0, 1);
                            $pdf->SetFont('Arial', '', 9);
                            $pdf->SetTextColor(128, 128, 128);
                            $pdf->Cell(0, 5, $this->utf8("Arquivo: {$nome_arquivo}"), 0, 1);
                            $pdf->Ln(3);
                        }
                        
                        $pdf->SetFillColor(240, 248, 255);
                        $pdf->Rect(20, $pdf->GetY() + 5, 170, 60, 'F');
                        $pdf->SetDrawColor(0, 102, 153);
                        $pdf->Rect(20, $pdf->GetY() + 5, 170, 60, 'D');
                        
                        $pdf->SetY($pdf->GetY() + 15);
                        $pdf->SetFont('Arial', 'B', 14);
                        $pdf->SetTextColor(0, 51, 102);
                        $pdf->Cell(0, 10, $this->utf8("Página {$p} de {$totalPaginas}"), 0, 1, 'C');
                        
                        $pdf->Ln(3);
                        $pdf->SetFont('Arial', '', 11);
                        $pdf->SetTextColor(80, 80, 80);
                        $pdf->MultiCell(0, 6, $this->utf8(
                            "Este documento PDF utiliza compressão avançada e não pôde ser incorporado diretamente.\n" .
                            "Para visualizar o conteúdo completo, baixe o arquivo original do sistema."
                        ), 0, 'C');
                    }
                }
                
                // Limpar arquivo temporário
                if ($arquivoTemp && file_exists($arquivoTemp)) {
                    @unlink($arquivoTemp);
                }
                
            } elseif (in_array($extensao, ['jpg', 'jpeg', 'png', 'gif'])) {
                // Adicionar imagem
                $pdf->AddPage();
                
                // Cabeçalho
                $pdf->SetFont('Arial', 'B', 12);
                $pdf->SetTextColor(0, 51, 102);
                $pdf->Cell(0, 8, $this->utf8("Documento: {$doc_nome}"), 0, 1);
                $pdf->SetFont('Arial', '', 9);
                $pdf->SetTextColor(128, 128, 128);
                $pdf->Cell(0, 5, $this->utf8("Arquivo: {$nome_arquivo}"), 0, 1);
                $pdf->Ln(3);
                
                // Calcular proporção da imagem
                list($width, $height) = getimagesize($doc_arquivo);
                
                $max_w = 190;
                $max_h = 240;
                
                $ratio = $width / $height;
                if ($max_w / $max_h > $ratio) {
                    $max_w = $max_h * $ratio;
                } else {
                    $max_h = $max_w / $ratio;
                }
                
                // Centralizar imagem
                $x = (210 - $max_w) / 2;
                
                $pdf->Image($doc_arquivo, $x, null, $max_w, $max_h);
                
            } else {
                // Formato não suportado - adicionar placeholder
                $this->adicionarPaginaPlaceholder($pdf, $doc_nome, $nome_arquivo, $extensao);
            }
        }
    }
    
    /**
     * Adiciona página de erro
     */
    private function adicionarPaginaErro($pdf, $doc_nome, $mensagem)
    {
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->SetTextColor(180, 0, 0);
        $pdf->Cell(0, 10, $this->utf8("Documento: {$doc_nome}"), 0, 1);
        $pdf->Ln(10);
        $pdf->SetFont('Arial', '', 12);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->MultiCell(0, 8, $this->utf8($mensagem));
    }
    
    /**
     * Adiciona página placeholder para formatos não suportados
     */
    private function adicionarPaginaPlaceholder($pdf, $doc_nome, $nome_arquivo, $extensao)
    {
        $pdf->AddPage();
        
        // Caixa de aviso
        $pdf->SetFillColor(255, 248, 220);
        $pdf->Rect(20, 50, 170, 80, 'F');
        $pdf->SetDrawColor(200, 180, 100);
        $pdf->Rect(20, 50, 170, 80, 'D');
        
        $pdf->SetY(60);
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->SetTextColor(150, 100, 0);
        $pdf->Cell(0, 10, $this->utf8("Documento: {$doc_nome}"), 0, 1, 'C');
        
        $pdf->Ln(5);
        $pdf->SetFont('Arial', '', 12);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->MultiCell(0, 8, $this->utf8("Este documento está em formato {$extensao} e não pode ser visualizado diretamente no relatório consolidado.\n\nArquivo: {$nome_arquivo}\n\nPara visualizar este documento, baixe o arquivo original individualmente do sistema."), 0, 'C');
    }
    
    /**
     * Importa páginas de um PDF usando FPDI
     * 
     * @param Fpdi $pdf Instância do FPDI
     * @param string $arquivo Caminho do arquivo PDF
     * @param string $doc_nome Nome do documento para o cabeçalho
     */
    private function importarPaginasPdf($pdf, $arquivo, $doc_nome)
    {
        $pageCount = $pdf->setSourceFile($arquivo);
        
        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $pdf->AddPage();
            
            // Cabeçalho na primeira página do documento
            if ($pageNo == 1) {
                $pdf->SetFont('Arial', 'B', 10);
                $pdf->SetTextColor(100, 100, 100);
                $pdf->Cell(0, 5, $this->utf8("Documento: {$doc_nome}"), 0, 1);
                $pdf->SetDrawColor(200, 200, 200);
                $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
                $pdf->Ln(2);
            }
            
            $templateId = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($templateId);
            
            // Calcular escala para caber na página
            $wRatio = 190 / $size['width'];
            $hRatio = 265 / $size['height'];
            $ratio = min($wRatio, $hRatio);
            
            $newWidth = $size['width'] * $ratio;
            $newHeight = $size['height'] * $ratio;
            
            // Centralizar
            $x = (210 - $newWidth) / 2;
            $y = $pdf->GetY() + 2;
            
            $pdf->useTemplate($templateId, $x, $y, $newWidth, $newHeight);
        }
    }
    
    /**
     * Encontra o executável do Ghostscript no sistema
     * 
     * @return string|null Caminho do executável ou null se não encontrado
     */
    private function encontrarGhostscript()
    {
        // Caminhos comuns no Windows
        $possiveisCaminhos = [];
        
        // Procurar em Program Files
        $programFiles = ['C:\\Program Files\\gs', 'C:\\Program Files (x86)\\gs'];
        foreach ($programFiles as $base) {
            if (is_dir($base)) {
                $dirs = @scandir($base);
                if ($dirs) {
                    foreach ($dirs as $dir) {
                        if (strpos($dir, 'gs') === 0 && $dir !== '.' && $dir !== '..') {
                            $possiveisCaminhos[] = "{$base}\\{$dir}\\bin\\gswin64c.exe";
                            $possiveisCaminhos[] = "{$base}\\{$dir}\\bin\\gswin32c.exe";
                        }
                    }
                }
            }
        }
        
        // Verificar qual existe
        foreach ($possiveisCaminhos as $caminho) {
            if (file_exists($caminho)) {
                return $caminho;
            }
        }
        
        // Tentar via PATH do sistema
        $output = [];
        @exec('gswin64c --version 2>&1', $output, $retval);
        if ($retval === 0) {
            return 'gswin64c';
        }
        
        @exec('gswin32c --version 2>&1', $output, $retval);
        if ($retval === 0) {
            return 'gswin32c';
        }
        
        @exec('gs --version 2>&1', $output, $retval);
        if ($retval === 0) {
            return 'gs';
        }
        
        return null;
    }
    
    /**
     * Pré-processa um PDF com Ghostscript para torná-lo compatível com FPDI
     * Converte para PDF 1.4 (sem cross-reference streams comprimidos)
     * 
     * @param string $arquivo Caminho do PDF original
     * @return string|null Caminho do PDF processado ou null se falhar
     */
    private function preprocessarPdfComGhostscript($arquivo)
    {
        $gs = $this->encontrarGhostscript();
        if (!$gs) {
            return null;
        }
        
        $arquivoTemp = 'tmp/gs_converted_' . uniqid() . '.pdf';
        
        // Garantir que o diretório tmp existe
        if (!is_dir('tmp')) {
            mkdir('tmp', 0777, true);
        }
        
        // Converter para PDF 1.4 (compatível com FPDI free parser)
        $gsCmd = sprintf(
            '"%s" -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dNOPAUSE -dQUIET -dBATCH -sOutputFile="%s" "%s" 2>&1',
            $gs,
            $arquivoTemp,
            $arquivo
        );
        
        $output = [];
        exec($gsCmd, $output, $retval);
        
        if ($retval === 0 && file_exists($arquivoTemp) && filesize($arquivoTemp) > 0) {
            return $arquivoTemp;
        }
        
        // Falha — limpar
        if (file_exists($arquivoTemp)) {
            @unlink($arquivoTemp);
        }
        
        return null;
    }
    
    /**
     * Notifica o cliente sobre a consolidação
     */
    private function notificarCliente()
    {
        try {
            $subject = "Consolidação Disponível: " . str_pad($this->entrega->mes_referencia, 2, '0', STR_PAD_LEFT) . "/" . $this->entrega->ano_referencia;
            $msg_body = "O relatório consolidado da sua entrega referente a " . str_pad($this->entrega->mes_referencia, 2, '0', STR_PAD_LEFT) . "/" . $this->entrega->ano_referencia . " já está disponível para download.";
            
            if (class_exists('NotificationService')) {
                NotificationService::send(TSession::getValue('userid'), $this->entrega->cliente_id, $subject, $msg_body);
            }
            
            if (class_exists('SystemNotification')) {
                SystemNotification::register(
                    $this->entrega->cliente_id,
                    $subject,
                    $msg_body,
                    'class=EntregaList',
                    'Ver Entregas',
                    'fa fa-file-pdf-o'
                );
            }
        } catch (Exception $e) {
            // Ignorar erros de notificação
        }
    }
    
    /**
     * Conta o número de páginas de um arquivo PDF
     * Usa smalot/pdfparser como estratégia principal e regex como fallback
     *
     * @param string $arquivo Caminho do arquivo PDF
     * @return int Número de páginas
     */
    private function contarPaginasPdf($arquivo)
    {
        // Estratégia 1: smalot/pdfparser (suporta todos os tipos de PDF)
        if (class_exists('\\Smalot\\PdfParser\\Parser')) {
            try {
                $parser = new \Smalot\PdfParser\Parser();
                $pdfDoc = $parser->parseFile($arquivo);
                $pages = $pdfDoc->getPages();
                $count = count($pages);
                if ($count > 0) {
                    return $count;
                }
            } catch (\Exception $e) {
                // Falha no parser — tentar fallback
            }
        }
        
        // Estratégia 2: Regex no conteúdo bruto do PDF
        $content = @file_get_contents($arquivo);
        if ($content !== false) {
            // Procurar /Count N no catálogo de páginas (mais confiável)
            if (preg_match('/\/Count\s+(\d+)/', $content, $matches)) {
                $count = intval($matches[1]);
                if ($count > 0) {
                    return $count;
                }
            }
            // Fallback: contar objetos /Type /Page (excluindo /Type /Pages)
            if (preg_match_all('/\/Type\s*\/Page[^s]/i', $content, $matches)) {
                return count($matches[0]);
            }
        }
        
        return 1; // Default mínimo
    }
    
    /**
     * Converte string UTF-8 para ISO-8859-1 (necessário para FPDF)
     */
    private function utf8($string)
    {
        return mb_convert_encoding($string, 'ISO-8859-1', 'UTF-8');
    }
}
