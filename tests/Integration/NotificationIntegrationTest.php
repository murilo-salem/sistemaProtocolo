<?php

namespace Tests\Integration;

use NotificationService;
use SystemNotification;
use Tests\TestCase;

class NotificationIntegrationTest extends TestCase
{
    public function testCreateAndRegisterLegacyNotificationTogether(): void
    {
        $ok = NotificationService::create(3, 'Novo documento', 'Seu documento foi validado', 'success');
        $legacy = SystemNotification::register(3, 'Novo documento', 'Seu documento foi validado');

        $this->assertTrue($ok);
        $this->assertNotNull($legacy->id);

        $legacyStored = SystemNotification::find($legacy->id);
        $this->assertSame('N', $legacyStored->checked);
        $this->assertSame(3, $legacyStored->system_user_id);
    }
}
