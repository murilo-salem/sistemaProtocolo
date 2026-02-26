<?php

namespace Tests\Unit\Service;

use DatabaseSetupService;
use TestService;
use Tests\TestCase;

class InfraServicesTest extends TestCase
{
    public function testDatabaseSetupServiceExecutesQueriesAndPrintsSuccess(): void
    {
        $conn = new class {
            public $queries = [];
            public function query($sql) { $this->queries[] = $sql; }
        };

        \TTransaction::setConnection($conn);

        $service = new DatabaseSetupService();
        ob_start();
        $service->setup();
        $output = ob_get_clean();

        $this->assertNotEmpty($conn->queries);
        $this->assertStringContainsString('chat_messages criada com sucesso', $output);
    }

    public function testDatabaseSetupServiceHandlesConnectionError(): void
    {
        $conn = new class {
            public function query($sql) { throw new \Exception('db falhou'); }
        };

        \TTransaction::setConnection($conn);

        $service = new DatabaseSetupService();
        ob_start();
        $service->setup();
        $output = ob_get_clean();

        $this->assertStringContainsString('Erro:', $output);
    }

    public function testTestServicePrintsGivenParam(): void
    {
        $service = new TestService();

        ob_start();
        $service->test(['a' => 1]);
        $out = ob_get_clean();

        $this->assertStringContainsString('array', strtolower($out));
        $this->assertStringContainsString('a', $out);
    }
}
