<?php

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * Classe base para todos os testes.
 */
abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (class_exists('TSession')) {
            \TSession::clear();
        }

        if (class_exists('MockDatabase')) {
            \MockDatabase::reset();
        }
        if (class_exists('TestSpy')) {
            \TestSpy::reset();
        }
    }

    protected function createMockUsuario(array $attributes = []): object
    {
        $defaults = [
            'id' => 1,
            'nome' => 'Usuario Teste',
            'email' => 'teste@example.com',
            'login' => 'teste',
            'senha' => password_hash('senha123', PASSWORD_DEFAULT),
            'tipo' => 'cliente',
            'ativo' => 1,
        ];

        return (object) array_merge($defaults, $attributes);
    }

    protected function createMockEntrega(array $attributes = []): object
    {
        $defaults = [
            'id' => 1,
            'cliente_id' => 1,
            'projeto_id' => 1,
            'mes_referencia' => (int) date('n'),
            'ano_referencia' => (int) date('Y'),
            'documentos_json' => '{}',
            'status' => 'pendente',
            'consolidado' => 0,
            'arquivo_consolidado' => null,
        ];

        return (object) array_merge($defaults, $attributes);
    }

    protected function createMockProjeto(array $attributes = []): object
    {
        $defaults = [
            'id' => 1,
            'nome' => 'Projeto Teste',
            'descricao' => 'Descricao do projeto',
            'dia_vencimento' => 15,
            'ativo' => 1,
        ];

        return (object) array_merge($defaults, $attributes);
    }

    protected function assertArrayHasKeys(array $keys, array $array, string $message = ''): void
    {
        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $array, $message ?: "Array should have key: {$key}");
        }
    }

    protected function seedRecords(string $class, array $records): void
    {
        \MockDatabase::seed($class, $records);
    }

    protected function getDeleteCalls(): array
    {
        return \MockDatabase::deleteCalls();
    }
}
