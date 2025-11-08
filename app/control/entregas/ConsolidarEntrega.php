<?php

class ConsolidarEntrega extends TPage
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
            
            // Criar PDF com TCPDF
            require_once('lib/tcpdf/tcpdf.php');
            
            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            
            // Configurações
            $pdf->SetCreator('Sistema de Gestão de Documentos');
            $pdf->SetAuthor($cliente->nome);
            $pdf->SetTitle("Entrega {$projeto->nome} - " . $entrega->mes_referencia . "/" . $entrega->ano_referencia);
            
            // Remover header/footer padrão
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            
            // Adicionar página de capa
            $pdf->AddPage();
            $pdf->SetFont('helvetica', 'B', 24);
            $pdf->Cell(0, 50, '', 0, 1);
            $pdf->Cell(0, 10, 'Entrega de Documentos', 0, 1, 'C');
            
            $pdf->SetFont('helvetica', '', 16);
            $pdf->Cell(0, 10, '', 0, 1);
            $pdf->Cell(0, 10, "Cliente: {$cliente->nome}", 0, 1, 'C');
            $pdf->Cell(0, 10, "Projeto: {$projeto->nome}", 0, 1, 'C');
            $pdf->Cell(0, 10, "Período: " . str_pad($entrega->mes_referencia, 2, '0', STR_PAD_LEFT) . "/" . $entrega->ano_referencia, 0, 1, 'C');
            $pdf->Cell(0, 10, "Data de Aprovação: " . date('d/m/Y', strtotime($entrega->data_aprovacao)), 0, 1, 'C');
            
            // Adicionar cada documento
            foreach ($documentos as $doc) {
                if (file_exists($doc['arquivo'])) {
                    $extensao = strtolower(pathinfo($doc['arquivo'], PATHINFO_EXTENSION));
                    
                    if ($extensao == 'pdf') {
                        // Importar PDF
                        $pageCount = $pdf->setSourceFile($doc['arquivo']);
                        for ($i = 1; $i <= $pageCount; $i++) {
                            $pdf->AddPage();
                            $tplId = $pdf->importPage($i);
                            $pdf->useTemplate($tplId);
                        }
                    } elseif (in_array($extensao, ['jpg', 'jpeg', 'png'])) {
                        // Adicionar imagem
                        $pdf->AddPage();
                        $pdf->Image($doc['arquivo'], 15, 15, 180);
                    }
                }
            }
            
            // Salvar PDF
            $pasta_consolidado = "files/consolidados/{$entrega->ano_referencia}/{$entrega->mes_referencia}";
            
            if (!is_dir($pasta_consolidado)) {
                mkdir($pasta_consolidado, 0777, true);
            }
            
            $nome_arquivo = "{$entrega->cliente_id}_{$entrega->projeto_id}_" . time() . ".pdf";
            $caminho_completo = "{$pasta_consolidado}/{$nome_arquivo}";
            
            $pdf->Output($caminho_completo, 'F');
            
            // Atualizar entrega
            $entrega->consolidado = 1;
            $entrega->arquivo_consolidado = $caminho_completo;
            $entrega->store();
            
            TTransaction::close();
            
            new TMessage('info', 'PDF consolidado gerado com sucesso!');
            TApplication::gotoPage('EntregaValidacao', 'onView', ['id' => $entrega->id]);
            
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
}