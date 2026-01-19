<?php
class CompanyTemplate extends TRecord
{
    const TABLENAME = 'company_templates';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'max'; // Serial in Postgres, AutoIncrement in MySQL

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('name');
    }

    /**
     * Composition with CompanyDocTemplate
     */
    public function get_doc_templates()
    {
        return $this->getItems('CompanyDocTemplate', 'company_template_id');
    }
}
