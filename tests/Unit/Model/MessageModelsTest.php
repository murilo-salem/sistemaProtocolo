<?php

namespace Tests\Unit\Model;

use ChatMessage;
use Mensagem;
use Usuario;
use Tests\TestCase;

class MessageModelsTest extends TestCase
{
    public function testChatMessageSenderAndReceiver(): void
    {
        $this->seedRecords(Usuario::class, [
            (object) ['id' => 1, 'nome' => 'Ana'],
            (object) ['id' => 2, 'nome' => 'Bruno'],
        ]);

        $chat = new ChatMessage();
        $chat->sender_id = 1;
        $chat->receiver_id = 2;

        $this->assertSame(1, $chat->get_sender()->id);
        $this->assertSame(2, $chat->get_receiver()->id);
    }

    public function testMensagemSenderAndReceiver(): void
    {
        $this->seedRecords(Usuario::class, [
            (object) ['id' => 3, 'nome' => 'Carlos'],
            (object) ['id' => 4, 'nome' => 'Denise'],
        ]);

        $mensagem = new Mensagem();
        $mensagem->system_user_id = 3;
        $mensagem->system_user_to_id = 4;

        $this->assertSame(3, $mensagem->get_sender()->id);
        $this->assertSame(4, $mensagem->get_receiver()->id);
    }
}
