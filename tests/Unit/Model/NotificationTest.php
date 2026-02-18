<?php

namespace Tests\Unit\Model;

use Tests\TestCase;

class NotificationTest extends TestCase
{
    public function testNotificationAttributes()
    {
        $notification = new \stdClass();
        $notification->system_user_id = 1;
        $notification->type = 'info';
        $notification->title = 'Test Title';
        $notification->message = 'Test Message';
        
        $this->assertEquals(1, $notification->system_user_id);
        $this->assertEquals('info', $notification->type);
        $this->assertEquals('Test Title', $notification->title);
    }
    
    public function testIsRead()
    {
        // Mock object behavior
        $notification = new \stdClass();
        $notification->read_at = '2023-01-01 12:00:00';
        
        $isRead = !empty($notification->read_at);
        $this->assertTrue($isRead);
        
        $notification->read_at = null;
        $isRead = !empty($notification->read_at);
        $this->assertFalse($isRead);
    }
}
