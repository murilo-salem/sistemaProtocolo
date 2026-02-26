<?php

namespace Tests\Functional;

use Notification;
use Tests\TestCase;

require_once APP_ROOT . '/app/control/notificacoes/NotificationList.php';
require_once APP_ROOT . '/app/control/notificacoes/NotificationDropdown.php';

class NotificationControllerTest extends TestCase
{
    public function testNotificationListOnViewMarksAsReadAndLoadsClassAction(): void
    {
        $this->seedRecords(Notification::class, [
            (object) [
                'id' => 1,
                'system_user_id' => 1,
                'title' => 'T',
                'message' => 'M',
                'created_at' => '2026-02-25 12:00:00',
                'read_at' => null,
                'action_url' => 'class=EntregaList&id=10',
            ],
        ]);

        $page = new \NotificationList();
        $page->onView(['id' => 1]);

        $updated = Notification::find(1);
        $this->assertNotNull($updated->read_at);
        $this->assertCount(1, \TestSpy::$appLoads);
        $this->assertSame('EntregaList', \TestSpy::$appLoads[0]['class']);
    }

    public function testNotificationListOnViewWithoutActionReloads(): void
    {
        \TSession::setValue('userid', 2);
        $this->seedRecords(Notification::class, [
            (object) [
                'id' => 2,
                'system_user_id' => 2,
                'title' => 'X',
                'message' => 'Y',
                'created_at' => '2026-02-25 12:00:00',
                'read_at' => null,
                'action_url' => null,
            ],
        ]);

        $page = new \NotificationList();
        $page->onView(['id' => 2]);

        $updated = Notification::find(2);
        $this->assertNotNull($updated->read_at);
    }

    public function testNotificationDropdownShowDoesNotFail(): void
    {
        $dropdown = new \NotificationDropdown();
        $dropdown->show();

        $this->assertTrue(true);
    }
}
