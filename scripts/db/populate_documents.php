<?php
// populate_documents.php

// Bootstrap
if (file_exists('init.php')) {
    require_once 'init.php';
} elseif (file_exists('../init.php')) {
    require_once '../init.php';
}

// Load FPDF manually if not autoloaded
if (!class_exists('FPDF') && file_exists('vendor/pablodalloglio/fpdf/src/Fpdf.php')) {
    require_once 'vendor/pablodalloglio/fpdf/src/Fpdf.php';
}

class PopulateDocuments
{
    public static function run()
    {
        try {
            TTransaction::open('database');
            
            echo "Iniciando geração de documentos...\n";
            
            // 1. Criar diretório de amostras
            $sampleDir = 'files/samples';
            if (!is_dir($sampleDir)) {
                mkdir($sampleDir, 0777, true);
            }
            
            // 2. Gerar PDFs base
            $baseFiles = [
                'balancete.pdf' => 'Balancete Financeiro',
                'comprovante.pdf' => 'Comprovante de Pagamento',
                'relatorio.pdf' => 'Relatório Operacional',
                'notafiscal.pdf' => 'Nota Fiscal Serviço',
            ];
            
            $generatedFiles = [];
            
            foreach ($baseFiles as $filename => $title) {
                $path = "{$sampleDir}/{$filename}";
                self::createDummyPDF($path, $title);
                $generatedFiles[$filename] = $path;
                echo "    + Arquivo gerado: {$path}\n";
            }
            
            // 3. Associar a Entregas
            $entregas = Entrega::getObjects();
            
            if ($entregas) {
                foreach ($entregas as $entrega) {
                    $docs = [];
                    
                    // Lógica variada de documentos por projeto
                    if (strpos($entrega->projeto->nome, 'Financeiro') !== false) {
                        $docs['Balancete'] = $generatedFiles['balancete.pdf'];
                        $docs['Comprovantes'] = $generatedFiles['comprovante.pdf'];
                    } elseif (strpos($entrega->projeto->nome, 'Auditoria') !== false) {
                        $docs['Relatório Preliminar'] = $generatedFiles['relatorio.pdf'];
                        $docs['Notas Fiscais'] = $generatedFiles['notafiscal.pdf'];
                    } else {
                        $docs['Documento Geral'] = $generatedFiles['relatorio.pdf'];
                    }
                    
                    $entrega->set_documentos($docs);
                    $entrega->store();
                    
                    echo "    > Entrega #{$entrega->id} ({$entrega->projeto->nome}) atualizada com " . count($docs) . " documentos.\n";
                }
            }
            
            TTransaction::close();
            echo "\nDocumentos populados com sucesso!\n";
            
        } catch (Exception $e) {
            echo "Erro: " . $e->getMessage() . "\n";
            TTransaction::rollback();
        }
    }
    
    private static function createDummyPDF($path, $title)
    {
        if (file_exists($path)) return;
        
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(40, 10, utf8_decode($title));
        $pdf->Ln(20);
        $pdf->SetFont('Arial', '', 12);
        $pdf->MultiCell(0, 10, utf8_decode("Este é um documento de exemplo gerado automaticamente para testes.\n\nData: " . date('d/m/Y H:i:s') . "\nID Único: " . uniqid()));
        $pdf->Output('F', $path);
    }
}

PopulateDocuments::run();
