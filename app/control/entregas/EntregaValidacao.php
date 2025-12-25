<?php

class EntregaValidacao extends TPage
{
    protected $form;
    
    public function __construct()
    {
        parent::__construct();
        
        $this->form = new BootstrapFormBuilder('form_validacao');
        $this->form->setFormTitle('Validação de Entrega');
        
        parent::add($this->form);
    }
    
    public function onView($param)
    {
        try {
            TTransaction::open('database');
            
            $entrega = new Entrega($param['id']);
            $cliente = new Usuario($entrega->cliente_id);
            $projeto = new Projeto($entrega->projeto_id);
            
            $this->form->clear();
            
            $entrega_id = new THidden('entrega_id');
            $entrega_id->setValue($entrega->id);
            
            $this->form->addFields([$entrega_id]);
            
            // Informações da entrega
            $html = "<div class='panel panel-info'>";
            $html .= "<div class='panel-heading'>Informações da Entrega</div>";
            $html .= "<div class='panel-body'>";
            $html .= "<p><strong>Cliente:</strong> {$cliente->nome}</p>";
            $html .= "<p><strong>Projeto:</strong> {$projeto->nome}</p>";
            $html .= "<p><strong>Mês/Ano:</strong> " . str_pad($entrega->mes_referencia, 2, '0', STR_PAD_LEFT) . "/" . $entrega->ano_referencia . "</p>";
            $html .= "<p><strong>Status Atual:</strong> {$entrega->status}</p>";
            $html .= "<p><strong>Data de Entrega:</strong> " . ($entrega->data_entrega ? date('d/m/Y H:i', strtotime($entrega->data_entrega)) : '-') . "</p>";
            $html .= "</div></div>";
            
            $this->form->addContent([new TElement('div', $html)]);
            
            // Lista de documentos
            $documentos = $entrega->get_documentos();
            
            if ($documentos) {
                $html_docs = "<div class='panel panel-primary'>";
                $html_docs .= "<div class='panel-heading'>Documentos Entregues</div>";
                $html_docs .= "<div class='panel-body'>";
                
                // $documentos is ['doc_name' => 'file_path']
                foreach ($documentos as $doc_nome => $doc_arquivo) {
                    $html_docs .= "<div class='well'>";
                    $html_docs .= "<h4>{$doc_nome}</h4>";
                    $html_docs .= "<p><a href='{$doc_arquivo}' target='_blank' class='btn btn-sm btn-primary'>";
                    $html_docs .= "<i class='fa fa-download'></i> Baixar/Visualizar</a></p>";
                    $html_docs .= "</div>";
                }
                
                $html_docs .= "</div></div>";
                
                $this->form->addContent([new TElement('div', $html_docs)]);
            }
            
            // Campo de observações do gestor
            $observacoes = new TText('observacoes');
            $observacoes->setSize('100%', 100);
            $observacoes->setValue($entrega->observacoes);
            
            $this->form->addFields([new TLabel('Observações do Gestor')], [$observacoes]);
            
            // Botões de ação
            if ($entrega->status == 'pendente' || $entrega->status == 'em_analise') {
                $btn_aprovar = $this->form->addAction('Aprovar', new TAction([$this, 'onAprovar']), 'fa:check green');
                $btn_rejeitar = $this->form->addAction('Rejeitar', new TAction([$this, 'onRejeitar']), 'fa:times red');
            }
            
            if ($entrega->status == 'aprovado' && !$entrega->consolidado) {
                $btn_consolidar = $this->form->addAction('Gerar Consolidação', new TAction([$this, 'onConsolidarPDF']), 'fa:file-pdf-o orange');
            }
            
            if ($entrega->consolidado && $entrega->arquivo_consolidado) {
                $btn_download = $this->form->addAction('Download PDF Consolidado', new TAction([$this, 'onDownload']), 'fa:download blue');
            }
            
            $btn_voltar = $this->form->addAction('Voltar', new TAction(['EntregaList', 'onReload']), 'fa:arrow-left');
            
            TTransaction::close();
            
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
    
    public function onAprovar($param)
    {
        try {
            TTransaction::open('database');
            
            $entrega = new Entrega($param['entrega_id']);
            $entrega->status = 'aprovado';
            $entrega->data_aprovacao = date('Y-m-d H:i:s');
            $entrega->aprovado_por = TSession::getValue('userid');
            $entrega->observacoes = $param['observacoes'] ?? '';
            $entrega->store();
            
            TTransaction::close();
            
            new TMessage('info', 'Entrega aprovada com sucesso!');
            $this->onView(['id' => $entrega->id]);
            
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
    
    public function onRejeitar($param)
    {
        try {
            TTransaction::open('database');
            
            $entrega = new Entrega($param['entrega_id']);
            $entrega->status = 'rejeitado';
            $entrega->observacoes = $param['observacoes'] ?? '';
            $entrega->store();
            
            TTransaction::close();
            
            new TMessage('info', 'Entrega rejeitada.');
            TApplication::gotoPage('EntregaList');
            
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
    
    public function onDownload($param)
    {
        try {
            TTransaction::open('database');
            
            $entrega = new Entrega($param['entrega_id']);
            
            if ($entrega->arquivo_consolidado && file_exists($entrega->arquivo_consolidado)) {
                $extension = strtolower(pathinfo($entrega->arquivo_consolidado, PATHINFO_EXTENSION));
                $content_type = ($extension == 'pdf') ? 'application/pdf' : 'application/zip';
                header('Content-Type: ' . $content_type);
                header('Content-Disposition: attachment; filename="' . basename($entrega->arquivo_consolidado) . '"');
                readfile($entrega->arquivo_consolidado);
            } else {
                throw new Exception('Arquivo consolidado não encontrado');
            }
            
            TTransaction::close();
            
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
    
    public function onConsolidarPDF($param)
    {
        try {
            TTransaction::open('database');
            
            $entrega = new Entrega($param['entrega_id']);
            
            // Save observations before consolidating
            $entrega->observacoes = $param['observacoes'] ?? '';
            $entrega->store();
            
            TTransaction::close();
            
            // Call the consolidation logic
            ConsolidarEntrega::onConsolidar(['id' => $entrega->id]);
            
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
}
