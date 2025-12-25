<?php

class EntregaList extends TPage
{
    protected $form;
    protected $datagrid;
    protected $pageNavigation;
    
    public function __construct()
    {
        parent::__construct();

        // AppSecurity::checkAccess('cliente');  // só gestores podem acessar
        
        $this->form = new BootstrapFormBuilder('form_search_entrega');
        $this->form->setFormTitle('Entregas');
        
        $cliente_id = new TDBCombo('cliente_id', 'database', 'Usuario', 'id', 'nome');
        $projeto_id = new TDBCombo('projeto_id', 'database', 'Projeto', 'id', 'nome');
        $mes = new TCombo('mes_referencia');
        $ano = new TEntry('ano_referencia');
        $status = new TCombo('status');
        
        $meses = [
            '1' => 'Janeiro', '2' => 'Fevereiro', '3' => 'Março',
            '4' => 'Abril', '5' => 'Maio', '6' => 'Junho',
            '7' => 'Julho', '8' => 'Agosto', '9' => 'Setembro',
            '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro'
        ];
        
        $mes->addItems($meses);
        $status->addItems([
            'pendente' => 'Pendente',
            'em_analise' => 'Em Análise',
            'aprovado' => 'Aprovado',
            'rejeitado' => 'Rejeitado'
        ]);
        
        $ano->setValue(date('Y'));
        
        $this->form->addFields([new TLabel('Cliente')], [$cliente_id]);
        $this->form->addFields([new TLabel('Projeto')], [$projeto_id]);
        $this->form->addFields([new TLabel('Mês')], [$mes], [new TLabel('Ano')], [$ano]);
        $this->form->addFields([new TLabel('Status')], [$status]);
        
        $btn_search = $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search');
        
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->width = '100%';
        
        $col_id = new TDataGridColumn('id', 'ID', 'center', 50);
        $col_cliente = new TDataGridColumn('cliente_id', 'Cliente', 'left');
        $col_projeto = new TDataGridColumn('projeto_id', 'Projeto', 'left');
        $col_mes = new TDataGridColumn('mes_referencia', 'Mês/Ano', 'center');
        $col_status = new TDataGridColumn('status', 'Status', 'center');
        $col_data = new TDataGridColumn('data_entrega', 'Data Entrega', 'center');
        
        $col_cliente->setTransformer(function($value) {
            $cliente = new Usuario($value);
            return $cliente->nome;
        });
        
        $col_projeto->setTransformer(function($value) {
            $projeto = new Projeto($value);
            return $projeto->nome;
        });
        
        $col_mes->setTransformer(function($value, $object) {
            return str_pad($value, 2, '0', STR_PAD_LEFT) . '/' . $object->ano_referencia;
        });
        
        $col_data->setTransformer(function($value) {
            return $value ? date('d/m/Y H:i', strtotime($value)) : '-';
        });
        
        $this->datagrid->addColumn($col_id);
        $this->datagrid->addColumn($col_cliente);
        $this->datagrid->addColumn($col_projeto);
        $this->datagrid->addColumn($col_mes);
        $this->datagrid->addColumn($col_status);
        $this->datagrid->addColumn($col_data);
        
        $action_validar = new TDataGridAction(['EntregaValidacao', 'onView'], ['id' => '{id}']);
        $this->datagrid->addAction($action_validar, 'Validar', 'fa:check green');
        
        // Ação Consolidar (Apenas para Gestor e Status Aprovado)
        $action_consolidar = new TDataGridAction(['ConsolidarEntrega', 'onConsolidar'], ['id' => '{id}']);
        $action_consolidar->setDisplayCondition(function($object) {
            return ($object->status == 'aprovado');
        });
        $this->datagrid->addAction($action_consolidar, 'Consolidar', 'fa:file-pdf-o orange');
        
        $this->datagrid->createModel();
        
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction([$this, 'onReload']));
        
        $panel = new TPanelGroup;
        $panel->add($this->datagrid);
        $panel->addFooter($this->pageNavigation);
        
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(new TXMLBreadCrumb('menu-cliente.xml', __CLASS__));
        $container->add($this->form);
        $container->add($panel);
        
        parent::add($container);
    }
    
    public function onSearch()
    {
        $data = $this->form->getData();
        TSession::setValue('EntregaList_filter', $data);
        $this->onReload();
    }
    
    public function onReload($param = NULL)
    {
        try {
            TTransaction::open('database');
            
            $criteria = new TCriteria;
            
            // Se for cliente, filtra apenas suas entregas
            if (TSession::getValue('usertype') != 'gestor') {
                $criteria->add(new TFilter('cliente_id', '=', TSession::getValue('userid')));
            }
            
            if ($filter = TSession::getValue('EntregaList_filter')) {
                if ($filter->cliente_id) {
                    $criteria->add(new TFilter('cliente_id', '=', $filter->cliente_id));
                }
                if ($filter->projeto_id) {
                    $criteria->add(new TFilter('projeto_id', '=', $filter->projeto_id));
                }
                if ($filter->mes_referencia) {
                    $criteria->add(new TFilter('mes_referencia', '=', $filter->mes_referencia));
                }
                if ($filter->ano_referencia) {
                    $criteria->add(new TFilter('ano_referencia', '=', $filter->ano_referencia));
                }
                if ($filter->status) {
                    $criteria->add(new TFilter('status', '=', $filter->status));
                }
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
