<?php

namespace Tests\Functional;

use Entrega;
use Notification;
use Projeto;
use Tests\TestCase;
use Usuario;

require_once APP_ROOT . '/app/control/admin/LoginForm.php';
require_once APP_ROOT . '/app/control/entregas/EntregaList.php';
require_once APP_ROOT . '/app/control/notificacoes/NotificationList.php';
require_once APP_ROOT . '/app/control/entregas/ConsolidarEntregaV2.php';

class ControllerSmokeTest extends TestCase
{
    public function testLoginFormConstructorBuildsFormWithoutErrors(): void
    {
        $form = new \LoginForm();
        $this->assertInstanceOf(\LoginForm::class, $form);
    }

    public function testEntregaListOnReloadAppliesClienteFilter(): void
    {
        \TSession::setValue('userid', 10);
        \TSession::setValue('usertype', 'cliente');

        $this->seedRecords(Usuario::class, [
            (object) ['id' => 10, 'nome' => 'Cliente 10'],
            (object) ['id' => 11, 'nome' => 'Cliente 11'],
        ]);
        $this->seedRecords(Projeto::class, [
            (object) ['id' => 1, 'nome' => 'Projeto A'],
        ]);
        $this->seedRecords(Entrega::class, [
            (object) ['id' => 1, 'cliente_id' => 10, 'projeto_id' => 1, 'status' => 'pendente', 'data_entrega' => '2026-02-01'],
            (object) ['id' => 2, 'cliente_id' => 11, 'projeto_id' => 1, 'status' => 'pendente', 'data_entrega' => '2026-02-01'],
        ]);

        $page = new \EntregaList();
        $page->onReload([]);

        $ref = new \ReflectionClass($page);
        $dgProp = $ref->getProperty('datagrid');
        $dgProp->setAccessible(true);
        $datagrid = $dgProp->getValue($page);

        $this->assertCount(1, $datagrid->items);
        $this->assertSame(10, $datagrid->items[0]->cliente_id);
    }

    public function testNotificationListMarkAllAsReadUpdatesUnreadNotifications(): void
    {
        \TSession::setValue('userid', 55);

        $this->seedRecords(Notification::class, [
            (object) ['id' => 1, 'system_user_id' => 55, 'title' => 'A', 'message' => 'a', 'read_at' => null, 'created_at' => '2026-02-25 10:00:00'],
            (object) ['id' => 2, 'system_user_id' => 55, 'title' => 'B', 'message' => 'b', 'read_at' => null, 'created_at' => '2026-02-25 10:01:00'],
        ]);

        $page = new \NotificationList();
        $page->onMarkAllAsRead();

        $unreadCount = \NotificationService::getUnreadCount(55);
        $this->assertSame(0, $unreadCount);
    }

    public function testConsolidarEntregaV2OnConsolidarRedirectsToDownloadWhenAlreadyConsolidated(): void
    {
        $file = APP_ROOT . '/tmp/consolidado-existente.pdf';
        file_put_contents($file, 'pdf');

        $this->seedRecords(Entrega::class, [
            (object) [
                'id' => 88,
                'cliente_id' => 1,
                'projeto_id' => 1,
                'consolidado' => 1,
                'arquivo_consolidado' => $file,
                'status' => 'aprovado',
            ],
        ]);

        \ConsolidarEntregaV2::onConsolidar(['id' => 88]);

        $this->assertNotEmpty(\TestSpy::$scripts);
        $this->assertStringContainsString('onDownload&id=88', \TestSpy::$scripts[0]);

        unlink($file);
    }

    public function testConsolidarEntregaV2OnDownloadShowsErrorWhenFileMissing(): void
    {
        $this->seedRecords(Entrega::class, [
            (object) [
                'id' => 89,
                'arquivo_consolidado' => APP_ROOT . '/tmp/arquivo-ausente.pdf',
                'mes_referencia' => 2,
                'ano_referencia' => 2026,
            ],
        ]);

        \ConsolidarEntregaV2::onDownload(['id' => 89]);

        $this->assertNotEmpty(\TestSpy::$messages);
        $this->assertSame('error', \TestSpy::$messages[0]['type']);
    }
}
