<?php

namespace Tests\Unit\Service;

use DocumentSummarizerService;
use Entrega;
use Tests\TestCase;

class DocumentSummarizerServiceTest extends TestCase
{
    public function testResumirEntregaFailsWhenNoDocuments(): void
    {
        $this->seedRecords(Entrega::class, [
            (object) [
                'id' => 10,
                'status' => 'aprovado',
                'documentos_json' => '{}',
            ],
        ]);

        $service = new DocumentSummarizerService();
        $result = $service->resumirEntrega(10);

        $this->assertFalse($result['success']);
        $this->assertSame('Nenhum documento encontrado nesta entrega.', $result['message']);
    }

    public function testResumirEntregaFailsWhenFilesCannotBeRead(): void
    {
        $this->seedRecords(Entrega::class, [
            (object) [
                'id' => 11,
                'status' => 'aprovado',
                'documentos_json' => json_encode(['Doc A' => APP_ROOT . '/tmp/nao-existe.pdf']),
            ],
        ]);

        $service = new DocumentSummarizerService();
        $result = $service->resumirEntrega(11);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('extrair texto dos documentos', $result['message']);
    }
}
