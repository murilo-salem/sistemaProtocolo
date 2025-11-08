<?php

class Entrega extends TRecord
{
    const TABLENAME = 'entrega';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'max';
    
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('cliente_id');
        parent::addAttribute('projeto_id');
        parent::addAttribute('mes_referencia');
        parent::addAttribute('ano_referencia');
        parent::addAttribute('documentos_json');
        parent::addAttribute('status');
        parent::addAttribute('data_entrega');
        parent::addAttribute('data_aprovacao');
        parent::addAttribute('aprovado_por');
        parent::addAttribute('observacoes');
        parent::addAttribute('consolidado');
        parent::addAttribute('arquivo_consolidado');
    }
    
    public function get_cliente()
    {
        return new Usuario($this->cliente_id);
    }
    
    public function get_projeto()
    {
        return new Projeto($this->projeto_id);
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
