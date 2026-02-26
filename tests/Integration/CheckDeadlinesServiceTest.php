<?php

namespace Tests\Integration;

use CheckDeadlinesService;
use ClienteProjeto;
use Projeto;
use SystemNotification;
use Usuario;
use Tests\TestCase;

class CheckDeadlinesServiceTest extends TestCase
{
    public function testProcessCreatesNotificationsForClientsAndManagers(): void
    {
        $targetDay = (int) date('d', strtotime('+3 days'));

        $this->seedRecords(Projeto::class, [
            (object) ['id' => 10, 'nome' => 'Projeto Fiscal', 'dia_vencimento' => $targetDay, 'ativo' => '1'],
        ]);
        $this->seedRecords(ClienteProjeto::class, [
            (object) ['id' => 1, 'cliente_id' => 100, 'projeto_id' => 10],
        ]);
        $this->seedRecords(Usuario::class, [
            (object) ['id' => 100, 'nome' => 'Cliente 100', 'tipo' => 'cliente', 'ativo' => 1],
            (object) ['id' => 200, 'nome' => 'Admin', 'tipo' => 'admin', 'ativo' => 1],
            (object) ['id' => 201, 'nome' => 'Gestor', 'tipo' => 'gestor', 'ativo' => 1],
        ]);

        $service = new CheckDeadlinesService();

        ob_start();
        $service->process();
        $output = ob_get_clean();

        $clientNotifs = SystemNotification::where('system_user_id', '=', 100)->load();
        $adminNotifs = SystemNotification::where('system_user_id', '=', 200)->load();
        $gestorNotifs = SystemNotification::where('system_user_id', '=', 201)->load();

        $this->assertStringContainsString('Deadline check finished.', $output);
        $this->assertCount(1, $clientNotifs);
        $this->assertCount(1, $adminNotifs);
        $this->assertCount(1, $gestorNotifs);
    }

    public function testProcessSkipsWhenNoMatchingProject(): void
    {
        $this->seedRecords(Projeto::class, [
            (object) ['id' => 11, 'nome' => 'Projeto Sem Prazo', 'dia_vencimento' => 1, 'ativo' => '1'],
        ]);

        $service = new CheckDeadlinesService();

        ob_start();
        $service->process();
        ob_end_clean();

        $all = SystemNotification::getObjects();
        $this->assertCount(0, $all);
    }
}
