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
        parent::addAttribute('created_at');
        parent::addAttribute('updated_at');
    }
    
    public function get_documentos()
    {
        return json_decode($this->documentos_json, true) ?: [];
    }
    
    public function set_documentos($array)
    {
        $this->documentos_json = json_encode($array, JSON_UNESCAPED_UNICODE);
    }
}
