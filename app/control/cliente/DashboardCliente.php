<?php

class DashboardCliente extends TPage
{

    private $datagrid;

    public function __construct()
    {
        parent::__construct();

        // AppSecurity::checkAccess('cliente');  // só gestores podem acessar

        try {
            TTransaction::open('database');
            
            $cliente_id = TSession::getValue('userid');
            $mes_atual = date('n');
            $ano_atual = date('Y');
            
            // Cards de resumo
            $vinculos = ClienteProjeto::where('cliente_id', '=', $cliente_id)->load();
            $total_projetos = count($vinculos);
            
            $entregas_pendentes = Entrega::where('cliente_id', '=', $cliente_id)
                                        ->where('mes_referencia', '=', $mes_atual)
                                        ->where('ano_referencia', '=', $ano_atual)
                                        ->where('status', '=', 'pendente')
                                        ->count();
            
            $entregas_aprovadas = Entrega::where('cliente_id', '=', $cliente_id)
                                        ->where('mes_referencia', '=', $mes_atual)
                                        ->where('ano_referencia', '=', $ano_atual)
                                        ->where('status', '=', 'aprovado')
                                        ->count();
            
            // Próximo vencimento
            $proximo_vencimento = '';
            $dia_mais_proximo = 32;
            foreach ($vinculos as $vinculo) {
                $projeto = new Projeto($vinculo->projeto_id);
                if ($projeto->dia_vencimento < $dia_mais_proximo) {
                    $dia_mais_proximo = $projeto->dia_vencimento;
                    $proximo_vencimento = $dia_mais_proximo . '/' . $mes_atual . '/' . $ano_atual;
                }
            }
            
            // HTML dos cards
            $html = "<div class='row'>";
            $html .= "<div class='col-sm-3'><div class='info-box'><span class='info-box-icon bg-blue'><i class='fa fa-folder'></i></span>";
            $html .= "<div class='info-box-content'><span class='info-box-text'>Projetos Ativos</span><span class='info-box-number'>{$total_projetos}</span></div></div></div>";
            
            $html .= "<div class='col-sm-3'><div class='info-box'><span class='info-box-icon bg-yellow'><i class='fa fa-clock-o'></i></span>";
            $html .= "<div class='info-box-content'><span class='info-box-text'>Entregas Pendentes</span><span class='info-box-number'>{$entregas_pendentes}</span></div></div></div>";
            
            $html .= "<div class='col-sm-3'><div class='info-box'><span class='info-box-icon bg-green'><i class='fa fa-check'></i></span>";
            $html .= "<div class='info-box-content'><span class='info-box-text'>Entregas Aprovadas</span><span class='info-box-number'>{$entregas_aprovadas}</span></div></div></div>";
            
            $html .= "<div class='col-sm-3'><div class='info-box'><span class='info-box-icon bg-red'><i class='fa fa-calendar'></i></span>";
            $html .= "<div class='info-box-content'><span class='info-box-text'>Próximo Vencimento</span><span class='info-box-number'>{$proximo_vencimento}</span></div></div></div>";
            $html .= "</div>";
            
            // Lista de projetos
            $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
            $this->datagrid->width = '100%';
            
            $col_projeto = new TDataGridColumn('projeto_id', 'Projeto', 'left');
            $col_vencimento = new TDataGridColumn('dia_vencimento', 'Vencimento', 'center');
            $col_status = new TDataGridColumn('status', 'Status Entrega', 'center');
            
            $col_projeto->setTransformer(function($value) {
                $projeto = new Projeto($value);
                return $projeto->nome;
            });
            
            $col_vencimento->setTransformer(function($value) {
                return 'Dia ' . $value;
            });
            
            $this->datagrid->addColumn($col_projeto);
            $this->datagrid->addColumn($col_vencimento);
            $this->datagrid->addColumn($col_status);
            
            $action_entregar = new TDataGridAction(['EntregaForm', 'onEdit'], ['projeto_id' => '{projeto_id}', 'cliente_id' => $cliente_id]);
            $this->datagrid->addAction($action_entregar, 'Enviar Documentos', 'fa:upload blue');
            
            $this->datagrid->createModel();
            
            // Carregar dados
            foreach ($vinculos as $vinculo) {
                $projeto = new Projeto($vinculo->projeto_id);
                
                $entrega = Entrega::where('cliente_id', '=', $cliente_id)
                                 ->where('projeto_id', '=', $projeto->id)
                                 ->where('mes_referencia', '=', $mes_atual)
                                 ->where('ano_referencia', '=', $ano_atual)
                                 ->first();
                
                $item = new stdClass;
                $item->projeto_id = $projeto->id;
                $item->dia_vencimento = $projeto->dia_vencimento;
                $item->status = $entrega ? $entrega->status : 'Não enviada';
                
                $this->datagrid->addItem($item);
            }
            
            TTransaction::close();
            
            $container = new TVBox;
            $container->style = 'width: 100%';
            $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
            $container->add(new TElement('div', $html));
            $container->add(TPanelGroup::pack('Meus Projetos', $this->datagrid));
            
            parent::add($container);
            
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }
}

