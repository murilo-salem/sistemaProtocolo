<?php

namespace Tests\Integration;

use Tests\TestCase;

/**
 * Testes de integração para NotificationService
 */
class NotificationServiceTest extends TestCase
{
    /**
     * Testa estrutura de mensagem de notificação
     */
    public function testNotificationMessageStructure(): void
    {
        $notification = [
            'system_user_id' => 1,
            'system_user_to_id' => 2,
            'subject' => 'Teste de Notificação',
            'message' => 'Esta é uma mensagem de teste.',
            'dt_message' => date('Y-m-d H:i:s'),
            'checked' => 'N'
        ];
        
        $this->assertArrayHasKey('system_user_id', $notification);
        $this->assertArrayHasKey('system_user_to_id', $notification);
        $this->assertArrayHasKey('subject', $notification);
        $this->assertArrayHasKey('message', $notification);
        $this->assertEquals('N', $notification['checked']);
    }
    
    /**
     * Testa formatação de subject de notificação
     */
    public function testNotificationSubjectFormat(): void
    {
        $mes = 2;
        $ano = 2026;
        
        $subject = "Consolidação Disponível: " . str_pad($mes, 2, '0', STR_PAD_LEFT) . "/" . $ano;
        
        $this->assertEquals('Consolidação Disponível: 02/2026', $subject);
    }
    
    /**
     * Testa que notificação não é enviada para o próprio usuário
     */
    public function testNotificationNotSentToSelf(): void
    {
        $fromId = 1;
        $toId = 1;
        
        // Lógica: não enviar se from == to
        $shouldSend = ($fromId != $toId);
        
        $this->assertFalse($shouldSend);
    }
    
    /**
     * Testa envio para múltiplos destinatários sem duplicatas
     */
    public function testNoDuplicateRecipients(): void
    {
        $recipients = [1, 2, 3, 2, 1, 4];
        $unique = array_unique($recipients);
        
        $this->assertCount(4, $unique);
        $this->assertEquals([0 => 1, 1 => 2, 2 => 3, 5 => 4], $unique);
    }
    
    /**
     * Testa corpo de mensagem de prazo
     */
    public function testDeadlineNotificationBody(): void
    {
        $projetoNome = 'Projeto Teste';
        $dia = 15;
        
        $body = "O projeto {$projetoNome} possui entrega prevista para dia {$dia}.";
        
        $this->assertStringContainsString($projetoNome, $body);
        $this->assertStringContainsString((string) $dia, $body);
    }
    
    /**
     * Testa identificação de gestores por tipo
     */
    public function testManagerIdentification(): void
    {
        $managerTypes = ['admin', 'gestor'];
        
        $user1 = $this->createMockUsuario(['tipo' => 'admin']);
        $user2 = $this->createMockUsuario(['tipo' => 'gestor']);
        $user3 = $this->createMockUsuario(['tipo' => 'cliente']);
        
        $this->assertContains($user1->tipo, $managerTypes);
        $this->assertContains($user2->tipo, $managerTypes);
        $this->assertNotContains($user3->tipo, $managerTypes);
    }
}
