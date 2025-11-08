<?php

class ProjetoList extends TPage
{
    protected $form;
    protected $datagrid;
    protected $pageNavigation;
    
    public function __construct()
    {
        parent::__construct();

        // AppSecurity::checkAccess('gestor');  // só gestores podem acessar
        
        // Formulário de busca
        $this->form = new BootstrapFormBuilder('form_search_projeto');
        $this->form->setFormTitle('Projetos');
        
        $nome = new TEntry('nome');
        $ativo = new TCombo('ativo');
        
        $ativo->addItems(['1' => 'Ativo', '0' => 'Inativo']);
        
        $this->form->addFields([new TLabel('Nome')], [$nome]);
        $this->form->addFields([new TLabel('Status')], [$ativo]);
        
        $btn_search = $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search');
        $btn_new = $this->form->addAction('Novo', new TAction(['ProjetoForm', 'onEdit']), 'fa:plus green');
        
        // DataGrid
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->width = '100%';
        
        $col_id = new TDataGridColumn('id', 'ID', 'center', 50);
        $col_nome = new TDataGridColumn('nome', 'Nome', 'left');
        $col_dia = new TDataGridColumn('dia_vencimento', 'Dia Venc.', 'center', 100);
        $col_ativo = new TDataGridColumn('ativo', 'Status', 'center', 100);
        
        $col_ativo->setTransformer(function($value) {
            return $value == 1 ? 'Ativo' : 'Inativo';
        });
        
        $this->datagrid->addColumn($col_id);
        $this->datagrid->addColumn($col_nome);
        $this->datagrid->addColumn($col_dia);
        $this->datagrid->addColumn($col_ativo);
        
        $action_edit = new TDataGridAction(['ProjetoForm', 'onEdit'], ['id' => '{id}']);
        $action_delete = new TDataGridAction([$this, 'onDelete'], ['id' => '{id}']);
        
        $this->datagrid->addAction($action_edit, 'Editar', 'fa:edit blue');
        $this->datagrid->addAction($action_delete, 'Excluir', 'fa:trash red');
        
        $this->datagrid->createModel();
        
        // Paginação
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
        TSession::setValue('ProjetoList_filter', $data);
        $this->onReload();
    }
    
    public function onReload($param = NULL)
    {
        try {
            TTransaction::open('database');
            
            $criteria = new TCriteria;
            
            if ($filter = TSession::getValue('ProjetoList_filter')) {
                if ($filter->nome) {
                    $criteria->add(new TFilter('nome', 'like', "%{$filter->nome}%"));
                }
                if ($filter->ativo !== '') {
                    $criteria->add(new TFilter('ativo', '=', $filter->ativo));
                }
            }
            
            $criteria->setProperty('limit', 10);
            $criteria->setProperty('offset', isset($param['offset']) ? $param['offset'] : 0);
            
            $projetos = Projeto::getObjects($criteria);
            
            $this->datagrid->clear();
            if ($projetos) {
                foreach ($projetos as $projeto) {
                    $this->datagrid->addItem($projeto);
                }
            }
            
            $count = Projeto::countObjects($criteria);
            $this->pageNavigation->setCount($count);
            $this->pageNavigation->setProperties($param);
            
            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }
    
    public function onDelete($param)
    {
        $action = new TAction([$this, 'Delete']);
        $action->setParameters($param);
        
        new TQuestion('Deseja realmente excluir este projeto?', $action);
    }
    
    public function Delete($param)
    {
        try {
            TTransaction::open('database');
            
            $projeto = new Projeto($param['id']);
            $projeto->delete();
            
            TTransaction::close();
            
            $this->onReload();
            new TMessage('info', 'Projeto excluído com sucesso');
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
