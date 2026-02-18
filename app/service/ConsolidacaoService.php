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
                
                if ($extensao == 'pdf' && $useFPDI) {
                    // Contar páginas do PDF
                    try {
                        $tempPdf = new Fpdi();
                        $pageCount = $tempPdf->setSourceFile($doc_arquivo);
                        $this->paginacao[$doc_nome]['paginas'] = $pageCount;
                        $paginaAtual += $pageCount;
                    } catch (Exception $e) {
                        $this->erros[] = "Não foi possível ler páginas de: {$doc_nome}";
                        $paginaAtual += 1; // Placeholder page
                    }
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
            
            if ($extensao == 'pdf' && $useFPDI) {
                // Importar páginas do PDF
                try {
                    $pageCount = $pdf->setSourceFile($doc_arquivo);
                    
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
                } catch (Exception $e) {
                    $this->adicionarPaginaErro($pdf, $doc_nome, "Erro ao importar PDF: " . $e->getMessage());
                    $this->erros[] = "Erro ao importar {$doc_nome}: " . $e->getMessage();
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
     * Converte string UTF-8 para ISO-8859-1 (necessário para FPDF)
     */
    private function utf8($string)
    {
        return mb_convert_encoding($string, 'ISO-8859-1', 'UTF-8');
    }
}
