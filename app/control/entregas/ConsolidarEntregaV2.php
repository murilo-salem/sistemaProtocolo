<?php
/**
 * ConsolidarEntregaV2
 *
 * @version    1.0
 * @package    control
 * @subpackage entregas
 * @author     Antigravity
 * @copyright  Copyright (c) 2024
 */
class ConsolidarEntregaV2 extends TPage
{
    public static function onConsolidar($param)
    {
        try {
            TTransaction::open('database');
            
            $entrega = new Entrega($param['id']);
            
            if ($entrega->status != 'aprovado') {
                throw new Exception('Apenas entregas aprovadas podem ser consolidadas');
            }
            
            $cliente = new Usuario($entrega->cliente_id);
            $projeto = new Projeto($entrega->projeto_id);
            $documentos = $entrega->get_documentos();
            
            // FPDF Library Check
            if (!class_exists('FPDF')) {
                throw new Exception('Biblioteca FPDF não encontrada.');
            }
            
            $pasta_consolidado = "files/consolidados/{$entrega->ano_referencia}/{$entrega->mes_referencia}";
            
            if (!is_dir($pasta_consolidado)) {
                mkdir($pasta_consolidado, 0777, true);
            }
            
            // Use Temp File Strategy AND Output Buffering to prevent leaks
            $temp_file = 'tmp/pdf_consolidado_' . uniqid() . '.pdf';
            
            ob_start(); // Start buffer to catch any noise
            try {
                $pdf = new FPDF('P', 'mm', 'A4');
                $pdf->SetAutoPageBreak(true, 10);
                
                // --- Title Page ---
                $pdf->AddPage();
                
                // Border
                $pdf->Rect(5, 5, 200, 287, 'D');
                
                $pdf->SetFont('Arial', 'B', 24);
                $pdf->Cell(0, 40, '', 0, 1);
                $pdf->Cell(0, 10, 'Relatório Consolidado', 0, 1, 'C');
                
                $pdf->SetFont('Arial', '', 16);
                $pdf->Ln(20);
                
                $pdf->Cell(0, 10, 'Entrega de Documentos', 0, 1, 'C');
                $pdf->Ln(20);
                
                $pdf->SetFont('Arial', 'B', 14);
                $pdf->Cell(40, 10, 'Cliente:', 0, 0);
                $pdf->SetFont('Arial', '', 14);
                $pdf->Cell(0, 10, mb_convert_encoding($cliente->nome, 'ISO-8859-1', 'UTF-8'), 0, 1);
                
                $pdf->SetFont('Arial', 'B', 14);
                $pdf->Cell(40, 10, 'Projeto:', 0, 0);
                $pdf->SetFont('Arial', '', 14);
                $pdf->Cell(0, 10, mb_convert_encoding($projeto->nome, 'ISO-8859-1', 'UTF-8'), 0, 1);
                
                $pdf->SetFont('Arial', 'B', 14);
                $pdf->Cell(40, 10, mb_convert_encoding('Período:', 'ISO-8859-1', 'UTF-8'), 0, 0);
                $pdf->SetFont('Arial', '', 14);
                $pdf->Cell(0, 10, str_pad($entrega->mes_referencia, 2, '0', STR_PAD_LEFT) . "/" . $entrega->ano_referencia, 0, 1);
                
                $pdf->SetFont('Arial', 'B', 14);
                $pdf->Cell(40, 10, mb_convert_encoding('Aprovação:', 'ISO-8859-1', 'UTF-8'), 0, 0);
                $pdf->SetFont('Arial', '', 14);
                $pdf->Cell(0, 10, date('d/m/Y H:i', strtotime($entrega->data_aprovacao)), 0, 1);
                
                $pdf->Ln(30);
                $pdf->SetFont('Arial', 'I', 10);
                $pdf->Cell(0, 10, mb_convert_encoding('Gerado automaticamente pelo Sistema de Protocolo', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
                
                
                // --- Documents Loop ---
                if (!empty($documentos) && is_array($documentos)) {
                    foreach ($documentos as $doc_nome => $doc_arquivo) {
                        if (file_exists($doc_arquivo)) {
                            $extensao = strtolower(pathinfo($doc_arquivo, PATHINFO_EXTENSION));
                            $nome_arquivo = basename($doc_arquivo);
                            
                            if (in_array($extensao, ['jpg', 'jpeg', 'png', 'gif'])) {
                                $pdf->AddPage();
                                
                                // Header
                                $pdf->SetFont('Arial', 'B', 12);
                                $pdf->Cell(0, 10, mb_convert_encoding("Documento: " . $doc_nome, 'ISO-8859-1', 'UTF-8'), 0, 1);
                                $pdf->SetFont('Arial', '', 10);
                                $pdf->Cell(0, 5, mb_convert_encoding("Arquivo: " . $nome_arquivo, 'ISO-8859-1', 'UTF-8'), 0, 1);
                                $pdf->Ln(5);
                                
                                // Calculate Aspect Ratio to fit inside page (w=190, h=250 approx)
                                list($width, $height) = getimagesize($doc_arquivo);
                                
                                $max_w = 190;
                                $max_h = 240;
                                
                                // Logic to fit box
                                $ratio = $width / $height;
                                if ($max_w / $max_h > $ratio) {
                                    $max_w = $max_h * $ratio;
                                } else {
                                    $max_h = $max_w / $ratio;
                                }
                                
                                $pdf->Image($doc_arquivo, null, null, $max_w, $max_h);
                                
                            } elseif ($extensao == 'pdf') {
                                // Placeholder for PDFs
                                $pdf->AddPage();
                                $pdf->SetFont('Arial', 'B', 14);
                                $pdf->Cell(0, 10, mb_convert_encoding("Documento: " . $doc_nome, 'ISO-8859-1', 'UTF-8'), 0, 1);
                                $pdf->Ln(10);
                                
                                $pdf->SetFont('Arial', '', 12);
                                $pdf->MultiCell(0, 10, mb_convert_encoding("Este documento é um arquivo PDF anexo.\nArquivo: {$nome_arquivo}\n\nO sistema atual mescla apenas imagens no relatório consolidado. Para visualizar este documento, baixe o arquivo original individualmente.", 'ISO-8859-1', 'UTF-8'));
                            } else {
                                // Other formats
                                $pdf->AddPage();
                                $pdf->SetFont('Arial', 'B', 14);
                                $pdf->Cell(0, 10, mb_convert_encoding("Documento: " . $doc_nome, 'ISO-8859-1', 'UTF-8'), 0, 1);
                                $pdf->Ln(10);
                                $pdf->SetFont('Arial', '', 12);
                                $pdf->Cell(0, 10, mb_convert_encoding("Formato de arquivo ({$extensao}) não visualizável no relatório.", 'ISO-8859-1', 'UTF-8'), 0, 1);
                            }
                        }
                    }
                } // end if documentos
                
                $pdf->Output($temp_file, 'F');
                
            } catch (Exception $e) {
                // Ignore output errors if file was created? 
            }
            ob_end_clean(); // Discard whatever FPDF tried to print
            
            $nome_arquivo = "Consolidado_{$entrega->cliente_id}_{$entrega->projeto_id}_" . time() . ".pdf";
            $caminho_completo = "{$pasta_consolidado}/{$nome_arquivo}";
            
            if (file_exists($temp_file)) {
                if (file_exists($caminho_completo)) {
                    unlink($caminho_completo);
                }
                rename($temp_file, $caminho_completo);
            } else {
                 throw new Exception('Erro ao gerar arquivo temporário PDF.');
            }
            
            // Update Entity
            $entrega->consolidado = 1;
            $entrega->arquivo_consolidado = $caminho_completo;
            $entrega->store();
            
            TTransaction::close();
            
            new TMessage('info', 'Relatório consolidado gerado e salvo com sucesso!');
            // Reload page if needed, or redirect
            // TApplication::loadPage('EntregaList'); 
            
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
}
