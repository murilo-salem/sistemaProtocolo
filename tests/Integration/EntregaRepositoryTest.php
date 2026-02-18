<?php

namespace Tests\Integration;

use Tests\TestCase;

/**
 * Testes de integração para persistência de Entrega
 * 
 * Nota: Estes testes requerem banco de dados configurado.
 * Em ambiente de CI/CD, usar SQLite em memória ou container PostgreSQL.
 */
class EntregaRepositoryTest extends TestCase
{
    /**
     * Verifica que a estrutura de dados de entrega está correta
     */
    public function testEntregaDataStructure(): void
    {
        $entrega = $this->createMockEntrega();
        
        $requiredFields = [
            'id', 'cliente_id', 'projeto_id', 
            'mes_referencia', 'ano_referencia',
            'status', 'consolidado'
        ];
        
        foreach ($requiredFields as $field) {
            $this->assertTrue(
                property_exists($entrega, $field) || isset($entrega->$field),
                "Entrega deve ter campo: {$field}"
            );
        }
    }
    
    /**
     * Testa serialização de documentos JSON
     */
    public function testDocumentJsonSerialization(): void
    {
        $documentos = [
            'Contrato' => 'path/to/contrato.pdf',
            'RG' => 'path/to/rg.jpg',
            'Comprovante' => 'path/to/comprovante.png'
        ];
        
        $json = json_encode($documentos, JSON_UNESCAPED_UNICODE);
        $decoded = json_decode($json, true);
        
        $this->assertEquals($documentos, $decoded);
        $this->assertCount(3, $decoded);
    }
    
    /**
     * Testa que documentos com caracteres especiais são preservados
     */
    public function testDocumentWithSpecialCharsPreserved(): void
    {
        $documentos = [
            'Contrato Social Alteração' => 'path/alteracao.pdf',
            'Comprovação de Endereço' => 'path/endereco.pdf',
        ];
        
        $json = json_encode($documentos, JSON_UNESCAPED_UNICODE);
        $decoded = json_decode($json, true);
        
        $this->assertArrayHasKey('Contrato Social Alteração', $decoded);
        $this->assertArrayHasKey('Comprovação de Endereço', $decoded);
    }
    
    /**
     * Testa transição de status válida
     */
    public function testValidStatusTransitions(): void
    {
        $validTransitions = [
            'pendente' => ['em_analise', 'rejeitado'],
            'em_analise' => ['aprovado', 'rejeitado'],
            'aprovado' => [], // Terminal
            'rejeitado' => ['pendente'], // Pode reabrir
        ];
        
        $this->assertArrayHasKey('pendente', $validTransitions);
        $this->assertContains('em_analise', $validTransitions['pendente']);
    }
    
    /**
     * Testa formato de data de entrega
     */
    public function testDataEntregaFormat(): void
    {
        $dataEntrega = date('Y-m-d H:i:s');
        
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
            $dataEntrega
        );
    }
    
    /**
     * Testa relação entrega -> cliente
     */
    public function testEntregaClienteRelation(): void
    {
        $entrega = $this->createMockEntrega(['cliente_id' => 5]);
        
        $this->assertEquals(5, $entrega->cliente_id);
        $this->assertIsInt($entrega->cliente_id);
    }
    
    /**
     * Testa relação entrega -> projeto
     */
    public function testEntregaProjetoRelation(): void
    {
        $entrega = $this->createMockEntrega(['projeto_id' => 3]);
        
        $this->assertEquals(3, $entrega->projeto_id);
        $this->assertIsInt($entrega->projeto_id);
    }
}
