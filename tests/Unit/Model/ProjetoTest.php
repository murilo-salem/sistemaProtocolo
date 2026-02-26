<?php

namespace Tests\Unit\Model;

use CompanyTemplate;
use Projeto;
use ProjetoDocumento;
use Tests\TestCase;

class ProjetoTest extends TestCase
{
    public function testGetDocumentosListReturnsProjetoDocumentos(): void
    {
        $this->seedRecords(ProjetoDocumento::class, [
            (object) ['id' => 1, 'projeto_id' => 10, 'nome_documento' => 'Contrato'],
            (object) ['id' => 2, 'projeto_id' => 10, 'nome_documento' => 'Balanco'],
            (object) ['id' => 3, 'projeto_id' => 99, 'nome_documento' => 'Outro'],
        ]);

        $projeto = new Projeto();
        $projeto->id = 10;

        $docs = $projeto->get_documentos_list();

        $this->assertCount(2, $docs);
        $this->assertSame('Contrato', $docs[0]->nome_documento);
    }

    public function testGetCompanyTemplateUsesFindById(): void
    {
        $this->seedRecords(CompanyTemplate::class, [
            (object) ['id' => 7, 'name' => 'Template Contabil'],
        ]);

        $projeto = new Projeto();
        $projeto->company_template_id = 7;

        $template = $projeto->get_company_template();

        $this->assertNotNull($template);
        $this->assertSame('Template Contabil', $template->name);
    }

    public function testDeleteRemovesProjetoAndRelatedDocuments(): void
    {
        $this->seedRecords(Projeto::class, [
            (object) ['id' => 3, 'nome' => 'Projeto A'],
        ]);
        $this->seedRecords(ProjetoDocumento::class, [
            (object) ['id' => 1, 'projeto_id' => 3, 'nome_documento' => 'Doc 1'],
            (object) ['id' => 2, 'projeto_id' => 5, 'nome_documento' => 'Doc 2'],
        ]);

        $projeto = new Projeto(3);
        $projeto->delete();

        $remainingDocs = ProjetoDocumento::where('projeto_id', '=', 3)->load();
        $deletedProject = Projeto::find(3);

        $this->assertCount(0, $remainingDocs);
        $this->assertNull($deletedProject);
    }
}
