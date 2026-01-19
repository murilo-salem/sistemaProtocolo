<?php

class ProjetoDocumento extends TRecord
{
    const TABLENAME = 'projeto_documento';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'max';
    
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('projeto_id');
        parent::addAttribute('nome_documento');
        parent::addAttribute('obrigatorio');
        parent::addAttribute('content');
        parent::addAttribute('status');
    }
}
