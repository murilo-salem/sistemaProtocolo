<?php

class EmpresaList extends TPage
{
    protected $form;
    protected $datagrid;
    protected $pageNavigation;
    
    public function __construct()
    {
        parent::__construct();
        
        // Build the page HTML structure
        $html = new TElement('div');
        $html->class = 'list-page-container';
        
        // Page Header
        $pageHeader = new TElement('div');
        $pageHeader->class = 'list-page-header';
        
        $headerLeft = new TElement('div');
        $headerLeft->class = 'header-left';
        $headerLeft->add('<h1 class="page-title"><i class="fa fa-building"></i> Empresas</h1>');
        
        $headerRight = new TElement('div');
        $headerRight->class = 'header-right';
        
        $btnNew = new TElement('a');
        $btnNew->href = 'index.php?class=EmpresaForm';
        $btnNew->class = 'btn-add-new';
        $btnNew->add('<i class="fa fa-plus"></i> Nova Empresa');
        $headerRight->add($btnNew);
        
        $pageHeader->add($headerLeft);
        $pageHeader->add($headerRight);
        $html->add($pageHeader);
        
        // Main Card
        $card = new TElement('div');
        $card->class = 'list-card';
        
        // Search Bar
        $searchBar = new TElement('div');
        $searchBar->class = 'search-bar';
        
        $this->form = new TForm('form_search_empresa');
        
        $searchInput = new TEntry('nome');
        $searchInput->setProperty('placeholder', 'Buscar empresa por nome...');
        $searchInput->setSize('100%');
        
        $searchWrapper = new TElement('div');
        $searchWrapper->class = 'search-input-wrapper';
        $searchIcon = new TElement('i');
        $searchIcon->class = 'fa fa-search search-icon';
        $searchWrapper->add($searchIcon);
        $searchWrapper->add($searchInput);
        
        $btnSearch = new TButton('btn_search');
        $btnSearch->setAction(new TAction([$this, 'onSearch']), 'Buscar');
        $btnSearch->class = 'btn-search';
        
        $this->form->add($searchWrapper);
        $this->form->add($btnSearch);
        $this->form->setFields([$searchInput, $btnSearch]);
        
        $searchBar->add($this->form);
        $card->add($searchBar);
        
        // DataGrid
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->width = '100%';
        $this->datagrid->class = 'modern-datagrid';
        
        $col_nome = new TDataGridColumn('name', 'Empresa', 'left');
        $col_projetos = new TDataGridColumn('id', 'Projetos', 'center', 120);
        $col_docs = new TDataGridColumn('id', 'Documentos', 'center', 120);
        
        // Transformer for company name with icon
        $col_nome->setTransformer(function($value, $object) {
            $initials = strtoupper(substr($value, 0, 2));
            return "<div class='item-name'>
                        <div class='item-icon'><i class='fa fa-building'></i></div>
                        <div class='item-details'>
                            <span class='item-title'>{$value}</span>
                            <span class='item-meta'>ID: {$object->id}</span>
                        </div>
                    </div>";
        });
        
        // Count projects linked to this company
        $col_projetos->setTransformer(function($value) {
            try {
                TTransaction::open('database');
                $count = Projeto::where('company_template_id', '=', $value)->count();
                TTransaction::close();
                return "<span class='meta-value'><i class='fa fa-briefcase'></i> {$count}</span>";
            } catch (Exception $e) {
                return '0';
            }
        });
        
        // Count documents
        $col_docs->setTransformer(function($value) {
            try {
                TTransaction::open('database');
                $count = CompanyDocTemplate::where('company_template_id', '=', $value)->count();
                TTransaction::close();
                return "<span class='meta-value'><i class='fa fa-file-text'></i> {$count}</span>";
            } catch (Exception $e) {
                return '0';
            }
        });
        
        $this->datagrid->addColumn($col_nome);
        $this->datagrid->addColumn($col_projetos);
        $this->datagrid->addColumn($col_docs);
        
        // Actions
        $action_edit = new TDataGridAction(['EmpresaForm', 'onEdit'], ['id' => '{id}']);
        $action_delete = new TDataGridAction([$this, 'onDelete'], ['id' => '{id}']);
        
        $this->datagrid->addAction($action_edit, 'Editar', 'fa:edit blue');
        $this->datagrid->addAction($action_delete, 'Excluir', 'fa:trash red');
        
        $this->datagrid->createModel();
        
        // Pagination
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction([$this, 'onReload']));
        
        // Datagrid wrapper
        $gridWrapper = new TElement('div');
        $gridWrapper->class = 'datagrid-wrapper';
        $gridWrapper->add($this->datagrid);
        
        $card->add($gridWrapper);
        
        // Pagination wrapper
        $paginationWrapper = new TElement('div');
        $paginationWrapper->class = 'pagination-wrapper';
        $paginationWrapper->add($this->pageNavigation);
        $card->add($paginationWrapper);
        
        $html->add($card);
        
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add($html);
        
        parent::add($container);
    }
    
    public function onSearch()
    {
        $data = $this->form->getData();
        TSession::setValue('EmpresaList_filter', $data);
        $this->onReload();
    }
    
    public function onReload($param = NULL)
    {
        try {
            TTransaction::open('database');
            
            $criteria = new TCriteria;
            
            // Apply search filter
            if ($filter = TSession::getValue('EmpresaList_filter')) {
                if (!empty($filter->nome)) {
                    $criteria->add(new TFilter('name', 'like', "%{$filter->nome}%"));
                }
            }
            
            $criteria->setProperty('limit', 10);
            $criteria->setProperty('offset', isset($param['offset']) ? $param['offset'] : 0);
            $criteria->setProperty('order', 'name');
            
            $empresas = CompanyTemplate::getObjects($criteria);
            
            $this->datagrid->clear();
            if ($empresas) {
                foreach ($empresas as $empresa) {
                    $this->datagrid->addItem($empresa);
                }
            }
            
            // Limpa ordenação para evitar erro de agregados no PostgreSQL
            $criteria->setProperty('limit', NULL);
            $criteria->setProperty('offset', NULL);
            $criteria->setProperty('order', NULL);
            $count = CompanyTemplate::countObjects($criteria);
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
            $empresa = CompanyTemplate::find($param['id']);
            TTransaction::close();
            
            if (!$empresa) {
                return;
            }
            
            $action = new TAction([$this, 'Delete']);
            $action->setParameter('id', $param['id']);
            
            new TQuestion('Deseja realmente excluir esta empresa?', $action);
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }
    
    public function Delete($param)
    {
        try {
            TTransaction::open('database');
            
            $key = $param['id'];
            $empresa = CompanyTemplate::find($key);
            
            if (!$empresa) {
                TTransaction::close();
                return;
            }
            
            // Check if there are projects linked
            $count = Projeto::where('company_template_id', '=', $key)->count();
            if ($count > 0) {
                throw new Exception("Não é possível excluir esta empresa pois existem {$count} projeto(s) vinculado(s) a ela.");
            }
            
            // Delete documents first
            CompanyDocTemplate::where('company_template_id', '=', $key)->delete();
            
            // Delete company
            $empresa->delete();
            
            TTransaction::close();
            
            $this->onReload();
            new TMessage('info', 'Empresa excluída com sucesso');
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
