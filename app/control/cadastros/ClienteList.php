<?php

class ClienteList extends TPage
{
    protected $form;
    protected $datagrid;
    protected $pageNavigation;
    private $activeFilter;
    
    public function __construct()
    {
        parent::__construct();
        
        // Get active filter from session
        $this->activeFilter = TSession::getValue('ClienteList_quickfilter') ?? 'todos';
        
        // Build the page HTML structure
        $html = new TElement('div');
        $html->class = 'list-page-container';
        
        // Page Header
        $pageHeader = new TElement('div');
        $pageHeader->class = 'list-page-header';
        
        $headerLeft = new TElement('div');
        $headerLeft->class = 'header-left';
        $headerLeft->add('<h1 class="page-title"><i class="fa fa-users"></i> Clientes</h1>');
        
        $headerRight = new TElement('div');
        $headerRight->class = 'header-right';
        
        $btnNew = new TElement('a');
        $btnNew->href = 'index.php?class=ClienteForm';
        $btnNew->class = 'btn-add-new';
        $btnNew->add('<i class="fa fa-plus"></i> Novo Cliente');
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
            $tabLink->href = "javascript:__adianti_load_page('index.php?class=ClienteList&method=onQuickFilter&filter={$key}')";
            $tabLink->class = 'filter-tab' . ($this->activeFilter === $key ? ' active' : '');
            $tabLink->add("<i class=\"fa {$tab['icon']}\"></i> {$tab['label']}");
            $filterTabs->add($tabLink);
        }
        
        $card->add($filterTabs);
        
        // Search Bar
        $searchBar = new TElement('div');
        $searchBar->class = 'search-bar';
        
        $this->form = new TForm('form_search_cliente');
        
        $searchInput = new TEntry('nome');
        $searchInput->setProperty('placeholder', 'Buscar cliente por nome ou email...');
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
        
        $col_nome = new TDataGridColumn('nome', 'Cliente', 'left');
        $col_email = new TDataGridColumn('email', 'E-mail', 'left');
        $col_ativo = new TDataGridColumn('ativo', 'Status', 'center', 120);
        
        // Transformer for client name with avatar
        $col_nome->setTransformer(function($value, $object) {
            $initials = strtoupper(substr($value, 0, 2));
            return "<div class='item-name'>
                        <div class='item-avatar'>{$initials}</div>
                        <div class='item-details'>
                            <span class='item-title'>{$value}</span>
                            <span class='item-meta'>Login: {$object->login}</span>
                        </div>
                    </div>";
        });
        
        $col_email->setTransformer(function($value) {
            return "<span class='meta-value'><i class='fa fa-envelope'></i> {$value}</span>";
        });
        
        $col_ativo->setTransformer(function($value) {
            if ($value == 1) {
                return '<span class="badge-status badge-success">Ativo</span>';
            } else {
                return '<span class="badge-status badge-inactive">Inativo</span>';
            }
        });
        
        $this->datagrid->addColumn($col_nome);
        $this->datagrid->addColumn($col_email);
        $this->datagrid->addColumn($col_ativo);
        
        // Actions
        $action_edit = new TDataGridAction(['ClienteForm', 'onEdit'], ['id' => '{id}']);
        $action_delete = new TDataGridAction([$this, 'onDelete'], ['id' => '{id}']);
        
        $this->datagrid->addAction($action_edit, 'Editar', 'fa:edit blue');
        
        $action_chat = new TDataGridAction(['SystemChat', 'onLoad'], ['target_id' => '{id}']);
        $this->datagrid->addAction($action_chat, 'Chat', 'fa:comments green');
        
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
        TSession::setValue('ClienteList_quickfilter', $param['filter']);
        TSession::setValue('ClienteList_filter', null);
        TApplication::loadPage('ClienteList');
    }
    
    public function onSearch()
    {
        $data = $this->form->getData();
        TSession::setValue('ClienteList_filter', $data);
        TSession::setValue('ClienteList_quickfilter', 'todos');
        $this->onReload();
    }
    
    public function onReload($param = NULL)
    {
        try {
            TTransaction::open('database');
            
            $criteria = new TCriteria;
            $criteria->add(new TFilter('tipo', '=', 'cliente'));
            
            // Apply quick filter
            $quickFilter = TSession::getValue('ClienteList_quickfilter') ?? 'todos';
            if ($quickFilter === 'ativos') {
                $criteria->add(new TFilter('ativo', '=', 1));
            } elseif ($quickFilter === 'inativos') {
                $criteria->add(new TFilter('ativo', '=', 0));
            }
            
            // Apply search filter
            if ($filter = TSession::getValue('ClienteList_filter')) {
                if (!empty($filter->nome)) {
                    $criteria->add(new TFilter('nome', 'like', "%{$filter->nome}%"));
                }
            }
            
            $criteria->setProperty('limit', 10);
            $criteria->setProperty('offset', isset($param['offset']) ? $param['offset'] : 0);
            $criteria->setProperty('order', 'nome');
            
            $clientes = Usuario::getObjects($criteria);
            
            $this->datagrid->clear();
            if ($clientes) {
                foreach ($clientes as $cliente) {
                    $this->datagrid->addItem($cliente);
                }
            }
            
            // Limpa ordenação para evitar erro de agregados no PostgreSQL
            $criteria->setProperty('limit', NULL);
            $criteria->setProperty('offset', NULL);
            $criteria->setProperty('order', NULL);
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
            $cliente = Usuario::find($param['id']);
            TTransaction::close();
            
            if (!$cliente) {
                return; // Ignora se o objeto já não existir no banco
            }
            
            $action = new TAction([$this, 'Delete']);
            $action->setParameter('id', $param['id']);
            
            new TQuestion('Deseja realmente excluir este cliente?', $action);
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }
    
    public function Delete($param)
    {
        try {
            TTransaction::open('database');
            
            $key = $param['id'];
            $cliente = Usuario::find($key);
            
            if (!$cliente) {
                TTransaction::close();
                return; // Impede erro caso clique duplo tenha submetido o formulário Delete duas vezes
            }
            
            ClienteProjeto::where('cliente_id', '=', $key)->delete();
            
            TTransaction::get()->exec("DELETE FROM mensagem WHERE system_user_to_id = '{$key}'");
            TTransaction::get()->exec("DELETE FROM mensagem WHERE system_user_id = '{$key}'");
            
            Entrega::where('cliente_id', '=', $key)->delete();
            
            $cliente->delete();
            
            TTransaction::close();
            
            $this->onReload();
            new TMessage('info', 'Cliente excluído com sucesso');
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
