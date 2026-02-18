<?php

class Projeto extends TRecord
{
    const TABLENAME = 'projeto';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'max';
    
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nome');
        parent::addAttribute('descricao');
        parent::addAttribute('documentos_json');
        parent::addAttribute('dia_vencimento');
        parent::addAttribute('ativo');
        parent::addAttribute('company_template_id');
        parent::addAttribute('is_template');
        parent::addAttribute('created_at');
        parent::addAttribute('updated_at');
    }
    
    public function get_documentos_list()
    {
        return $this->getItems('ProjetoDocumento', 'projeto_id');
    }

    public function get_company_template()
    {
        return CompanyTemplate::find($this->company_template_id);
    }

    public function delete($id = NULL)
    {
        $id = isset($id) ? $id : $this->id;
        
        ProjetoDocumento::where('projeto_id', '=', $id)->delete();
        
        parent::delete($id);
    }
}
