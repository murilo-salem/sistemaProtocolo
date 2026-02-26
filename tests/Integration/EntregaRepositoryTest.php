<?php

namespace Tests\Integration;

use Entrega;
use Tests\TestCase;

class EntregaRepositoryTest extends TestCase
{
    public function testEntregaStoreAndFindRoundTrip(): void
    {
        $entrega = new Entrega();
        $entrega->cliente_id = 5;
        $entrega->projeto_id = 9;
        $entrega->status = 'pendente';
        $entrega->mes_referencia = 2;
        $entrega->ano_referencia = 2026;
        $entrega->set_documentos(['Contrato' => 'files/contrato.pdf']);
        $entrega->store();

        $loaded = Entrega::find($entrega->id);

        $this->assertNotNull($loaded);
        $this->assertSame(5, $loaded->cliente_id);
        $this->assertSame(['Contrato' => 'files/contrato.pdf'], $loaded->get_documentos());
    }

    public function testEntregaWhereFiltersByStatusAndReference(): void
    {
        $this->seedRecords(Entrega::class, [
            (object) ['id' => 1, 'status' => 'pendente', 'mes_referencia' => 2, 'ano_referencia' => 2026],
            (object) ['id' => 2, 'status' => 'aprovado', 'mes_referencia' => 2, 'ano_referencia' => 2026],
            (object) ['id' => 3, 'status' => 'aprovado', 'mes_referencia' => 1, 'ano_referencia' => 2026],
        ]);

        $result = Entrega::where('status', '=', 'aprovado')
            ->where('mes_referencia', '=', 2)
            ->where('ano_referencia', '=', 2026)
            ->load();

        $this->assertCount(1, $result);
        $this->assertSame(2, $result[0]->id);
    }

    public function testEntregaDeleteRemovesRecord(): void
    {
        $this->seedRecords(Entrega::class, [
            (object) ['id' => 77, 'status' => 'pendente'],
        ]);

        $entrega = new Entrega(77);
        $entrega->delete();

        $this->assertNull(Entrega::find(77));
    }
}
