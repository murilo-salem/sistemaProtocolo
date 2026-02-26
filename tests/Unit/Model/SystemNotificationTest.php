<?php

namespace Tests\Unit\Model;

use SystemNotification;
use Tests\TestCase;

class SystemNotificationTest extends TestCase
{
    public function testRegisterCreatesUnreadNotification(): void
    {
        $created = SystemNotification::register(
            9,
            'Titulo',
            'Mensagem',
            'class=EntregaList',
            'Abrir',
            'fa fa-bell'
        );

        $saved = SystemNotification::find($created->id);

        $this->assertSame(9, $saved->system_user_id);
        $this->assertSame('N', $saved->checked);
        $this->assertSame('class=EntregaList', $saved->action_url);
        $this->assertNotNull($saved->dt_message);
    }
}
