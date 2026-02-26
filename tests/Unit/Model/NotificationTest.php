<?php

namespace Tests\Unit\Model;

use Notification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    public function testMarkAsReadSetsTimestampAndPersists(): void
    {
        $this->seedRecords(Notification::class, [
            (object) [
                'id' => 5,
                'system_user_id' => 1,
                'title' => 'Nova mensagem',
                'message' => 'Corpo',
                'read_at' => null,
            ],
        ]);

        $notification = new Notification(5);
        $notification->markAsRead();

        $updated = Notification::find(5);
        $this->assertNotNull($updated->read_at);
        $this->assertTrue($notification->isRead());
    }

    public function testIsReadReturnsFalseWhenReadAtIsEmpty(): void
    {
        $notification = new Notification();
        $notification->read_at = null;

        $this->assertFalse($notification->isRead());
    }
}
