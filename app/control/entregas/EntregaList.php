<?php

class EntregaList extends TPage
{
    protected $form;
    protected $datagrid;
    protected $pageNavigation;
    private $activeFilter;
    
    public function __construct()
    {
        parent::__construct();
        
        $this->activeFilter = TSession::getValue('EntregaList_quickfilter') ?? 'todos';
        
        // Build the page HTML structure
        $html = new TElement('div');
        $html->class = 'list-page-container';
        
        // Page Header
        $pageHeader = new TElement('div');
        $pageHeader->class = 'list-page-header';
        
        $headerLeft = new TElement('div');
        $headerLeft->class = 'header-left';
        $headerLeft->add('<h1 class="page-title"><i class="fa fa-file-text"></i> Entregas</h1>');
        
        $pageHeader->add($headerLeft);
        $html->add($pageHeader);
        
        // Main Card
        $card = new TElement('div');
        $card->class = 'list-card';
        
        // Quick Filter Tabs
        $filterTabs = new TElement('div');
        $filterTabs->class = 'quick-filter-tabs';
        
        $tabs = [
            'todos' => ['label' => 'Todas', 'icon' => 'fa-list'],
            'pendente' => ['label' => 'Pendentes', 'icon' => 'fa-clock-o'],
            'em_analise' => ['label' => 'Em Análise', 'icon' => 'fa-search'],
            'aprovado' => ['label' => 'Aprovadas', 'icon' => 'fa-check-circle'],
            'rejeitado' => ['label' => 'Rejeitadas', 'icon' => 'fa-times-circle']
        ];
        
        foreach ($tabs as $key => $tab) {
            $tabLink = new TElement('a');
            $tabLink->href = "javascript:__adianti_load_page('index.php?class=EntregaList&method=onQuickFilter&filter={$key}')";
            $tabLink->class = 'filter-tab' . ($this->activeFilter === $key ? ' active' : '');
            $tabLink->add("<i class=\"fa {$tab['icon']}\"></i> {$tab['label']}");
            $filterTabs->add($tabLink);
        }
        
        $card->add($filterTabs);
        
        // Search Bar
        $searchBar = new TElement('div');
        $searchBar->class = 'search-bar';
        
        $this->form = new TForm('form_search_entrega');
        
        $searchInput = new TEntry('search_term');
        $searchInput->setProperty('placeholder', 'Buscar por cliente ou projeto...');
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
        
        $col_cliente = new TDataGridColumn('cliente_id', 'Entrega', 'left');
        $col_mes = new TDataGridColumn('mes_referencia', 'Referência', 'center', 120);
        $col_status = new TDataGridColumn('status', 'Status', 'center', 140);
        $col_data = new TDataGridColumn('data_entrega', 'Data', 'center', 150);
        
        // Transformer for client/project info
        $col_cliente->setTransformer(function($value, $object) {
            try {
                TTransaction::open('database');
                $cliente = new Usuario($value);
                $projeto = new Projeto($object->projeto_id);
                TTransaction::close();
                
                $clienteNome = $cliente->nome ?? 'Cliente';
                $projetoNome = $projeto->nome ?? 'Projeto';
                $initials = strtoupper(substr($clienteNome, 0, 2));
                
                return "<div class='item-name'>
                            <div class='item-avatar'>{$initials}</div>
                            <div class='item-details'>
                                <span class='item-title'>{$clienteNome}</span>
                                <span class='item-meta'>{$projetoNome}</span>
                            </div>
                        </div>";
            } catch (Exception $e) {
                return $value;
            }
        });
        
        $col_mes->setTransformer(function($value, $object) {
            $mesAno = str_pad($value, 2, '0', STR_PAD_LEFT) . '/' . $object->ano_referencia;
            return "<span class='meta-value'><i class='fa fa-calendar'></i> {$mesAno}</span>";
        });
        
        $col_data->setTransformer(function($value) {
            if ($value) {
                $formatted = date('d/m/Y', strtotime($value));
                return "<span class='meta-value'><i class='fa fa-clock-o'></i> {$formatted}</span>";
            }
            return "<span class='meta-value text-muted'>-</span>";
        });
        
        $col_status->setTransformer(function($value) {
            $badges = [
                'pendente' => '<span class="badge-status badge-pending">Pendente</span>',
                'em_analise' => '<span class="badge-status badge-analysis">Em Análise</span>',
                'aprovado' => '<span class="badge-status badge-approved">Aprovado</span>',
                'rejeitado' => '<span class="badge-status badge-rejected">Rejeitado</span>'
            ];
            return $badges[$value] ?? '<span class="badge-status badge-inactive">' . ucfirst($value) . '</span>';
        });
        
        $this->datagrid->addColumn($col_cliente);
        $this->datagrid->addColumn($col_mes);
        $this->datagrid->addColumn($col_status);
        $this->datagrid->addColumn($col_data);
        
        // Actions
        $action_validar = new TDataGridAction(['EntregaValidacao', 'onView'], ['id' => '{id}']);
        $this->datagrid->addAction($action_validar, 'Validar', 'fa:check green');
        
        $action_consolidar = new TDataGridAction(['ConsolidarEntrega', 'onConsolidar'], ['id' => '{id}']);
        $action_consolidar->setDisplayCondition(function($object) {
            return ($object->status == 'aprovado');
        });
        $this->datagrid->addAction($action_consolidar, 'Consolidar', 'fa:file-pdf-o orange');
        
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
        TSession::setValue('EntregaList_quickfilter', $param['filter']);
        TSession::setValue('EntregaList_filter', null);
        TApplication::loadPage('EntregaList');
    }
    
    public function onSearch()
    {
        $data = $this->form->getData();
        TSession::setValue('EntregaList_filter', $data);
        TSession::setValue('EntregaList_quickfilter', 'todos');
        $this->onReload();
    }
    
    public function onReload($param = NULL)
    {
        try {
            TTransaction::open('database');
            
            $criteria = new TCriteria;
            
            // Client filter (non-gestor)
            if (TSession::getValue('usertype') != 'gestor') {
                $criteria->add(new TFilter('cliente_id', '=', TSession::getValue('userid')));
            }
            
            // Quick filter
            $quickFilter = TSession::getValue('EntregaList_quickfilter') ?? 'todos';
            if ($quickFilter !== 'todos') {
                $criteria->add(new TFilter('status', '=', $quickFilter));
            }
            
            $criteria->setProperty('limit', 10);
            $criteria->setProperty('offset', isset($param['offset']) ? $param['offset'] : 0);
            $criteria->setProperty('order', 'data_entrega');
            $criteria->setProperty('direction', 'desc');
            
            $entregas = Entrega::getObjects($criteria);
            
            $this->datagrid->clear();
            if ($entregas) {
                foreach ($entregas as $entrega) {
                    $this->datagrid->addItem($entrega);
                }
            }
            
            $count = Entrega::countObjects($criteria);
            $this->pageNavigation->setCount($count);
            $this->pageNavigation->setProperties($param);
            
            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }
    
    public function show()
    {
        $this->onReload();
        parent::show();
    }
}
