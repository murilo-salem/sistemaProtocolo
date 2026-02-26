<?php

namespace Tests\Functional;

use TSession;
use Tests\TestCase;
use Usuario;

class AuthenticationTest extends TestCase
{
    public function testValidCredentialsAuthenticateAndStoreSession(): void
    {
        $this->seedRecords(Usuario::class, [
            (object) [
                'id' => 42,
                'nome' => 'Usuario Teste',
                'login' => 'usuario_teste',
                'senha' => password_hash('Senha@123', PASSWORD_DEFAULT),
                'tipo' => 'cliente',
                'ativo' => 1,
            ],
        ]);

        $usuario = Usuario::autenticar('usuario_teste', 'Senha@123');

        $this->assertInstanceOf(Usuario::class, $usuario);

        TSession::setValue('userid', $usuario->id);
        TSession::setValue('username', $usuario->nome);
        TSession::setValue('usertype', $usuario->tipo);

        $this->assertSame(42, TSession::getValue('userid'));
        $this->assertSame('Usuario Teste', TSession::getValue('username'));
        $this->assertSame('cliente', TSession::getValue('usertype'));
    }

    public function testInvalidCredentialsDoNotAuthenticate(): void
    {
        $this->seedRecords(Usuario::class, [
            (object) [
                'id' => 1,
                'login' => 'cliente1',
                'senha' => password_hash('SenhaCerta', PASSWORD_DEFAULT),
                'ativo' => 1,
            ],
        ]);

        $this->assertFalse(Usuario::autenticar('cliente1', 'senha_errada'));
    }

    public function testInactiveUserCannotAuthenticate(): void
    {
        $this->seedRecords(Usuario::class, [
            (object) [
                'id' => 2,
                'login' => 'inativo',
                'senha' => password_hash('Senha@123', PASSWORD_DEFAULT),
                'ativo' => 0,
            ],
        ]);

        $this->assertFalse(Usuario::autenticar('inativo', 'Senha@123'));
    }

    public function testLogoutClearsSession(): void
    {
        TSession::setValue('userid', 1);
        TSession::setValue('username', 'Teste');

        TSession::clear();

        $this->assertNull(TSession::getValue('userid'));
        $this->assertNull(TSession::getValue('username'));
    }
}
