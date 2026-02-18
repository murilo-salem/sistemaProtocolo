<?php

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * Classe base para todos os testes
 * 
 * Fornece helpers comuns e configuração de ambiente.
 */
abstract class TestCase extends BaseTestCase
{
    /**
     * Configuração executada antes de cada teste
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Limpar sessão entre testes
        if (class_exists('TSession')) {
            \TSession::clear();
        }
    }
    
    /**
     * Limpeza executada após cada teste
     */
    protected function tearDown(): void
    {
        parent::tearDown();
    }
    
    /**
     * Cria um mock de Usuario para testes
     */
    protected function createMockUsuario(array $attributes = []): object
    {
        $defaults = [
            'id' => 1,
            'nome' => 'Usuário Teste',
            'email' => 'teste@example.com',
            'login' => 'teste',
            'senha' => password_hash('senha123', PASSWORD_DEFAULT),
            'tipo' => 'cliente',
            'ativo' => 1,
        ];
        
        return (object) array_merge($defaults, $attributes);
    }
    
    /**
     * Cria um mock de Entrega para testes
     */
    protected function createMockEntrega(array $attributes = []): object
    {
        $defaults = [
            'id' => 1,
            'cliente_id' => 1,
            'projeto_id' => 1,
            'mes_referencia' => date('n'),
            'ano_referencia' => date('Y'),
            'documentos_json' => '{}',
            'status' => 'pendente',
            'consolidado' => 0,
            'arquivo_consolidado' => null,
        ];
        
        return (object) array_merge($defaults, $attributes);
    }
    
    /**
     * Cria um mock de Projeto para testes
     */
    protected function createMockProjeto(array $attributes = []): object
    {
        $defaults = [
            'id' => 1,
            'nome' => 'Projeto Teste',
            'descricao' => 'Descrição do projeto',
            'dia_vencimento' => 15,
            'ativo' => 1,
        ];
        
        return (object) array_merge($defaults, $attributes);
    }
    
    /**
     * Assert que um array contém todas as chaves esperadas
     */
    protected function assertArrayHasKeys(array $keys, array $array, string $message = ''): void
    {
        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $array, $message ?: "Array should have key: {$key}");
        }
    }
}
