<?php
/**
 * HistoricoList - Modernized
 */
class HistoricoList extends TPage
{
    private $form;
    private $datagrid;
    private $pageNavigation;
    private $loaded;

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
        $headerLeft->add('<h1 class="page-title"><i class="fa fa-history"></i> Histórico de Arquivos</h1>');
        
        $pageHeader->add($headerLeft);
        $html->add($pageHeader);
        
        // Main Card
        $card = new TElement('div');
        $card->class = 'list-card';
        
        // Info Header
        $infoHeader = new TElement('div');
        $infoHeader->class = 'list-info-header';
        $infoHeader->add('<div class="info-text"><i class="fa fa-check-circle"></i> Exibindo apenas entregas <strong>aprovadas e consolidadas</strong></div>');
        $card->add($infoHeader);
        
        // Search Bar
        $searchBar = new TElement('div');
        $searchBar->class = 'search-bar';
        
        $this->form = new TForm('form_search_historico');
        
        $cliente_id = new TDBUniqueSearch('cliente_id', 'database', 'Usuario', 'id', 'nome');
        $cliente_id->setMinLength(0);
        $cliente_id->setProperty('placeholder', 'Filtrar por cliente...');
        $cliente_id->setSize('100%');
        
        $projeto_id = new TDBUniqueSearch('projeto_id', 'database', 'Projeto', 'id', 'nome');
        $projeto_id->setMinLength(0);
        $projeto_id->setProperty('placeholder', 'Filtrar por projeto...');
        $projeto_id->setSize('100%');
        
        $filterRow = new TElement('div');
        $filterRow->class = 'filter-row';
        
        $clienteWrapper = new TElement('div');
        $clienteWrapper->class = 'filter-field';
        $clienteLabel = new TElement('label');
        $clienteLabel->add('Cliente');
        $clienteWrapper->add($clienteLabel);
        $clienteWrapper->add($cliente_id);
        
        $projetoWrapper = new TElement('div');
        $projetoWrapper->class = 'filter-field';
        $projetoLabel = new TElement('label');
        $projetoLabel->add('Projeto');
        $projetoWrapper->add($projetoLabel);
        $projetoWrapper->add($projeto_id);
        
        $btnWrapper = new TElement('div');
        $btnWrapper->class = 'filter-buttons';
        
        $btnSearch = new TButton('btn_search');
        $btnSearch->setAction(new TAction([$this, 'onSearch']), 'Buscar');
        $btnSearch->class = 'btn-search';
        
        $btnClear = new TButton('btn_clear');
        $btnClear->setAction(new TAction([$this, 'onClear']), 'Limpar');
        $btnClear->class = 'btn-clear';
        
        $btnWrapper->add($btnSearch);
        $btnWrapper->add($btnClear);
        
        $filterRow->add($clienteWrapper);
        $filterRow->add($projetoWrapper);
        $filterRow->add($btnWrapper);
        
        $this->form->add($filterRow);
        $this->form->setFields([$cliente_id, $projeto_id, $btnSearch, $btnClear]);
        
        $searchBar->add($this->form);
        $card->add($searchBar);
        
        // Restore form data
        $this->form->setData(TSession::getValue('HistoricoList_filter_data'));

        // DataGrid
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->width = '100%';
        $this->datagrid->class = 'modern-datagrid';

        $col_cliente = new TDataGridColumn('cliente_id', 'Arquivo', 'left');
        $col_mes = new TDataGridColumn('mes_referencia', 'Referência', 'center', 120);
        $col_data = new TDataGridColumn('data_aprovacao', 'Data Aprovação', 'center', 150);
        
        // Transformers
        $col_cliente->setTransformer(function($value, $object) {
            try {
                TTransaction::open('database');
                $cliente = new Usuario($value);
                $projeto = new Projeto($object->projeto_id);
                TTransaction::close();
                
                $clienteNome = $cliente->nome ?? 'Cliente';
                $projetoNome = $projeto->nome ?? 'Projeto';
                
                return "<div class='item-name'>
                            <div class='item-icon item-icon-success'><i class='fa fa-file-pdf-o'></i></div>
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
                $formatted = date('d/m/Y H:i', strtotime($value));
                return "<span class='meta-value'><i class='fa fa-check'></i> {$formatted}</span>";
            }
            return '-';
        });

        $this->datagrid->addColumn($col_cliente);
        $this->datagrid->addColumn($col_mes);
        $this->datagrid->addColumn($col_data);

        // Actions
        $action_download = new TDataGridAction([$this, 'onDownload'], ['key' => '{id}']);
        $this->datagrid->addAction($action_download, 'Baixar', 'fa:download green');
        
        $action_view = new TDataGridAction([$this, 'onViewPDF'], ['key' => '{id}']);
        $this->datagrid->addAction($action_view, 'Ver Online', 'fa:eye blue');

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
    
    public function onDownload($param)
    {
        try {
            if (isset($param['key'])) {
                TTransaction::open('database');
                $entrega = new Entrega($param['key']);
                
                if ($entrega->arquivo_consolidado && file_exists($entrega->arquivo_consolidado)) {
                    parent::openFile($entrega->arquivo_consolidado);
                } else {
                    new TMessage('error', 'Arquivo não encontrado.');
                }
                TTransaction::close();
            }
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }
    
    public function onViewPDF($param)
    {
        try {
            if (isset($param['key'])) {
                TTransaction::open('database');
                $entrega = new Entrega($param['key']);
                
                if ($entrega->arquivo_consolidado && file_exists($entrega->arquivo_consolidado)) {
                    $file = $entrega->arquivo_consolidado;
                    $window_name = "view_pdf_{$entrega->id}";
                    $script = "window.open('download.php?file={$file}&inline=1', '{$window_name}');";
                    TScript::create($script);
                } else {
                    new TMessage('error', 'Arquivo não encontrado ou não consolidado.');
                }
                TTransaction::close();
            }
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }
    
    public function onSearch()
    {
        $data = $this->form->getData();
        TSession::setValue('HistoricoList_filter_data', $data);
        $this->onReload();
    }

    public function onClear()
    {
        $this->form->clear();
        TSession::setValue('HistoricoList_filter_data', NULL);
        $this->onReload();
    }

    public function onReload($param = NULL)
    {
        try {
            TTransaction::open('database');
            $repository = new TRepository('Entrega');
            
            $criteria = new TCriteria;
            $criteria->add(new TFilter('status', '=', 'aprovado')); 
            $criteria->add(new TFilter('consolidado', '=', '1'));
            
            $data = TSession::getValue('HistoricoList_filter_data');
            
            if (!empty($data->cliente_id)) {
                $criteria->add(new TFilter('cliente_id', '=', $data->cliente_id));
            }
            if (!empty($data->projeto_id)) {
                $criteria->add(new TFilter('projeto_id', '=', $data->projeto_id));
            }

            $order = isset($param['order']) ? $param['order'] : 'data_aprovacao';
            $direction = isset($param['direction']) ? $param['direction'] : 'desc';
            
            $criteria->setProperties([
                'order' => $order,
                'direction' => $direction,
                'limit' => 10,
                'offset' => isset($param['offset']) ? $param['offset'] : 0
            ]);

            $objects = $repository->load($criteria, FALSE);
            
            $this->datagrid->clear();
            if ($objects) {
                foreach ($objects as $object) {
                    $this->datagrid->addItem($object);
                }
            }

            $criteria->resetProperties();
            $count = $repository->count($criteria);

            $this->pageNavigation->setCount($count);
            $this->pageNavigation->setProperties($param);
            $this->pageNavigation->setLimit(10);

            TTransaction::close();
            $this->loaded = true;
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    public function show()
    {
        if (!$this->loaded) {
            $this->onReload();
        }
        parent::show();
    }
}
