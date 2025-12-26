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
                $this->form->addContent([new TElement('h4', 'Validação de Itens')]);
                
                // $documentos is ['doc_name' => 'file_path']
                // Need to use index to create unique field names
                $i = 0;
                foreach ($documentos as $doc_nome => $doc_arquivo) {
                    $i++;
                    
                    $frame = new TElement('div');
                    $frame->style = 'margin-bottom: 20px; border: 1px solid #ddd; padding: 15px; border-radius: 4px;';
                    
                    $link = "<a href='{$doc_arquivo}' target='_blank' class='btn btn-default btn-xs'><i class='fa fa-download'></i> Ver Arquivo</a>";
                    $label = new TLabel("<b>{$doc_nome}</b> $link");
                    
                    $status_field = new TCombo("status_doc_{$i}");
                    $status_field->addItems(['aprovado' => 'Aprovado', 'rejeitado' => 'Rejeitado']);
                    $status_field->setValue('aprovado');
                    $status_field->setSize('100%');
                    $status_field->setChangeAction(new TAction([$this, 'onChangeStatus'], ['index' => $i]));
                    
                    $motivo_field = new TEntry("motivo_doc_{$i}");
                    $motivo_field->setProperty('placeholder', 'Motivo da rejeição (obrigatório se rejeitado)');
                    $motivo_field->setSize('100%');
                    // $motivo_field->style = 'display:none'; // Hard to toggle via pure PHP without reload, keeping visible but optional
                    
                    // Hidden field to store doc name for reference
                    $name_field = new THidden("nome_doc_{$i}");
                    $name_field->setValue($doc_nome);
                    
                    $row = new TElement('div');
                    $row->class = 'row';
                    
                    $col1 = new TElement('div'); $col1->class = 'col-sm-6';
                    $col1->add($label);
                    
                    $col2 = new TElement('div'); $col2->class = 'col-sm-2';
                    $col2->add($status_field);
                    
                    $col3 = new TElement('div'); $col3->class = 'col-sm-4';
                    $col3->add($motivo_field);
                    
                    $row->add($col1);
                    $row->add($col2);
                    $row->add($col3);
                    
                    $frame->add($row);
                    
                    $this->form->addFields([$name_field]); // register hidden
                    $this->form->addContent([$frame]);
                    
                    // Register fields manually in form to ensure they are posted
                    $this->form->addField($status_field);
                    $this->form->addField($motivo_field);
                }
                
                // Hidden field store count
                $count_field = new THidden('doc_count');
                $count_field->setValue($i);
                $this->form->addFields([$count_field]);
            }
            
            // Campo de observações gerais
            $observacoes = new TText('observacoes');
            $observacoes->setSize('100%', 100);
            $observacoes->setValue($entrega->observacoes);
            
            $this->form->addFields([new TLabel('Observações Gerais')], [$observacoes]);
            
            // Botões de ação
            if ($entrega->status != 'aprovado') { // Allow re-validation if needed or if pending
                $btn_confirmar = $this->form->addAction('Confirmar Validação', new TAction([$this, 'onConfirmar']), 'fa:check-circle green');
            }
            
            if ($entrega->status == 'aprovado' && !$entrega->consolidado) {
                 // If already approved, show consolidate button
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
    
    public static function onChangeStatus($param)
    {
        // Placeholder for dynamic show/hide interaction if needed using TScript
        // Currently keeping simple
    }

    public function onConfirmar($param)
    {
        try {
            TTransaction::open('database');
            
            $entrega = new Entrega($param['entrega_id']);
            $count = (int) ($param['doc_count'] ?? 0);
            
            $all_approved = true;
            $rejection_reasons = [];
            
            for ($i = 1; $i <= $count; $i++) {
                $status = $param["status_doc_{$i}"] ?? 'aprovado';
                $motivo = $param["motivo_doc_{$i}"] ?? '';
                $doc_nome = $param["nome_doc_{$i}"] ?? "Documento $i";
                
                if ($status == 'rejeitado') {
                    $all_approved = false;
                    if (empty($motivo)) {
                        throw new Exception("O motivo é obrigatório para o documento '{$doc_nome}' ser rejeitado.");
                    }
                    $rejection_reasons[] = "- {$doc_nome}: {$motivo}";
                }
            }
            
            if ($all_approved) {
                $entrega->status = 'aprovado';
                $entrega->data_aprovacao = date('Y-m-d H:i:s');
                $entrega->aprovado_por = TSession::getValue('userid');
                $entrega->observacoes = $param['observacoes'] ?? '';
                $entrega->store();
                
                // Notify Client of Approval
                try {
                    $subject = "Entrega Aprovada: " . $entrega->mes_referencia . "/" . $entrega->ano_referencia;
                    $msg_body = "Sua entrega de documentos referente a " . str_pad($entrega->mes_referencia, 2, '0', STR_PAD_LEFT) . "/" . $entrega->ano_referencia . " foi analisada e aprovada.";
                    NotificationService::send(TSession::getValue('userid'), $entrega->cliente_id, $subject, $msg_body);
                } catch (Exception $e) {}
                
                new TMessage('info', 'Todos os documentos foram validados. Entrega APROVADA!');
            } else {
                $entrega->status = 'rejeitado';
                $entrega->observacoes = $param['observacoes'] ?? '';
                $entrega->store();
                
                // Send Notification (Rejection)
                $this->notifyClient($entrega, $rejection_reasons);
                
                new TMessage('warning', 'Alguns documentos foram rejeitados. O cliente foi notificado.');
            }
            
            TTransaction::close();
            
            // Reload page to show new status
            $this->onView(['id' => $entrega->id]);
            
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
    
    public function notifyClient($entrega, $reasons)
    {
        // Use NotificationService
        $msg_body = "Sua entrega referente a " . str_pad($entrega->mes_referencia, 2, '0', STR_PAD_LEFT) . "/" . $entrega->ano_referencia . " foi analisada e REJEITADA.\n\n";
        $msg_body .= "Motivos:\n";
        $msg_body .= implode("\n", $reasons);
        $msg_body .= "\n\nPor favor, corrija os arquivos e envie novamente.";
        
        $subject = "Correção Solicitada: Entrega " . $entrega->mes_referencia . "/" . $entrega->ano_referencia;
        
        NotificationService::send(TSession::getValue('userid'), $entrega->cliente_id, $subject, $msg_body);
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
        // Forward logic to ConsolidarEntrega
        ConsolidarEntrega::onConsolidar(['id' => $param['entrega_id']]);
    }
}
