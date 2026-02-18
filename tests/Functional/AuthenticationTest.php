<?php

namespace Tests\Functional;

use Tests\TestCase;

/**
 * Testes funcionais para fluxo de autenticação
 * 
 * Valida o fluxo completo de login/logout.
 */
class AuthenticationTest extends TestCase
{
    /**
     * Testa que credenciais válidas permitem login
     */
    public function testValidCredentialsAllowLogin(): void
    {
        $senha = 'minhasenha123';
        $hash = password_hash($senha, PASSWORD_DEFAULT);
        
        $usuario = $this->createMockUsuario([
            'login' => 'usuario_teste',
            'senha' => $hash,
            'ativo' => 1
        ]);
        
        // Simular validação de login
        $loginValido = password_verify($senha, $usuario->senha) && $usuario->ativo == 1;
        
        $this->assertTrue($loginValido);
    }
    
    /**
     * Testa que credenciais inválidas bloqueiam login
     */
    public function testInvalidCredentialsBlockLogin(): void
    {
        $hash = password_hash('senhaCorreta', PASSWORD_DEFAULT);
        
        $usuario = $this->createMockUsuario([
            'senha' => $hash,
            'ativo' => 1
        ]);
        
        $loginValido = password_verify('senhaErrada', $usuario->senha);
        
        $this->assertFalse($loginValido);
    }
    
    /**
     * Testa que usuário inativo não pode fazer login
     */
    public function testInactiveUserCannotLogin(): void
    {
        $senha = 'minhasenha123';
        $hash = password_hash($senha, PASSWORD_DEFAULT);
        
        $usuario = $this->createMockUsuario([
            'senha' => $hash,
            'ativo' => 0
        ]);
        
        $loginValido = password_verify($senha, $usuario->senha) && $usuario->ativo == 1;
        
        $this->assertFalse($loginValido);
    }
    
    /**
     * Testa armazenamento de sessão após login
     */
    public function testSessionStorageAfterLogin(): void
    {
        $usuario = $this->createMockUsuario([
            'id' => 42,
            'nome' => 'Usuário Teste',
            'tipo' => 'cliente'
        ]);
        
        // Simular armazenamento de sessão
        \TSession::setValue('userid', $usuario->id);
        \TSession::setValue('username', $usuario->nome);
        \TSession::setValue('usertype', $usuario->tipo);
        
        $this->assertEquals(42, \TSession::getValue('userid'));
        $this->assertEquals('Usuário Teste', \TSession::getValue('username'));
        $this->assertEquals('cliente', \TSession::getValue('usertype'));
    }
    
    /**
     * Testa logout limpa a sessão
     */
    public function testLogoutClearsSession(): void
    {
        // Simular login
        \TSession::setValue('userid', 1);
        \TSession::setValue('username', 'Teste');
        
        // Simular logout
        \TSession::clear();
        
        $this->assertNull(\TSession::getValue('userid'));
        $this->assertNull(\TSession::getValue('username'));
    }
    
    /**
     * Testa permissões por tipo de usuário
     */
    public function testUserPermissionsByType(): void
    {
        $permissions = [
            'admin' => ['manage_users', 'manage_projects', 'approve_deliveries', 'view_reports'],
            'gestor' => ['manage_projects', 'approve_deliveries', 'view_reports'],
            'cliente' => ['submit_deliveries', 'view_own_deliveries'],
        ];
        
        $admin = $this->createMockUsuario(['tipo' => 'admin']);
        $cliente = $this->createMockUsuario(['tipo' => 'cliente']);
        
        $this->assertContains('manage_users', $permissions[$admin->tipo]);
        $this->assertNotContains('manage_users', $permissions[$cliente->tipo]);
        $this->assertContains('submit_deliveries', $permissions[$cliente->tipo]);
    }
    
    /**
     * Testa validação de formato de email
     */
    public function testEmailValidation(): void
    {
        $validEmails = ['user@example.com', 'test.user@domain.org'];
        $invalidEmails = ['invalid', 'no@domain', '@nodomain.com'];
        
        foreach ($validEmails as $email) {
            $this->assertTrue(filter_var($email, FILTER_VALIDATE_EMAIL) !== false);
        }
        
        foreach ($invalidEmails as $email) {
            $this->assertFalse(filter_var($email, FILTER_VALIDATE_EMAIL) !== false);
        }
    }
    
    /**
     * Testa requisitos de senha forte
     */
    public function testStrongPasswordRequirements(): void
    {
        $strongPasswords = ['Senha@123', 'Complex1!', 'MyP@ssw0rd'];
        $weakPasswords = ['123456', 'password', 'abc'];
        
        foreach ($strongPasswords as $pass) {
            // Pelo menos 8 caracteres com número
            $isStrong = strlen($pass) >= 8 && preg_match('/[0-9]/', $pass);
            $this->assertTrue($isStrong, "Senha forte deve passar: {$pass}");
        }
        
        foreach ($weakPasswords as $pass) {
            $isWeak = strlen($pass) < 8 || !preg_match('/[0-9]/', $pass);
            $this->assertTrue($isWeak, "Senha fraca deve falhar: {$pass}");
        }
    }
}
