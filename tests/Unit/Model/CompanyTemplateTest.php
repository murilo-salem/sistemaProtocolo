<?php

namespace Tests\Unit\Model;

use CompanyDocTemplate;
use CompanyTemplate;
use Tests\TestCase;

class CompanyTemplateTest extends TestCase
{
    public function testGetDocTemplatesReturnsRelatedRows(): void
    {
        $this->seedRecords(CompanyDocTemplate::class, [
            (object) ['id' => 1, 'company_template_id' => 4, 'document_name' => 'Contrato'],
            (object) ['id' => 2, 'company_template_id' => 4, 'document_name' => 'Balanco'],
            (object) ['id' => 3, 'company_template_id' => 9, 'document_name' => 'Outro'],
        ]);

        $template = new CompanyTemplate();
        $template->id = 4;

        $docs = $template->get_doc_templates();

        $this->assertCount(2, $docs);
        $this->assertSame('Contrato', $docs[0]->document_name);
    }

    public function testCompanyDocTemplateResolvesParentTemplate(): void
    {
        $this->seedRecords(CompanyTemplate::class, [
            (object) ['id' => 7, 'name' => 'MEI'],
        ]);

        $doc = new CompanyDocTemplate();
        $doc->company_template_id = 7;

        $parent = $doc->get_company_template();
        $this->assertSame('MEI', $parent->name);
    }
}
