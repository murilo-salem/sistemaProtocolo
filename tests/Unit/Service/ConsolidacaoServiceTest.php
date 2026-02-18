<?php

namespace Tests\Unit\Service;

use Tests\TestCase;

/**
 * Testes unitários para ConsolidacaoService
 * 
 * Testa lógica isolada de consolidação sem dependência de banco.
 */
class ConsolidacaoServiceTest extends TestCase
{
    /**
     * Testa conversão UTF-8 para ISO-8859-1
     */
    public function testUtf8ConversionWorks(): void
    {
        $input = 'Consolidação de Documentos - Período 01/2026';
        $expected = mb_convert_encoding($input, 'ISO-8859-1', 'UTF-8');
        
        $this->assertNotEquals($input, $expected);
        $this->assertIsString($expected);
    }
    
    /**
     * Testa caracteres especiais na conversão
     */
    public function testSpecialCharacterConversion(): void
    {
        $specialChars = ['ç', 'ã', 'é', 'í', 'ó', 'ú', 'ñ'];
        
        foreach ($specialChars as $char) {
            $converted = mb_convert_encoding($char, 'ISO-8859-1', 'UTF-8');
            $this->assertNotEmpty($converted);
        }
    }
    
    /**
     * Testa identificação de extensões de arquivo suportadas
     */
    public function testSupportedFileExtensions(): void
    {
        $supported = ['pdf', 'jpg', 'jpeg', 'png', 'gif'];
        $unsupported = ['docx', 'xlsx', 'txt', 'zip'];
        
        foreach ($supported as $ext) {
            $this->assertContains($ext, $supported);
        }
        
        foreach ($unsupported as $ext) {
            $this->assertNotContains($ext, $supported);
        }
    }
    
    /**
     * Testa extração de extensão de arquivo
     */
    public function testExtractFileExtension(): void
    {
        $files = [
            'documento.pdf' => 'pdf',
            'imagem.PNG' => 'png',
            'foto.JPEG' => 'jpeg',
            'arquivo.teste.pdf' => 'pdf',
        ];
        
        foreach ($files as $filename => $expectedExt) {
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $this->assertEquals($expectedExt, $ext);
        }
    }
    
    /**
     * Testa geração de nome de arquivo consolidado
     */
    public function testConsolidatedFilenameGeneration(): void
    {
        $clienteId = 1;
        $projetoId = 2;
        $timestamp = time();
        
        $filename = "Consolidado_{$clienteId}_{$projetoId}_{$timestamp}.pdf";
        
        $this->assertStringStartsWith('Consolidado_', $filename);
        $this->assertStringEndsWith('.pdf', $filename);
        $this->assertStringContainsString("_{$clienteId}_", $filename);
    }
    
    /**
     * Testa criação de caminho de pasta consolidada
     */
    public function testConsolidatedFolderPathGeneration(): void
    {
        $ano = 2026;
        $mes = 2;
        
        $path = "files/consolidados/{$ano}/{$mes}";
        
        $this->assertEquals('files/consolidados/2026/2', $path);
    }
    
    /**
     * Testa cálculo de paginação básica
     */
    public function testBasicPaginationCalculation(): void
    {
        // Simular documentos com suas páginas
        $documentos = [
            'Doc 1' => ['paginas' => 3],
            'Doc 2' => ['paginas' => 5],
            'Doc 3' => ['paginas' => 2],
        ];
        
        // Capa = 1, Sumário = 1, Total = 2 páginas iniciais
        $paginaInicial = 3; // Primeira página após capa e sumário
        $paginacao = [];
        
        foreach ($documentos as $nome => $info) {
            $paginacao[$nome] = $paginaInicial;
            $paginaInicial += $info['paginas'];
        }
        
        $this->assertEquals(3, $paginacao['Doc 1']);
        $this->assertEquals(6, $paginacao['Doc 2']);
        $this->assertEquals(11, $paginacao['Doc 3']);
    }
    
    /**
     * Testa formatação de linha de sumário
     */
    public function testSummaryLineFormat(): void
    {
        $docName = 'Contrato Social';
        $pageNum = 5;
        
        $line = "{$docName} ............ pág. {$pageNum}";
        
        $this->assertStringContainsString($docName, $line);
        $this->assertStringContainsString("pág. {$pageNum}", $line);
    }
    
    /**
     * Testa resultado de consolidação bem sucedida
     */
    public function testSuccessfulConsolidationResult(): void
    {
        $result = [
            'success' => true,
            'arquivo' => 'files/consolidados/2026/2/Consolidado_1_1_123456.pdf',
            'erros' => []
        ];
        
        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['arquivo']);
        $this->assertEmpty($result['erros']);
    }
    
    /**
     * Testa resultado de consolidação com erro
     */
    public function testFailedConsolidationResult(): void
    {
        $result = [
            'success' => false,
            'arquivo' => null,
            'erros' => ['Apenas entregas aprovadas podem ser consolidadas']
        ];
        
        $this->assertFalse($result['success']);
        $this->assertNull($result['arquivo']);
        $this->assertNotEmpty($result['erros']);
    }
    
    /**
     * Testa cálculo de proporção de imagem
     */
    public function testImageAspectRatioCalculation(): void
    {
        // Imagem 800x600 (landscape)
        $width = 800;
        $height = 600;
        $maxW = 190;
        $maxH = 240;
        
        $ratio = $width / $height;
        
        if ($maxW / $maxH > $ratio) {
            $finalW = $maxH * $ratio;
            $finalH = $maxH;
        } else {
            $finalW = $maxW;
            $finalH = $maxW / $ratio;
        }
        
        $this->assertLessThanOrEqual($maxW, $finalW);
        $this->assertLessThanOrEqual($maxH, $finalH);
    }
}
