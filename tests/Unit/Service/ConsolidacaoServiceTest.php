<?php

namespace Tests\Unit\Service;

use ConsolidacaoService;
use Entrega;
use ReflectionClass;
use Tests\TestCase;

class ConsolidacaoServiceTest extends TestCase
{
    private function setPrivate(object $target, string $property, $value): void
    {
        $ref = new ReflectionClass($target);
        $prop = $ref->getProperty($property);
        $prop->setAccessible(true);
        $prop->setValue($target, $value);
    }

    private function getPrivate(object $target, string $property)
    {
        $ref = new ReflectionClass($target);
        $prop = $ref->getProperty($property);
        $prop->setAccessible(true);
        return $prop->getValue($target);
    }

    private function callPrivate(object $target, string $method, array $args = [])
    {
        $ref = new ReflectionClass($target);
        $m = $ref->getMethod($method);
        $m->setAccessible(true);
        return $m->invokeArgs($target, $args);
    }

    public function testConsolidarEntregaFailsWhenStatusIsNotApproved(): void
    {
        $this->seedRecords(Entrega::class, [
            (object) [
                'id' => 1,
                'cliente_id' => 1,
                'projeto_id' => 1,
                'status' => 'pendente',
                'documentos_json' => '{"Doc":"a.pdf"}',
                'mes_referencia' => 2,
                'ano_referencia' => 2026,
            ],
        ]);

        $service = new ConsolidacaoService();
        $result = $service->consolidarEntrega(1);

        $this->assertFalse($result['success']);
        $this->assertSame('Apenas entregas aprovadas podem ser consolidadas', $result['erros'][0]);
    }

    public function testConsolidarEntregaFailsWhenNoDocuments(): void
    {
        $this->seedRecords(Entrega::class, [
            (object) [
                'id' => 2,
                'cliente_id' => 1,
                'projeto_id' => 1,
                'status' => 'aprovado',
                'documentos_json' => '{}',
                'mes_referencia' => 2,
                'ano_referencia' => 2026,
            ],
        ]);

        $service = new ConsolidacaoService();
        $result = $service->consolidarEntrega(2);

        $this->assertFalse($result['success']);
        $this->assertSame('Nenhum documento encontrado para consolidar.', $result['erros'][0]);
    }

    public function testContarPaginasPdfUsesCountFromRawContentFallback(): void
    {
        $tmp = APP_ROOT . '/tmp/fake-pages.pdf';
        file_put_contents($tmp, '%PDF-1.4 /Count 7 >>');

        $service = new ConsolidacaoService();
        $method = (new ReflectionClass($service))->getMethod('contarPaginasPdf');
        $method->setAccessible(true);

        $count = $method->invoke($service, $tmp);

        unlink($tmp);
        $this->assertSame(7, $count);
    }

    public function testUtf8PrivateHelperConvertsString(): void
    {
        $service = new ConsolidacaoService();
        $converted = $this->callPrivate($service, 'utf8', ['Consolidacao']);

        $this->assertIsString($converted);
        $this->assertNotSame('', $converted);
    }

    public function testCalcularPaginacaoHandlesPdfImageUnsupportedAndMissingFiles(): void
    {
        $pdf = APP_ROOT . '/tmp/paginas.pdf';
        $img = APP_ROOT . '/tmp/paginas.png';
        $txt = APP_ROOT . '/tmp/paginas.txt';
        $missing = APP_ROOT . '/tmp/inexistente.pdf';

        file_put_contents($pdf, '%PDF-1.4 /Count 3 >>');
        file_put_contents($txt, 'conteudo');
        file_put_contents($img, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO7x2pQAAAAASUVORK5CYII='));

        $service = new ConsolidacaoService();
        $this->setPrivate($service, 'documentos', [
            'PDF A' => $pdf,
            'IMG B' => $img,
            'TXT C' => $txt,
            'MISSING D' => $missing,
        ]);

        $this->callPrivate($service, 'calcularPaginacao', [false]);
        $paginacao = $this->getPrivate($service, 'paginacao');
        $erros = $this->getPrivate($service, 'erros');

        $this->assertSame(3, $paginacao['PDF A']['paginas']);
        $this->assertSame(1, $paginacao['IMG B']['paginas']);
        $this->assertSame(1, $paginacao['TXT C']['paginas']);
        $this->assertNotEmpty($erros);

        @unlink($pdf);
        @unlink($img);
        @unlink($txt);
    }

    public function testAdicionarCapaAndSumarioRenderIntoPdfObject(): void
    {
        $service = new ConsolidacaoService();
        $this->setPrivate($service, 'cliente', (object) ['nome' => 'Cliente Teste']);
        $this->setPrivate($service, 'projeto', (object) ['nome' => 'Projeto Teste']);
        $this->setPrivate($service, 'entrega', (object) [
            'mes_referencia' => 2,
            'ano_referencia' => 2026,
            'data_aprovacao' => '2026-02-25 12:00:00',
        ]);
        $this->setPrivate($service, 'documentos', ['Doc 1' => 'a.pdf', 'Doc 2' => 'b.pdf']);
        $this->setPrivate($service, 'paginacao', [
            'Doc 1' => ['pagina_inicial' => 3],
            'Doc 2' => ['pagina_inicial' => 5],
        ]);

        $pdf = new FakePdfDocument();
        $this->callPrivate($service, 'adicionarCapa', [$pdf]);
        $this->callPrivate($service, 'adicionarSumario', [$pdf]);

        $this->assertGreaterThanOrEqual(2, $pdf->pages);
        $this->assertNotEmpty($pdf->cells);
    }

    public function testProcessarDocumentosAddsPagesForDifferentTypes(): void
    {
        $pdfFile = APP_ROOT . '/tmp/processo.pdf';
        $imgFile = APP_ROOT . '/tmp/processo.png';
        $txtFile = APP_ROOT . '/tmp/processo.txt';
        $missing = APP_ROOT . '/tmp/processo-missing.pdf';

        file_put_contents($pdfFile, '%PDF-1.4 /Count 2 >>');
        file_put_contents($txtFile, 'abc');
        file_put_contents($imgFile, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO7x2pQAAAAASUVORK5CYII='));

        $service = new ConsolidacaoService();
        $this->setPrivate($service, 'documentos', [
            'PDF' => $pdfFile,
            'IMG' => $imgFile,
            'TXT' => $txtFile,
            'MISS' => $missing,
        ]);

        $pdf = new FakePdfDocument();
        $this->callPrivate($service, 'processarDocumentos', [$pdf, false]);

        $this->assertGreaterThanOrEqual(5, $pdf->pages);

        @unlink($pdfFile);
        @unlink($imgFile);
        @unlink($txtFile);
    }

    public function testImportarPaginasPdfUsesTemplateFlow(): void
    {
        $service = new ConsolidacaoService();
        $pdf = new FakePdfDocument();

        $this->callPrivate($service, 'importarPaginasPdf', [$pdf, APP_ROOT . '/tmp/qualquer.pdf', 'Doc']);

        $this->assertSame(2, $pdf->templateUseCount);
        $this->assertGreaterThanOrEqual(2, $pdf->pages);
    }

    public function testPreprocessarPdfComGhostscriptReturnsNullWhenUnavailableOrFails(): void
    {
        $service = new ConsolidacaoService();
        $result = $this->callPrivate($service, 'preprocessarPdfComGhostscript', [APP_ROOT . '/tmp/inexistente.pdf']);
        $this->assertNull($result);
    }

    public function testNotificarClienteCreatesNotificationRecords(): void
    {
        \TSession::setValue('userid', 500);

        $service = new ConsolidacaoService();
        $this->setPrivate($service, 'entrega', (object) [
            'id' => 999,
            'cliente_id' => 77,
            'mes_referencia' => 2,
            'ano_referencia' => 2026,
        ]);

        $this->callPrivate($service, 'notificarCliente');

        $notifs = \Notification::where('system_user_id', '=', 77)->load();
        $systemNotifs = \SystemNotification::where('system_user_id', '=', 77)->load();

        $this->assertNotEmpty($notifs);
        $this->assertNotEmpty($systemNotifs);
    }
}

class FakePdfDocument
{
    public $pages = 0;
    public $y = 10;
    public $cells = [];
    public $templateUseCount = 0;

    public function SetAutoPageBreak($enabled, $margin = 0) {}
    public function AddPage() { $this->pages++; $this->y = 10; }
    public function SetDrawColor($r, $g = null, $b = null) {}
    public function SetLineWidth($w) {}
    public function Rect($x, $y, $w, $h, $style = '') {}
    public function SetFont($family, $style = '', $size = 0) {}
    public function SetTextColor($r, $g = null, $b = null) {}
    public function Ln($h = null) { $this->y += $h ?? 5; }
    public function Cell($w, $h = 0, $txt = '', $border = 0, $ln = 0, $align = '') { $this->cells[] = $txt; if ($ln) { $this->y += $h ?: 5; } }
    public function Line($x1, $y1, $x2, $y2) {}
    public function SetY($y) { $this->y = $y; }
    public function GetY() { return $this->y; }
    public function MultiCell($w, $h, $txt, $border = 0, $align = '') { $this->cells[] = $txt; $this->y += $h ?: 5; }
    public function GetStringWidth($txt) { return strlen((string) $txt) * 2; }
    public function SetFillColor($r, $g = null, $b = null) {}
    public function Image($file, $x = null, $y = null, $w = 0, $h = 0) {}
    public function setSourceFile($file) { return 2; }
    public function importPage($pageNo) { return $pageNo; }
    public function getTemplateSize($templateId) { return ['width' => 100, 'height' => 120]; }
    public function useTemplate($templateId, $x = null, $y = null, $w = null, $h = null) { $this->templateUseCount++; }
}
