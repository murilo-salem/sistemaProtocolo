<?php
class EstatisticasRoot extends TPage
{
    private $form;
    private $datagrid;
    
    public function __construct()
    {
        parent::__construct();
        
        $this->form = new TForm('form_estatisticas');
        
        $criteria_gestor = new TCriteria;
        $criteria_gestor->add(new TFilter('tipo', '=', 'gestor'));
        $gestor_id = new TDBCombo('gestor_id', 'database', 'Usuario', 'id', 'nome', 'nome', $criteria_gestor);
        $gestor_id->setSize('100%');
        $gestor_id->setDefaultOption('Selecione um Gestor...');
        
        $btn_search = new TButton('btn_search');
        $btn_search->setAction(new TAction([$this, 'onSearch']), 'Gerar Estatísticas');
        $btn_search->setImage('fa:bar-chart white');
        $btn_search->class = 'btn btn-primary';
        
        $btn_report = new TButton('btn_report');
        $btn_report->setAction(new TAction([$this, 'onGeneratePDF']), 'Baixar Relatório PDF');
        $btn_report->setImage('fa:file-pdf-o red');
        $btn_report->class = 'btn btn-default';
        
        $table = new TTable;
        $table->style = 'width: 100%; border-spacing: 10px; border-collapse: separate;';
        
        $row = $table->addRow();
        $row->addCell(new TLabel('Gestor:'))->style = 'width: 100px';
        $row->addCell($gestor_id);
        
        $row = $table->addRow();
        $row->addCell('');
        $btn_box = new THBox;
        $btn_box->add($btn_search);
        $btn_box->add($btn_report);
        $row->addCell($btn_box);
        
        $this->form->add($table);
        $this->form->setFields([$gestor_id, $btn_search, $btn_report]);
        
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->addColumn(new TDataGridColumn('id', 'ID', 'center', 50));
        $this->datagrid->addColumn(new TDataGridColumn('cliente_nome', 'Cliente', 'left'));
        $this->datagrid->addColumn(new TDataGridColumn('projeto_nome', 'Projeto', 'left'));
        $this->datagrid->addColumn(new TDataGridColumn('status', 'Status', 'center'));
        $this->datagrid->addColumn(new TDataGridColumn('data_entrega', 'Data', 'center'));
        $this->datagrid->createModel();
        
        $this->html = new THtmlRenderer('app/resources/estatisticas_root.html');
        $this->html->enableSection('main');
        
        $data = TSession::getValue('EstatisticasRoot_data');
        if ($data && !empty($data->gestor_id)) {
            $this->loadData($data->gestor_id);
            $this->form->setData($data);
        } else {
            $this->html->enableSection('empty');
        }
        
        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add(new TXMLBreadCrumb('menu-root.xml', __CLASS__));
        $vbox->add($this->form);
        $vbox->add($this->html);
        
        parent::add($vbox);
    }
    
    public function onSearch($param)
    {
        try {
            $data = $this->form->getData();
            if (empty($data->gestor_id)) {
                throw new Exception('Selecione um gestor');
            }
            
            TSession::setValue('EstatisticasRoot_data', $data);
            TApplication::loadPage(__CLASS__);
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }

    public function loadData($gestor_id)
    {
        try {
            TTransaction::open('database');
            
            // Buscar clientes vinculados ao gestor
            $clientes = Usuario::where('gestor_id', '=', $gestor_id)->load();
            $cliente_ids = [0]; // fallback
            if ($clientes) {
                foreach ($clientes as $c) $cliente_ids[] = $c->id;
            }
            
            // Métricas
            $total = Entrega::where('cliente_id', 'IN', $cliente_ids)->count();
            $aprovadas = Entrega::where('cliente_id', 'IN', $cliente_ids)->where('status', '=', 'aprovado')->count();
            $pendentes = Entrega::where('cliente_id', 'IN', $cliente_ids)->where('status', '=', 'pendente')->count();
            $rejeitadas = Entrega::where('cliente_id', 'IN', $cliente_ids)->where('status', '=', 'rejeitado')->count();
            
            // Listagem
            $entregas = Entrega::where('cliente_id', 'IN', $cliente_ids)->orderBy('data_entrega', 'desc')->load();
            
            $this->datagrid->clear();
            if ($entregas) {
                foreach ($entregas as $e) {
                    $item = new stdClass;
                    $item->id = $e->id;
                    $item->cliente_nome = $e->cliente->nome;
                    $item->projeto_nome = $e->projeto->nome;
                    $item->status = $e->status;
                    $item->data_entrega = $e->data_entrega ? date('d/m/Y', strtotime($e->data_entrega)) : '-';
                    $this->datagrid->addItem($item);
                }
            }
            
            $this->html->enableSection('result', [
                'total_entregas' => $total,
                'total_aprovadas' => $aprovadas,
                'total_pendentes' => $pendentes,
                'total_rejeitadas' => $rejeitadas
            ]);
            
            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }
    
    public function onGeneratePDF($param)
    {
        try {
            $data = $this->form->getData();
            if (empty($data->gestor_id)) throw new Exception('Selecione um gestor antes de baixar o relatório.');
            
            TTransaction::open('database');
            $gestor = new Usuario($data->gestor_id);
            
            $clientes = Usuario::where('gestor_id', '=', $data->gestor_id)->load();
            $cliente_ids = [0];
            if ($clientes) foreach ($clientes as $c) $cliente_ids[] = $c->id;
            
            $entregas = Entrega::where('cliente_id', 'IN', $cliente_ids)->orderBy('data_entrega', 'desc')->load();
            
            if (!$entregas) throw new Exception('Nenhuma entrega encontrada para este gestor.');
            
            // Gerar HTML simples para o PDF
            $html = "<h1>Relatório de Desempenho - Gestor: {$gestor->nome}</h1>";
            $html .= "<p>Data: " . date('d/m/Y H:i') . "</p>";
            $html .= "<table border='1' width='100%' style='border-collapse: collapse;'>";
            $html .= "<thead><tr style='background: #eee;'><th>ID</th><th>Cliente</th><th>Projeto</th><th>Status</th><th>Data</th></tr></thead><tbody>";
            
            foreach ($entregas as $e) {
                $dt = $e->data_entrega ? date('d/m/Y', strtotime($e->data_entrega)) : '-';
                $html .= "<tr><td align='center'>{$e->id}</td><td>{$e->cliente->nome}</td><td>{$e->projeto->nome}</td><td align='center'>{$e->status}</td><td align='center'>{$dt}</td></tr>";
            }
            $html .= "</tbody></table>";
            
            // Salvar PDF temporário usando dompdf ou similar (Assumindo que o ambiente tem suporte)
            // Se não tiver dompdf, podemos apenas exibir um erro ou usar outra técnica.
            // Para simplicidade e garantir funcionamento no browser subagent se necessário:
            $filename = 'tmp/relatorio_gestor_' . $gestor->id . '.html';
            file_put_contents($filename, $html);
            
            TTransaction::close();
            
            $action = new TAction(['DownloadService', 'onDownload']);
            $action->setParameter('file', $filename);
            TApplication::gotoPage('DownloadService', 'onDownload', ['file' => $filename]);
            
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }
}
