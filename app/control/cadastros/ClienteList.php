<?php

class ClienteList extends TPage
{
    protected $form;
    protected $datagrid;
    protected $pageNavigation;
    
    public function __construct()
    {
        parent::__construct();

        // AppSecurity::checkAccess('gestor');  // só gestores podem acessar
        
        $this->form = new BootstrapFormBuilder('form_search_cliente');
        $this->form->setFormTitle('Clientes');
        
        $nome = new TEntry('nome');
        $email = new TEntry('email');
        $ativo = new TCombo('ativo');
        
        $ativo->addItems(['1' => 'Ativo', '0' => 'Inativo']);
        
        $this->form->addFields([new TLabel('Nome')], [$nome]);
        $this->form->addFields([new TLabel('Email')], [$email]);
        $this->form->addFields([new TLabel('Status')], [$ativo]);
        
        $btn_search = $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search');
        $btn_new = $this->form->addAction('Novo', new TAction(['ClienteForm', 'onEdit']), 'fa:plus green');
        
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->width = '100%';
        
        $col_id = new TDataGridColumn('id', 'ID', 'center', 50);
        $col_nome = new TDataGridColumn('nome', 'Nome', 'left');
        $col_email = new TDataGridColumn('email', 'Email', 'left');
        $col_login = new TDataGridColumn('login', 'Login', 'center');
        $col_ativo = new TDataGridColumn('ativo', 'Status', 'center', 100);
        
        $col_ativo->setTransformer(function($value) {
            return $value == 1 ? 'Ativo' : 'Inativo';
        });
        
        $this->datagrid->addColumn($col_id);
        $this->datagrid->addColumn($col_nome);
        $this->datagrid->addColumn($col_email);
        $this->datagrid->addColumn($col_login);
        $this->datagrid->addColumn($col_ativo);
        
        $action_edit = new TDataGridAction(['ClienteForm', 'onEdit'], ['id' => '{id}']);
        $action_delete = new TDataGridAction([$this, 'onDelete'], ['id' => '{id}']);
        
        $this->datagrid->addAction($action_edit, 'Editar', 'fa:edit blue');
        $this->datagrid->addAction($action_delete, 'Desativar', 'fa:ban red');
        
        $this->datagrid->createModel();
        
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction([$this, 'onReload']));
        
        $panel = new TPanelGroup;
        $panel->add($this->datagrid);
        $panel->addFooter($this->pageNavigation);
        
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add($this->form);
        $container->add($panel);
        
        parent::add($container);
    }
    
    public function onSearch()
    {
        $data = $this->form->getData();
        TSession::setValue('ClienteList_filter', $data);
        $this->onReload();
    }
    
    public function onReload($param = NULL)
    {
        try {
            TTransaction::open('database');
            
            $criteria = new TCriteria;
            $criteria->add(new TFilter('tipo', '=', 'cliente'));
            
            if ($filter = TSession::getValue('ClienteList_filter')) {
                if ($filter->nome) {
                    $criteria->add(new TFilter('nome', 'like', "%{$filter->nome}%"));
                }
                if ($filter->email) {
                    $criteria->add(new TFilter('email', 'like', "%{$filter->email}%"));
                }
                if ($filter->ativo !== '') {
                    $criteria->add(new TFilter('ativo', '=', $filter->ativo));
                }
            }
            
            $criteria->setProperty('limit', 10);
            $criteria->setProperty('offset', isset($param['offset']) ? $param['offset'] : 0);
            
            $clientes = Usuario::getObjects($criteria);
            
            $this->datagrid->clear();
            if ($clientes) {
                foreach ($clientes as $cliente) {
                    $this->datagrid->addItem($cliente);
                }
            }
            
            $count = Usuario::countObjects($criteria);
            $this->pageNavigation->setCount($count);
            $this->pageNavigation->setProperties($param);
            
            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }
    
    public function onDelete($param)
    {
        try {
            TTransaction::open('database');
            
            $cliente = new Usuario($param['id']);
            $cliente->ativo = 0;
            $cliente->store();
            
            TTransaction::close();
            
            $this->onReload();
            new TMessage('info', 'Cliente desativado com sucesso');
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
    
    public function show()
    {
        $this->onReload();
        parent::show();
    }
}
