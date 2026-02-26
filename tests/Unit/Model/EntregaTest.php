<?php

namespace Tests\Unit\Model;

use Entrega;
use Tests\TestCase;

class EntregaTest extends TestCase
{
    public function testSetAndGetDocumentosRoundTrip(): void
    {
        $entrega = new Entrega();
        $docs = [
            'Contrato Social' => 'files/contrato.pdf',
            'RG' => 'files/rg.png',
        ];

        $entrega->set_documentos($docs);

        $this->assertSame($docs, $entrega->get_documentos());
        $this->assertStringContainsString('Contrato Social', $entrega->documentos_json);
    }

    public function testGetDocumentosReturnsEmptyArrayForInvalidJson(): void
    {
        $entrega = new Entrega();
        $entrega->documentos_json = '{invalid';

        $this->assertSame([], $entrega->get_documentos());
    }

    public function testIsConsolidadoAndPodeConsolidarRules(): void
    {
        $entrega = new Entrega();
        $entrega->status = 'aprovado';
        $entrega->consolidado = 0;

        $this->assertFalse($entrega->isConsolidado());
        $this->assertTrue($entrega->podeConsolidar());

        $entrega->consolidado = 1;
        $this->assertTrue($entrega->isConsolidado());
        $this->assertFalse($entrega->podeConsolidar());
    }

    public function testGetArquivoConsolidadoReturnsPathOnlyIfExists(): void
    {
        $path = APP_ROOT . '/tmp/test-consolidado.pdf';
        file_put_contents($path, 'pdf');

        $entrega = new Entrega();
        $entrega->consolidado = 1;
        $entrega->arquivo_consolidado = $path;

        $this->assertSame($path, $entrega->getArquivoConsolidado());

        unlink($path);
        $this->assertNull($entrega->getArquivoConsolidado());
    }
}
