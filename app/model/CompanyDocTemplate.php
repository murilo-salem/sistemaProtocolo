<?php
class CompanyDocTemplate extends TRecord
{
    const TABLENAME = 'company_doc_templates';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'max';

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('company_template_id');
        parent::addAttribute('document_name');
        parent::addAttribute('is_required');
    }

    public function get_company_template()
    {
        return CompanyTemplate::find($this->company_template_id);
    }
}
