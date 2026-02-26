<?php

namespace Tests\Unit\Model;

use ClienteProjeto;
use Projeto;
use Usuario;
use Tests\TestCase;

class ClienteProjetoTest extends TestCase
{
    public function testGetClienteAndProjetoResolveRelations(): void
    {
        $this->seedRecords(Usuario::class, [(object) ['id' => 11, 'nome' => 'Cliente X']]);
        $this->seedRecords(Projeto::class, [(object) ['id' => 22, 'nome' => 'Projeto Y']]);

        $link = new ClienteProjeto();
        $link->cliente_id = 11;
        $link->projeto_id = 22;

        $cliente = $link->get_cliente();
        $projeto = $link->get_projeto();

        $this->assertSame(11, $cliente->id);
        $this->assertSame(22, $projeto->id);
    }
}
