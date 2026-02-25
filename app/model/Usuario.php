<?php

class Usuario extends TRecord
{
    const TABLENAME = 'usuario';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'max';
    
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nome');
        parent::addAttribute('email');
        parent::addAttribute('login');
        parent::addAttribute('senha');
        parent::addAttribute('tipo');
        parent::addAttribute('ativo');
        parent::addAttribute('gestor_id');
        parent::addAttribute('created_at');
    }
    
    public function get_gestor()
    {
        return new Usuario($this->gestor_id);
    }
    
    public function get_projetos()
    {
        return ClienteProjeto::where('cliente_id', '=', $this->id)->load();
    }
    
    public static function autenticar($login, $senha)
    {
        $usuario = Usuario::where('login', '=', $login)
                         ->where('ativo', '=', 1)
                         ->first();
        
        if ($usuario && password_verify($senha, $usuario->senha)) {
            return $usuario;
        }
        return false;
    }
}
