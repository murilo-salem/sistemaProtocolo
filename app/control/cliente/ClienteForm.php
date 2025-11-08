<?php

class ClienteForm extends TPage
{
    protected $form;
    
    public function __construct()
    {
        parent::__construct();
        
        $this->form = new BootstrapFormBuilder('form_cliente');
        $this->form->setFormTitle('Cliente');
        
        $id = new THidden('id');
        $nome = new TEntry('nome');
        $email = new TEntry('email');
        $login = new TEntry('login');
        $senha = new TEntry('senha');
        $ativo = new TCheckButton('ativo');
        
        $projetos = new TDBCheckGroup('projetos', 'database', 'Projeto', 'id', 'nome');
        
        $nome->setSize('100%');
        $email->setSize('100%');
        $login->setSize('100%');
        $senha->setSize('100%');
        
        $login->setEditable(FALSE);
        $senha->setEditable(FALSE);
        
        $nome->addValidation('Nome', new TRequiredValidator);
        $email->addValidation('Email', new TRequiredValidator);
        $email->addValidation('Email', new TEmailValidator);
        
        $this->form->addFields([$id]);
        $this->form->addFields([new TLabel('Nome*')], [$nome]);
        $this->form->addFields([new TLabel('Email*')], [$email]);
        $this->form->addFields([new TLabel('Login (gerado)' )], [$login]);
        $this->form->addFields([new TLabel('Senha (gerada)')], [$senha]);
        $this->form->addFields([new TLabel('Ativo')], [$ativo]);
        $this->form->addFields([new TLabel('Projetos Vinculados')], [$projetos]);
        
        $btn_save = $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fa:save green');
        $btn_back = $this->form->addAction('Voltar', new TAction(['ClienteList', 'onReload']), 'fa:arrow-left blue');
        
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add($this->form);
        
        parent::add($container);
    }
    
    public function onEdit($param)
    {
        try {
            if (isset($param['id'])) {
                TTransaction::open('database');
                
                $usuario = new Usuario($param['id']);
                $data = $usuario->toArray();
                
                // Carregar projetos vinculados
                $vinculados = ClienteProjeto::where('cliente_id', '=', $usuario->id)->load();
                $projetos_ids = [];
                foreach ($vinculados as $vinculo) {
                    $projetos_ids[] = $vinculo->projeto_id;
                }
                $data['projetos'] = $projetos_ids;
                
                $this->form->setData((object) $data);
                
                TTransaction::close();
            } else {
                $this->form->setData(new stdClass);
            }
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }
    
    public function onSave($param)
    {
        try {
            $this->form->validate();
            
            TTransaction::open('database');
            
            $usuario = new Usuario;
            
            if (!empty($param['id'])) {
                $usuario = new Usuario($param['id']);
            } else {
                // Gerar login e senha
                $login_base = strtolower(substr($param['nome'], 0, 5));
                $login_base = preg_replace('/[^a-z]/', '', $login_base);
                $login = $login_base . rand(100, 999);
                
                $senha_gerada = $this->gerarSenha(8);
                
                $usuario->login = $login;
                $usuario->senha = password_hash($senha_gerada, PASSWORD_DEFAULT);
                $usuario->tipo = 'cliente';
                
                $param['login'] = $login;
                $param['senha'] = $senha_gerada;
            }
            
            $usuario->nome = $param['nome'];
            $usuario->email = $param['email'];
            $usuario->ativo = isset($param['ativo']) ? 1 : 0;
            $usuario->store();
            
            // Remover vínculos antigos
            ClienteProjeto::where('cliente_id', '=', $usuario->id)->delete();
            
            // Adicionar novos vínculos
            if (isset($param['projetos']) && is_array($param['projetos'])) {
                foreach ($param['projetos'] as $projeto_id) {
                    $vinculo = new ClienteProjeto;
                    $vinculo->cliente_id = $usuario->id;
                    $vinculo->projeto_id = $projeto_id;
                    $vinculo->store();
                }
            }
            
            TTransaction::close();
            
            if (isset($senha_gerada)) {
                $msg = "Cliente salvo com sucesso!\n\n";
                $msg .= "Login: {$param['login']}\n";
                $msg .= "Senha: {$senha_gerada}\n\n";
                $msg .= "Anote estas informações!";
                new TMessage('info', $msg);
            } else {
                new TMessage('info', 'Cliente atualizado com sucesso');
            }
            
            TApplication::gotoPage('ClienteList');
            
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
    
    private function gerarSenha($length = 8)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $senha = '';
        for ($i = 0; $i < $length; $i++) {
            $senha .= $chars[rand(0, strlen($chars) - 1)];
        }
        return $senha;
    }
}

