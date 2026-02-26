<?php
class DashboardGestor extends TPage
{
    private $datagrid;

    public function __construct()
    {
        parent::__construct();

        try {
            TTransaction::open('database');

            $mes_atual = date('n');
            $ano_atual = date('Y');

            // ============================
            // CONTADORES PRINCIPAIS
            // ============================
            $total_clientes = Usuario::where('tipo', '=', 'cliente')
                                     ->where('ativo', '=', 1)
                                     ->count();

            $total_projetos = Projeto::where('ativo', '=', 1)->count();

            $entregas_pendentes = Entrega::where('status', '=', 'pendente')
                                         ->count();

            $entregas_aprovadas = Entrega::where('mes_referencia', '=', $mes_atual)
                                         ->where('ano_referencia', '=', $ano_atual)
                                         ->where('status', '=', 'aprovado')
                                         ->count();

            $entregas_em_analise = Entrega::where('status', '=', 'em_analise')
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
            $entregas_pendentes_list = Entrega::where('status', '=', 'pendente')->load();

            if ($entregas_pendentes_list) {
                foreach ($entregas_pendentes_list as $entrega) {
                    $projeto = new Projeto($entrega->projeto_id);
                    $vencimento_dia = (int) $projeto->dia_vencimento;

                    if ($vencimento_dia < 1 || $vencimento_dia > 31) {
                        $vencimento_dia = 1;
                    }

                    $ano_ref = $entrega->ano_referencia ?: date('Y');
                    $mes_ref = $entrega->mes_referencia ?: date('m');

                    $data_vencimento = new DateTime(sprintf('%04d-%02d-%02d', $ano_ref, $mes_ref, $vencimento_dia));
                    $hoje = new DateTime(date('Y-m-d'));

                    if ($hoje > $data_vencimento) {
                        $entregas_atrasadas++;
                    }
                }
            }

            // ============================
            // TAXA DE APROVACAO
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
                'total_clientes'      => $total_clientes,
                'total_projetos'      => $total_projetos,
                'entregas_pendentes'  => $entregas_pendentes,
                'entregas_aprovadas'  => $entregas_aprovadas,
                'entregas_em_analise' => $entregas_em_analise,
                'entregas_rejeitadas' => $entregas_rejeitadas,
                'total_entregas'      => $total_entregas,
                'entregas_atrasadas'  => $entregas_atrasadas,
                'taxa_aprovacao'      => $taxa_aprovacao
            ];

            $html->enableSection('main', $replacements);

            // ============================
            // DATAGRID: ENTREGAS EM ANALISE
            // ============================
            $panel_entregas = new TPanelGroup('Entregas Aguardando Validacao');

            $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
            $this->datagrid->width = '100%';

            $col_id = new TDataGridColumn('id', 'ID', 'center', 50);
            $col_cliente = new TDataGridColumn('cliente_id', 'Cliente', 'left');
            $col_projeto = new TDataGridColumn('projeto_id', 'Projeto', 'left');
            $col_mes = new TDataGridColumn('mes_referencia', 'Mes/Ano', 'center');
            $col_status = new TDataGridColumn('status', 'Status', 'center');
            $col_data = new TDataGridColumn('data_entrega', 'Data Entrega', 'center');

            $col_cliente->setTransformer(function ($value) {
                $cliente = new Usuario($value);
                return $cliente->nome ?? '-';
            });

            $col_projeto->setTransformer(function ($value) {
                $projeto = new Projeto($value);
                return $projeto->nome ?? '-';
            });

            $col_mes->setTransformer(function ($value, $object) {
                return str_pad($value, 2, '0', STR_PAD_LEFT) . '/' . $object->ano_referencia;
            });

            $col_data->setTransformer(function ($value) {
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

            $this->datagrid->createModel();

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

            $panel_entregas->add($this->datagrid);

            // ============================
            // CALENDARIO: ENTREGAS DOS CLIENTES DO GESTOR
            // ============================
            $calendar = new TFullCalendar(date('Y-m-d'), 'month');
            $calendar->setTimeRange('00:00', '23:59');
            $calendar->enableFullHeight();
            $calendar->enablePopover(
                'Detalhes da Entrega',
                '<b>Cliente:</b> {cliente_nome}<br><b>Projeto:</b> {projeto_nome}<br><b>Status:</b> {status}<br><b>Data:</b> {data_entrega}'
            );

            $gestor_id = (int) TSession::getValue('userid');
            $cliente_ids = [];

            if ($gestor_id > 0) {
                $clientes_gestor = Usuario::where('tipo', '=', 'cliente')
                                          ->where('gestor_id', '=', $gestor_id)
                                          ->where('ativo', '=', '1')
                                          ->load();

                if ($clientes_gestor) {
                    foreach ($clientes_gestor as $cliente_gestor) {
                        $cliente_ids[] = (int) $cliente_gestor->id;
                    }
                }
            }

            if (!empty($cliente_ids)) {
                $criteria_calendar = new TCriteria;
                $criteria_calendar->add(new TFilter('cliente_id', 'IN', $cliente_ids));
                $criteria_calendar->setProperty('order', 'data_entrega desc');

                $repository_calendar = new TRepository('Entrega');
                $entregas_calendar = $repository_calendar->load($criteria_calendar);

                if ($entregas_calendar) {
                    foreach ($entregas_calendar as $entrega_calendar) {
                        if (empty($entrega_calendar->data_entrega)) {
                            continue;
                        }

                        $timestamp = strtotime($entrega_calendar->data_entrega);
                        if ($timestamp === false) {
                            continue;
                        }

                        $cliente = new Usuario($entrega_calendar->cliente_id);
                        $projeto = new Projeto($entrega_calendar->projeto_id);

                        $evento = (object) [
                            'cliente_nome' => $cliente->nome ?? 'Cliente nao identificado',
                            'projeto_nome' => $projeto->nome ?? 'Projeto nao identificado',
                            'status' => ucfirst(str_replace('_', ' ', (string) $entrega_calendar->status)),
                            'data_entrega' => date('d/m/Y H:i', $timestamp)
                        ];

                        $titulo = ($evento->cliente_nome ?? 'Cliente') . ' - ' . ($evento->projeto_nome ?? 'Projeto');

                        $calendar->addEvent(
                            'entrega_' . $entrega_calendar->id,
                            $titulo,
                            date('Y-m-d', $timestamp),
                            null,
                            null,
                            $this->getCalendarColor($entrega_calendar->status),
                            $evento
                        );
                    }
                }
            }

            $panel_calendar = new TPanelGroup('Calendario de Entregas');
            $panel_calendar->add($calendar);

            // ============================
            // CONTAINER PRINCIPAL
            // ============================
            $container = new TVBox;
            $container->style = 'width: 100%';
            $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
            $container->add($html);
            $container->add($panel_calendar);
            $container->add($panel_entregas);

            TTransaction::close();
            parent::add($container);
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    private function getCalendarColor($status)
    {
        if ($status === 'aprovado') {
            return '#00a65a';
        }

        if ($status === 'rejeitado') {
            return '#dd4b39';
        }

        if ($status === 'em_analise') {
            return '#f39c12';
        }

        if ($status === 'pendente') {
            return '#3c8dbc';
        }

        return '#605ca8';
    }
}

