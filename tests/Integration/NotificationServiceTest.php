<?php

namespace Tests\Integration;

use Notification;
use NotificationService;
use Usuario;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    public function testCreatePersistsNotification(): void
    {
        $ok = NotificationService::create(5, 'Titulo', 'Corpo', 'warning', 'entrega', 99, 'class=EntregaList', 'Ver', 'fa fa-bell');

        $this->assertTrue($ok);

        $all = Notification::where('system_user_id', '=', 5)->load();
        $this->assertCount(1, $all);
        $this->assertSame('warning', $all[0]->type);
        $this->assertSame(99, $all[0]->reference_id);
    }

    public function testNotifyManagersSendsToAdminAndGestorOnly(): void
    {
        $this->seedRecords(Usuario::class, [
            (object) ['id' => 1, 'tipo' => 'admin'],
            (object) ['id' => 2, 'tipo' => 'gestor'],
            (object) ['id' => 3, 'tipo' => 'cliente'],
        ]);

        NotificationService::notifyManagers('Alerta', 'Mensagem');

        $forAdmin = Notification::where('system_user_id', '=', 1)->load();
        $forGestor = Notification::where('system_user_id', '=', 2)->load();
        $forCliente = Notification::where('system_user_id', '=', 3)->load();

        $this->assertCount(1, $forAdmin);
        $this->assertCount(1, $forGestor);
        $this->assertCount(0, $forCliente);
    }

    public function testUnreadCountAndMarkAllAsRead(): void
    {
        NotificationService::create(7, 'N1', 'M1');
        NotificationService::create(7, 'N2', 'M2');

        $countBefore = NotificationService::getUnreadCount(7);
        $this->assertSame(2, $countBefore);

        NotificationService::markAllAsRead(7);

        $countAfter = NotificationService::getUnreadCount(7);
        $this->assertSame(0, $countAfter);
    }

    public function testNotifyClientAndSendWrapper(): void
    {
        $this->assertTrue(NotificationService::notifyClient(8, 'T1', 'M1'));
        $this->assertTrue(NotificationService::send(1, 8, 'T2', 'M2'));

        $notifs = Notification::where('system_user_id', '=', 8)->load();
        $this->assertCount(2, $notifs);
    }
}
