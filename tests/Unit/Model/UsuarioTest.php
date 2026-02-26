<?php

namespace Tests\Unit\Model;

use ClienteProjeto;
use Usuario;
use Tests\TestCase;

class UsuarioTest extends TestCase
{
    public function testGetGestorReturnsAssociatedUser(): void
    {
        $this->seedRecords(Usuario::class, [
            (object) ['id' => 2, 'nome' => 'Gestor A'],
        ]);

        $usuario = new Usuario();
        $usuario->gestor_id = 2;

        $gestor = $usuario->get_gestor();

        $this->assertInstanceOf(Usuario::class, $gestor);
        $this->assertSame(2, $gestor->id);
    }

    public function testGetProjetosReturnsLinksForCliente(): void
    {
        $usuario = new Usuario();
        $usuario->id = 15;

        $this->seedRecords(ClienteProjeto::class, [
            (object) ['id' => 1, 'cliente_id' => 15, 'projeto_id' => 9],
            (object) ['id' => 2, 'cliente_id' => 15, 'projeto_id' => 10],
            (object) ['id' => 3, 'cliente_id' => 99, 'projeto_id' => 11],
        ]);

        $links = $usuario->get_projetos();

        $this->assertCount(2, $links);
        $this->assertSame(9, $links[0]->projeto_id);
    }

    public function testAutenticarReturnsUserForValidCredentials(): void
    {
        $hash = password_hash('Senha@123', PASSWORD_DEFAULT);
        $this->seedRecords(Usuario::class, [
            (object) ['id' => 1, 'login' => 'joao', 'senha' => $hash, 'ativo' => 1],
        ]);

        $result = Usuario::autenticar('joao', 'Senha@123');

        $this->assertInstanceOf(Usuario::class, $result);
        $this->assertSame(1, $result->id);
    }

    public function testAutenticarFailsForInvalidPasswordOrInactiveUser(): void
    {
        $this->seedRecords(Usuario::class, [
            (object) ['id' => 1, 'login' => 'joao', 'senha' => password_hash('Senha@123', PASSWORD_DEFAULT), 'ativo' => 1],
            (object) ['id' => 2, 'login' => 'maria', 'senha' => password_hash('Senha@123', PASSWORD_DEFAULT), 'ativo' => 0],
        ]);

        $this->assertFalse(Usuario::autenticar('joao', 'errada'));
        $this->assertFalse(Usuario::autenticar('maria', 'Senha@123'));
    }
}
