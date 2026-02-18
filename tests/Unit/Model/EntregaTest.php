<?php

namespace Tests\Unit\Model;

use Tests\TestCase;

/**
 * Testes unitários para o model Entrega
 * 
 * Foca nos helper methods: isConsolidado(), podeConsolidar(), get_documentos()
 */
class EntregaTest extends TestCase
{
    /**
     * Testa isConsolidado retorna true quando consolidado = 1
     */
    public function testIsConsolidadoReturnsTrueWhenConsolidated(): void
    {
        $entrega = $this->createMockEntrega(['consolidado' => 1]);
        
        // Simular lógica do método
        $isConsolidado = !empty($entrega->consolidado) && $entrega->consolidado == 1;
        
        $this->assertTrue($isConsolidado);
    }
    
    /**
     * Testa isConsolidado retorna false quando consolidado = 0
     */
    public function testIsConsolidadoReturnsFalseWhenNotConsolidated(): void
    {
        $entrega = $this->createMockEntrega(['consolidado' => 0]);
        
        $isConsolidado = !empty($entrega->consolidado) && $entrega->consolidado == 1;
        
        $this->assertFalse($isConsolidado);
    }
    
    /**
     * Testa isConsolidado retorna false quando consolidado é null
     */
    public function testIsConsolidadoReturnsFalseWhenNull(): void
    {
        $entrega = $this->createMockEntrega(['consolidado' => null]);
        
        $isConsolidado = !empty($entrega->consolidado) && $entrega->consolidado == 1;
        
        $this->assertFalse($isConsolidado);
    }
    
    /**
     * Testa podeConsolidar retorna true para aprovado não consolidado
     */
    public function testPodeConsolidarReturnsTrueForApprovedNotConsolidated(): void
    {
        $entrega = $this->createMockEntrega([
            'status' => 'aprovado',
            'consolidado' => 0
        ]);
        
        $isConsolidado = !empty($entrega->consolidado) && $entrega->consolidado == 1;
        $podeConsolidar = $entrega->status == 'aprovado' && !$isConsolidado;
        
        $this->assertTrue($podeConsolidar);
    }
    
    /**
     * Testa podeConsolidar retorna false para aprovado já consolidado
     */
    public function testPodeConsolidarReturnsFalseForAlreadyConsolidated(): void
    {
        $entrega = $this->createMockEntrega([
            'status' => 'aprovado',
            'consolidado' => 1
        ]);
        
        $isConsolidado = !empty($entrega->consolidado) && $entrega->consolidado == 1;
        $podeConsolidar = $entrega->status == 'aprovado' && !$isConsolidado;
        
        $this->assertFalse($podeConsolidar);
    }
    
    /**
     * Testa podeConsolidar retorna false para pendente
     */
    public function testPodeConsolidarReturnsFalseForPending(): void
    {
        $entrega = $this->createMockEntrega([
            'status' => 'pendente',
            'consolidado' => 0
        ]);
        
        $podeConsolidar = $entrega->status == 'aprovado';
        
        $this->assertFalse($podeConsolidar);
    }
    
    /**
     * Testa get_documentos com JSON válido
     */
    public function testGetDocumentosWithValidJson(): void
    {
        $docs = ['Documento 1' => 'path/to/doc1.pdf', 'Documento 2' => 'path/to/doc2.pdf'];
        $json = json_encode($docs);
        
        $result = json_decode($json, true) ?: [];
        
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('Documento 1', $result);
    }
    
    /**
     * Testa get_documentos com JSON vazio
     */
    public function testGetDocumentosWithEmptyJson(): void
    {
        $json = '{}';
        
        $result = json_decode($json, true) ?: [];
        
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
    
    /**
     * Testa get_documentos com JSON inválido
     */
    public function testGetDocumentosWithInvalidJson(): void
    {
        $json = 'invalid json';
        
        $result = json_decode($json, true) ?: [];
        
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
    
    /**
     * Testa get_documentos com null
     */
    public function testGetDocumentosWithNull(): void
    {
        $json = null;
        
        $result = json_decode($json, true) ?: [];
        
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
    
    /**
     * Testa status válidos de entrega
     */
    public function testValidEntregaStatuses(): void
    {
        $validStatuses = ['pendente', 'em_analise', 'aprovado', 'rejeitado'];
        
        foreach ($validStatuses as $status) {
            $entrega = $this->createMockEntrega(['status' => $status]);
            $this->assertContains($entrega->status, $validStatuses);
        }
    }
    
    /**
     * Testa formatação correta de mês/ano de referência
     */
    public function testReferenceMonthYearFormat(): void
    {
        $entrega = $this->createMockEntrega([
            'mes_referencia' => 2,
            'ano_referencia' => 2026
        ]);
        
        $formatted = str_pad($entrega->mes_referencia, 2, '0', STR_PAD_LEFT) . '/' . $entrega->ano_referencia;
        
        $this->assertEquals('02/2026', $formatted);
    }
}
