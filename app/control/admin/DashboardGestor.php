<?php
class DashboardGestor extends TPage
{
    private $datagrid;

    public function __construct()
    {
        parent::__construct();

        // AppSecurity::checkAccess('gestor');  // só gestores podem acessar

        try {
            TTransaction::open('database');

            $mes_atual = date('n');
            $ano_atual = date('Y');
            $dia_atual = date('j');

            // ============================
            // CONTADORES PRINCIPAIS
            // ============================
            $total_clientes = Usuario::where('tipo', '=', 'cliente')
                                    ->where('ativo', '=', 1)
                                    ->count();

            $total_projetos = Projeto::where('ativo', '=', 1)->count();

            $entregas_pendentes = Entrega::where('mes_referencia', '=', $mes_atual)
                                        ->where('ano_referencia', '=', $ano_atual)
                                        ->where('status', '=', 'pendente')
                                        ->count();

            $entregas_aprovadas = Entrega::where('mes_referencia', '=', $mes_atual)
                                        ->where('ano_referencia', '=', $ano_atual)
                                        ->where('status', '=', 'aprovado')
                                        ->count();

            $entregas_em_analise = Entrega::where('mes_referencia', '=', $mes_atual)
                                         ->where('ano_referencia', '=', $ano_atual)
                                         ->where('status', '=', 'em_analise')
                                         ->count();

            $entregas_rejeitadas = Entrega::where('mes_referencia', '=', $mes_atual)
                                         ->where('ano_referencia', '=', $ano_atual)
                                         ->where('status', '=', 'rejeitado')
                                         ->count();

            $total_entregas = Entrega::where('mes_referencia', '=', $mes_atual)
                                    ->where('ano_referencia', '=', $ano_atual)
                                    ->count();

            // ============================
            // CALCULA ENTREGAS ATRASADAS
            // ============================
            $entregas_atrasadas = 0;
            $entregas_pendentes_list = Entrega::where('mes_referencia', '=', $mes_atual)
                                             ->where('ano_referencia', '=', $ano_atual)
                                             ->where('status', '=', 'pendente')
                                             ->load();

            if ($entregas_pendentes_list) {
                foreach ($entregas_pendentes_list as $entrega) {
                    $projeto = new Projeto($entrega->projeto_id);
                    if ($dia_atual > $projeto->dia_vencimento) {
                        $entregas_atrasadas++;
                    }
                }
            }

            // ============================
            // TAXA DE APROVAÇÃO
            // ============================
            $taxa_aprovacao = 0;
            if ($total_entregas > 0) {
                $taxa_aprovacao = round(($entregas_aprovadas / $total_entregas) * 100, 1);
            }

            // ============================
            // RENDERIZA HTML
            // ============================
            $html = new THtmlRenderer('app/resources/dashboard_gestor.html');

            $replacements = [
                'total_clientes'       => $total_clientes,
                'total_projetos'       => $total_projetos,
                'entregas_pendentes'   => $entregas_pendentes,
                'entregas_aprovadas'   => $entregas_aprovadas,
                'entregas_em_analise'  => $entregas_em_analise,
                'entregas_rejeitadas'  => $entregas_rejeitadas,
                'total_entregas'       => $total_entregas,
                'entregas_atrasadas'   => $entregas_atrasadas,
                'taxa_aprovacao'       => $taxa_aprovacao
            ];

            $html->enableSection('main', $replacements);

            // ============================
            // DATAGRID: ENTREGAS EM ANÁLISE
            // ============================
            $panel_entregas = new TPanelGroup('Entregas Aguardando Validação');

            $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
            $this->datagrid->width = '100%';

            // Colunas
            $col_id       = new TDataGridColumn('id', 'ID', 'center', 50);
            $col_cliente  = new TDataGridColumn('cliente_id', 'Cliente', 'left');
            $col_projeto  = new TDataGridColumn('projeto_id', 'Projeto', 'left');
            $col_mes      = new TDataGridColumn('mes_referencia', 'Mês/Ano', 'center');
            $col_status   = new TDataGridColumn('status', 'Status', 'center');
            $col_data     = new TDataGridColumn('data_entrega', 'Data Entrega', 'center');

            // Transformações
            $col_cliente->setTransformer(function($value) {
                $cliente = new Usuario($value);
                return $cliente->nome ?? '—';
            });

            $col_projeto->setTransformer(function($value) {
                $projeto = new Projeto($value);
                return $projeto->nome ?? '—';
            });

            $col_mes->setTransformer(function($value, $object) {
                return str_pad($value, 2, '0', STR_PAD_LEFT) . '/' . $object->ano_referencia;
            });

            $col_data->setTransformer(function($value) {
                return $value ? date('d/m/Y H:i', strtotime($value)) : '-';
            });

            // Adiciona colunas
            $this->datagrid->addColumn($col_id);
            $this->datagrid->addColumn($col_cliente);
            $this->datagrid->addColumn($col_projeto);
            $this->datagrid->addColumn($col_mes);
            $this->datagrid->addColumn($col_status);
            $this->datagrid->addColumn($col_data);

            // Ação
            $action_validar = new TDataGridAction(['EntregaValidacao', 'onView'], ['id' => '{id}']);
            $this->datagrid->addAction($action_validar, 'Validar', 'fa:check green');

            // Cria modelo antes de carregar dados
            $this->datagrid->createModel();

            // ============================
            // CONSULTA DE DADOS
            // ============================
            $criteria = new TCriteria;
            $criteria->add(new TFilter('status', '=', 'em_analise'));
            $criteria->setProperty('order', 'data_entrega desc');
            $criteria->setProperty('limit', 10);

            $repository = new TRepository('Entrega');
            $entregas = $repository->load($criteria);

            $this->datagrid->clear();
            if ($entregas) {
                foreach ($entregas as $entrega) {
                    $this->datagrid->addItem($entrega);
                }
            }

            // ============================
            // MONTA O PAINEL
            // ============================
            $panel_entregas->add($this->datagrid);

            // Fecha transação
            TTransaction::close();

            // ============================
            // CONTAINER PRINCIPAL
            // ============================
            $container = new TVBox;
            $container->style = 'width: 100%';
            $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
            $container->add($html);
            $container->add($panel_entregas);

            parent::add($container);

        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
}
