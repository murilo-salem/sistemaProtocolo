<?php

class ProjetoList extends TPage
{
    protected $form;
    protected $datagrid;
    protected $pageNavigation;
    private $activeFilter;
    
    public function __construct()
    {
        parent::__construct();
        
        // Get active filter from session
        $this->activeFilter = TSession::getValue('ProjetoList_quickfilter') ?? 'todos';
        
        // Build the page HTML structure
        $html = new TElement('div');
        $html->class = 'list-page-container';
        
        // Page Header
        $pageHeader = new TElement('div');
        $pageHeader->class = 'list-page-header';
        
        $headerLeft = new TElement('div');
        $headerLeft->class = 'header-left';
        $headerLeft->add('<h1 class="page-title"><i class="fa fa-briefcase"></i> Projetos</h1>');
        
        $headerRight = new TElement('div');
        $headerRight->class = 'header-right';
        
        $btnNew = new TElement('a');
        $btnNew->href = 'index.php?class=ProjetoForm';
        $btnNew->class = 'btn-add-new';
        $btnNew->add('<i class="fa fa-plus"></i> Novo Projeto');
        $headerRight->add($btnNew);
        
        $pageHeader->add($headerLeft);
        $pageHeader->add($headerRight);
        $html->add($pageHeader);
        
        // Main Card
        $card = new TElement('div');
        $card->class = 'list-card';
        
        // Quick Filter Tabs
        $filterTabs = new TElement('div');
        $filterTabs->class = 'quick-filter-tabs';
        
        $tabs = [
            'todos' => ['label' => 'Todos', 'icon' => 'fa-list'],
            'ativos' => ['label' => 'Ativos', 'icon' => 'fa-check-circle'],
            'inativos' => ['label' => 'Inativos', 'icon' => 'fa-times-circle']
        ];
        
        foreach ($tabs as $key => $tab) {
            $tabLink = new TElement('a');
            $tabLink->href = "javascript:__adianti_load_page('index.php?class=ProjetoList&method=onQuickFilter&filter={$key}')";
            $tabLink->class = 'filter-tab' . ($this->activeFilter === $key ? ' active' : '');
            $tabLink->add("<i class=\"fa {$tab['icon']}\"></i> {$tab['label']}");
            $filterTabs->add($tabLink);
        }
        
        $card->add($filterTabs);
        
        // Search Bar
        $searchBar = new TElement('div');
        $searchBar->class = 'search-bar';
        
        $this->form = new TForm('form_search_projeto');
        
        $searchInput = new TEntry('nome');
        $searchInput->setProperty('placeholder', 'Buscar projeto por nome...');
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
        
        $col_nome = new TDataGridColumn('nome', 'Projeto', 'left');
        $col_dia = new TDataGridColumn('dia_vencimento', 'Vencimento', 'center', 120);
        $col_ativo = new TDataGridColumn('ativo', 'Status', 'center', 120);
        
        // Transformer for project name with icon
        $col_nome->setTransformer(function($value, $object) {
            return "<div class='item-name'>
                        <div class='item-icon'><i class='fa fa-folder'></i></div>
                        <div class='item-details'>
                            <span class='item-title'>{$value}</span>
                            <span class='item-meta'>ID: {$object->id}</span>
                        </div>
                    </div>";
        });
        
        $col_dia->setTransformer(function($value) {
            return "<span class='meta-value'><i class='fa fa-calendar'></i> Dia {$value}</span>";
        });
        
        $col_ativo->setTransformer(function($value) {
            if ($value == 1) {
                return '<span class="badge-status badge-success">Ativo</span>';
            } else {
                return '<span class="badge-status badge-inactive">Inativo</span>';
            }
        });
        
        $this->datagrid->addColumn($col_nome);
        $this->datagrid->addColumn($col_dia);
        $this->datagrid->addColumn($col_ativo);
        
        // Actions
        $action_edit = new TDataGridAction(['ProjetoForm', 'onEdit'], ['id' => '{id}']);
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
    
    public static function onQuickFilter($param)
    {
        TSession::setValue('ProjetoList_quickfilter', $param['filter']);
        TSession::setValue('ProjetoList_filter', null);
        TApplication::loadPage('ProjetoList');
    }
    
    public function onSearch()
    {
        $data = $this->form->getData();
        TSession::setValue('ProjetoList_filter', $data);
        TSession::setValue('ProjetoList_quickfilter', 'todos');
        $this->onReload();
    }
    
    public function onReload($param = NULL)
    {
        try {
            TTransaction::open('database');
            
            $criteria = new TCriteria;
            
            // Apply quick filter
            $quickFilter = TSession::getValue('ProjetoList_quickfilter') ?? 'todos';
            if ($quickFilter === 'ativos') {
                $criteria->add(new TFilter('ativo', '=', 1));
            } elseif ($quickFilter === 'inativos') {
                $criteria->add(new TFilter('ativo', '=', 0));
            }
            
            // Apply search filter
            if ($filter = TSession::getValue('ProjetoList_filter')) {
                if (!empty($filter->nome)) {
                    $criteria->add(new TFilter('nome', 'like', "%{$filter->nome}%"));
                }
            }
            
            $criteria->setProperty('limit', 10);
            $criteria->setProperty('offset', isset($param['offset']) ? $param['offset'] : 0);
            $criteria->setProperty('order', 'nome');
            
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
            
            $key = $param['id'];
            
            // Delete client-project links first
            ClienteProjeto::where('projeto_id', '=', $key)->delete();
            
            // Delete deliveries
            Entrega::where('projeto_id', '=', $key)->delete();

            ProjetoDocumento::where('projeto_id', '=', $key)->delete();
            
            $projeto = new Projeto($key);
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
