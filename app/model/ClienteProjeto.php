<?php

class ClienteProjeto extends TRecord
{
    const TABLENAME = 'cliente_projeto';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'max';
    
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('cliente_id');
        parent::addAttribute('projeto_id');
        parent::addAttribute('data_atribuicao');
    }
    
    public function get_cliente()
    {
        return new Usuario($this->cliente_id);
    }
    
    public function get_projeto()
    {
        return new Projeto($this->projeto_id);
    }
}

