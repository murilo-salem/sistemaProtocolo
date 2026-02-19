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
            
            // Prevent self-validation
            if ($entrega->cliente_id == TSession::getValue('userid')) {
                new TMessage('error', 'Você não pode validar sua própria entrega. Aguarde a análise de um gestor.');
                TTransaction::close();
                return;
            }
            
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
            
            if ($entrega->status == 'aprovado' && !$entrega->isConsolidado()) {
                 // If already approved, show consolidate button
                $btn_consolidar = $this->form->addAction('Gerar Consolidação', new TAction([$this, 'onConsolidarPDF']), 'fa:file-pdf orange');
            }
            
            if ($entrega->isConsolidado() && $entrega->arquivo_consolidado && file_exists($entrega->arquivo_consolidado)) {
                $btn_download = $this->form->addAction('Download PDF Consolidado', new TAction([$this, 'onDownload'], ['entrega_id' => $entrega->id]), 'fa:download blue');
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
            
            if ($entrega->cliente_id == TSession::getValue('userid')) {
                throw new Exception('Você não pode validar sua própria entrega.');
            }

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
                
                // Salvar dados antes de fechar transação
                $cliente_id = $entrega->cliente_id;
                $entrega_id = $entrega->id;
                $periodo = str_pad($entrega->mes_referencia, 2, '0', STR_PAD_LEFT) . '/' . $entrega->ano_referencia;
                
                TTransaction::close();
                // *** Transação principal fechada — entrega atualizada ***
                
                new TMessage('info', 'Todos os documentos foram validados. Entrega APROVADA!');
                
                // Notificações em transações isoladas
                try {
                    $subject = "Entrega Aprovada - {$periodo}";
                    $msg_body = "Sua entrega de documentos referente a {$periodo} foi analisada e aprovada.";
                    
                    // Notificação customizada (NotificationList)
                    NotificationService::notifyClient(
                        $cliente_id,
                        $subject,
                        $msg_body,
                        'success',
                        'entrega',
                        $entrega_id,
                        'class=EntregaList'
                    );
                    
                    // Notificação do sistema (barra superior Adianti) — transação própria
                    TTransaction::open('database');
                    SystemNotification::register(
                        $cliente_id,
                        $subject,
                        $msg_body,
                        'class=EntregaList',
                        'Ver Entregas',
                        'fa fa-check-circle'
                    );
                    TTransaction::close();
                } catch (Exception $e) {}
            } else {
                $entrega->status = 'rejeitado';
                $entrega->observacoes = $param['observacoes'] ?? '';
                $entrega->store();
                
                // Salvar dados antes de fechar transação
                $cliente_id = $entrega->cliente_id;
                $entrega_id = $entrega->id;
                $periodo = str_pad($entrega->mes_referencia, 2, '0', STR_PAD_LEFT) . '/' . $entrega->ano_referencia;
                
                TTransaction::close();
                // *** Transação principal fechada — entrega atualizada ***
                
                // Enviar notificações de rejeição em transações isoladas
                $this->notifyClient($cliente_id, $entrega_id, $periodo, $rejection_reasons);
                
                new TMessage('warning', 'Alguns documentos foram rejeitados. O cliente foi notificado.');
            }
            
            // Reload page to show new status
            $this->onView(['id' => $entrega_id]);
            
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
    
    public function notifyClient($cliente_id, $entrega_id, $periodo, $reasons)
    {
        $subject = "Entrega Reprovada - {$periodo}";
        
        $msg_body = "Sua entrega referente a {$periodo} foi analisada e REJEITADA.\\n\\n";
        $msg_body .= "Motivos:\n";
        $msg_body .= implode("\n", $reasons);
        $msg_body .= "\n\nPor favor, corrija os arquivos e envie novamente.";
        
        // Notificação customizada (NotificationList)
        NotificationService::notifyClient(
            $cliente_id,
            $subject,
            $msg_body,
            'warning',
            'entrega',
            $entrega_id,
            'class=EntregaList'
        );
        
        // Notificação do sistema (barra superior Adianti) — transação própria
        TTransaction::open('database');
        SystemNotification::register(
            $cliente_id,
            $subject,
            $msg_body,
            'class=EntregaList',
            'Ver Detalhes',
            'fa fa-times-circle'
        );
        TTransaction::close();
    }
    
    public function onDownload($param)
    {
        try {
            TTransaction::open('database');
            
            $entrega = new Entrega($param['entrega_id']);
            
            if (empty($entrega->arquivo_consolidado) || !file_exists($entrega->arquivo_consolidado)) {
                throw new Exception('Arquivo consolidado não encontrado.');
            }
            
            $entrega_id = $entrega->id;
            TTransaction::close();
            
            // Redirecionar para download via request direto (não AJAX)
            // Isso evita que o binário do PDF seja injetado na resposta AJAX
            TScript::create("window.location.href = 'engine.php?class=ConsolidarEntregaV2&method=onDownload&id={$entrega_id}';");
            
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
    
    public function onConsolidarPDF($param)
    {
        try {
            $service = new ConsolidacaoService();
            $resultado = $service->consolidarEntrega($param['entrega_id']);
            
            if ($resultado['success']) {
                $mensagem = 'Relatório consolidado gerado com sucesso!';
                if (!empty($resultado['erros'])) {
                    $mensagem .= "\n\nAvisos:\n- " . implode("\n- ", $resultado['erros']);
                }
                new TMessage('info', $mensagem, new TAction([$this, 'onView'], ['id' => $param['entrega_id']]));
            } else {
                $erros = implode("\n", $resultado['erros']);
                new TMessage('error', "Erro na consolidação:\n{$erros}");
            }
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }
}
