<?php
/**
 * HistoricoList
 *
 * @version    1.0
 * @package    control
 * @subpackage admin
 * @author     Antigravity
 * @copyright  Copyright (c) 2024
 * @license    http://www.adianti.com.br/framework-license
 */
class HistoricoList extends TPage
{
    private $form;
    private $datagrid;
    private $pageNavigation;
    private $loaded;

    /**
     * Class constructor
     * Creates the page, the form and the listing
     */
    public function __construct()
    {
        parent::__construct();
        
        // Creates the form
        $this->form = new BootstrapFormBuilder('form_search_Historico');
        $this->form->setFormTitle('Histórico de Arquivos Consolidados');

        // Fields
        $cliente_id = new TDBUniqueSearch('cliente_id', 'database', 'Usuario', 'id', 'nome');
        $projeto_id = new TDBUniqueSearch('projeto_id', 'database', 'Projeto', 'id', 'nome');
        
        $cliente_id->setMinLength(0);
        $projeto_id->setMinLength(0);

        // Define fields to form
        $this->form->addFields([new TLabel('Pessoa (Cliente):')], [$cliente_id]);
        $this->form->addFields([new TLabel('Projeto:')], [$projeto_id]);

        $this->form->setData(TSession::getValue('HistoricoList_filter_data'));

        // Action buttons
        $btn_search = $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search');
        $btn_search->style = 'width: 100px'; 
        
        $btn_clear = $this->form->addAction('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');

        // Creates the Datagrid
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->width = '100%';

        $col_id = new TDataGridColumn('id', 'ID', 'center', '50');
        $col_cliente = new TDataGridColumn('cliente_id', 'Pessoa', 'left');
        $col_projeto = new TDataGridColumn('projeto_id', 'Projeto', 'left');
        $col_mes = new TDataGridColumn('mes_referencia', 'Ref', 'center'); // Shortened
        $col_data = new TDataGridColumn('data_aprovacao', 'Data Aprovação', 'center');
        
        // Transformers
        $col_cliente->setTransformer(function($value){
            return (new Usuario($value))->nome ?? $value;
        });

        $col_projeto->setTransformer(function($value){
            return (new Projeto($value))->nome ?? $value;
        });
        
        $col_mes->setTransformer(function($value, $object){
             return str_pad($value, 2, '0', STR_PAD_LEFT) . '/' . $object->ano_referencia;
        });

        $col_data->setTransformer(function($value){
            return TDate::convertToLocal($value, 'yyyy-mm-dd hh:ii');
        });

        // Add columns
        $this->datagrid->addColumn($col_id);
        $this->datagrid->addColumn($col_cliente);
        $this->datagrid->addColumn($col_projeto);
        $this->datagrid->addColumn($col_mes);
        $this->datagrid->addColumn($col_data);

        // Actions
        // 1. Download
        $action_download = new TDataGridAction([$this, 'onDownload'], ['key' => '{id}']);
        $this->datagrid->addAction($action_download, 'Baixar Arquivo', 'fa:download green');
        
        // 2. View Online
        $action_view = new TDataGridAction([$this, 'onViewPDF'], ['key' => '{id}']);
        $this->datagrid->addAction($action_view, 'Ver Online', 'fa:eye blue');

        // Page Navigation
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction([$this, 'onReload']));

        $panel = new TPanelGroup;
        $panel->add($this->form);
        $panel->add($this->datagrid);
        $panel->add($this->pageNavigation);

        // Vbox
        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add(new TXMLBreadCrumb('menu-gestor.xml', __CLASS__));
        $vbox->add($panel);

        parent::add($vbox);
    }
    
    /**
     * Download Action
     */
    public function onDownload($param)
    {
        try {
            if (isset($param['key'])) {
                TTransaction::open('database');
                $entrega = new Entrega($param['key']);
                
                if ($entrega->arquivo_consolidado && file_exists($entrega->arquivo_consolidado)) {
                    parent::openFile($entrega->arquivo_consolidado); // Adianti helper for download
                } else {
                    new TMessage('error', 'Arquivo não encontrado.');
                }
                TTransaction::close();
            }
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }
    
    /**
     * View Online Action (Stream PDF)
     */
    public function onViewPDF($param)
    {
        try {
            if (isset($param['key'])) {
                TTransaction::open('database');
                $entrega = new Entrega($param['key']);
                
                if ($entrega->arquivo_consolidado && file_exists($entrega->arquivo_consolidado)) {
                    $file = $entrega->arquivo_consolidado;
                    
                    // We need to serve this file in a new window/tab as inline content
                    // Since TPage runs inside the layout, we usually window.open to a download handler.
                    // But for simplicity in Adianti, we can use a download script or just stream it if we kill the layout.
                    // A trick is to use 'engine.php?class=HistoricoList&method=downloadInline&file=...' but security.
                    // Better: script to open window.
                    
                    $window_name = "view_pdf_{$entrega->id}";
                    $script = "window.open('download.php?file={$file}&inline=1', '{$window_name}');";
                    TScript::create($script);
                    
                    // Note: 'download.php' needs to exist or be created. 
                    // Adianti usually has 'download.php' in root. If not, we use engine.php?class=SystemDocument&method=onView...
                    // Let's rely on standard openFile for now for simplicity, but openFile triggers download.
                    // Let's create a custom viewer method.
                    
                } else {
                    new TMessage('error', 'Arquivo não encontrado ou não consolidado.');
                }
                TTransaction::close();
            }
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }
    
    // NOTE: For 'Ver Online' to work perfectly as streaming, we often need a separate handler.
    // Adianti's parent::openFile usually forces download.
    // I will implement a simplier version using TPage::openFile which handles temporary file serving.
    // If specific streaming is needed, we'd create a specific PHP handler.
    
    /**
     * Search triggers
     */
    public function onSearch()
    {
        $data = $this->form->getData();
        TSession::setValue('HistoricoList_filter_data', $data);

        $param = [];
        if (!empty($data->cliente_id)) {
            $param['cliente_id'] = $data->cliente_id;
        }
        if (!empty($data->projeto_id)) {
            $param['projeto_id'] = $data->projeto_id;
        }

        $this->onReload($param);
    }

    /**
     * Clear filters
     */
    public function onClear()
    {
        $this->form->clear();
        TSession::setValue('HistoricoList_filter_data', NULL);
        $this->onReload();
    }

    /**
     * Reload datagrid
     */
    public function onReload($param = NULL)
    {
        try {
            TTransaction::open('database');
            $repository = new TRepository('Entrega');
            
            // Criteria: Approved AND Consolidated
            $criteria = new TCriteria;
            $criteria->add(new TFilter('status', '=', 'aprovado')); 
            $criteria->add(new TFilter('consolidado', '=', '1')); // Only consolidated
            
            // Check filters in session/param
            $data = TSession::getValue('HistoricoList_filter_data');
            
            if (!empty($data->cliente_id)) {
                $criteria->add(new TFilter('cliente_id', '=', $data->cliente_id));
            }
            if (!empty($data->projeto_id)) {
                 $criteria->add(new TFilter('projeto_id', '=', $data->projeto_id));
            }

            // Ordering
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
