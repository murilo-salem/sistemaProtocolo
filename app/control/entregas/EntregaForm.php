<?php

class EntregaForm extends TPage
{
    protected $form;

    public function __construct($param)
    {
        parent::__construct();

        $this->form = new BootstrapFormBuilder('form_entrega');
        $this->form->setFormTitle('Envio de Documentos');
        
        // Define fields manually so TForm knows about them (even if rendered via HTML)
        $cliente_id_field = new THidden('cliente_id');
        $projeto_id_field = new THidden('projeto_id');
        
        // Month/Year as Hidden fields (auto-assigned to current)
        $mes = new THidden('mes_referencia');
        $ano = new THidden('ano_referencia');
        
        // Default values
        $cliente_id = TSession::getValue('userid');
        $projeto_id = $param['projeto_id'] ?? null;
        
        // If no project selected, try to find one for this client
        if (empty($projeto_id)) {
             try {
                 TTransaction::open('database');
                 $rel = ClienteProjeto::where('cliente_id', '=', $cliente_id)->first();
                 if ($rel) {
                     $projeto_id = $rel->projeto_id;
                 }
                 TTransaction::close();
             } catch (Exception $e) {
                 // ignore
             }
        }
        
        // Validation: If still no project, we can't show requirements
        if (empty($projeto_id)) {
            new TMessage('error', 'Nenhum projeto encontrado para este usuário. Impossível enviar documentos.');
        }

        $cliente_id_field->setValue($cliente_id);
        $projeto_id_field->setValue($projeto_id);
        
        $mes->setValue(date('n')); 
        $ano->setValue(date('Y'));

        // Register fields in the form
        $this->form->addFields([$cliente_id_field, $projeto_id_field, $mes, $ano]);
        
        // Bloqueio de entrega duplicada (Aprovada)
        try {
            TTransaction::open('database');
            $chk_mes = date('n');
            $chk_ano = date('Y');
            
            $has_approved = Entrega::where('cliente_id', '=', $cliente_id)
                                   ->where('mes_referencia', '=', $chk_mes)
                                   ->where('ano_referencia', '=', $chk_ano)
                                   ->where('status', '=', 'aprovado')
                                   ->count() > 0;
                                   
            $has_pending = Entrega::where('cliente_id', '=', $cliente_id)
                                  ->where('mes_referencia', '=', $chk_mes)
                                  ->where('ano_referencia', '=', $chk_ano)
                                  ->where('status', '=', 'pendente')
                                  ->count() > 0;
                                  
            TTransaction::close();
            
            if ($has_approved) {
                new TMessage('info', "Você já possui uma entrega <b>APROVADA</b> para este mês ({$chk_mes}/{$chk_ano}).<br>Não é necessário enviar novos documentos.");
                return; // Stop rendering form
            }
            
            if ($has_pending) {
                new TMessage('info', "Você possui uma entrega <b>PENDENTE</b> para este mês ({$chk_mes}/{$chk_ano}).<br>Ao enviar novos documentos, sua entrega será atualizada.");
                // Allow rendering form to edit/update
            }
            
        } catch (Exception $e) {
            // ignore
        }
        
        // -- HTML Rendering --
        $html = new THtmlRenderer('app/resources/entrega_form.html');
        
        // 1. Static Replacements
        $replacements = [];
        // No replacements needed for hidden fields in the UI anymore
        
        // 2. Requirements Section
        $requirements_data = [];
        
        if ($projeto_id) {
            TTransaction::open('database');
            // Fetch Requirements
            $doc_items = ProjetoDocumento::where('projeto_id', '=', $projeto_id)->load();
            if ($doc_items) {
                foreach ($doc_items as $item) {
                    $requirements_data[] = [
                        'doc_id' => $item->id,
                        'doc_name' => $item->nome_documento
                    ];
                }
            }
            TTransaction::close();
        }
        
        $html->enableSection('requirements', $requirements_data, true);
        $html->enableSection('main', $replacements);
        
        // Add container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(new TXMLBreadCrumb('menu-cliente.xml', __CLASS__));
        
        // Wrap HTML in a container that the form action will include
        $content = new TElement('div');
        $content->add($html);
        
        $this->form->add($content);
        

        
        // Add Actions (injected into _ACTIONS_)
        $this->form->addAction('Enviar Entrega', new TAction([$this, 'onSave']), 'fa:check green');
        
        $container->add($this->form);
        parent::add($container);
    }

    public function onSave($param)
    {
        try {
            TTransaction::open('database');
            
            $cliente_id = $param['cliente_id'];
            $projeto_id = $param['projeto_id'];
            $mes = $param['mes_referencia'];
            $ano = $param['ano_referencia'];
            
            // Bloqueio Backend: Verifica se JÁ EXISTE entrega aprovada/pendente para este mês
            $existing = Entrega::where('cliente_id', '=', $cliente_id)
                               ->where('mes_referencia', '=', $mes)
                               ->where('ano_referencia', '=', $ano)
                               ->where('status', 'IN', ['aprovado', 'pendente', 'rejeitado']) // Include rejected to update them too
                               ->first();
                               
            if ($existing) {
                if ($existing->status == 'aprovado') {
                    throw new Exception("Já existe uma entrega APROVADA para este mês ({$mes}/{$ano}).");
                } 
                // If 'pendente' or 'rejeitado', we ALLOW update.
                // We will use $existing object to update.
                $entrega = $existing;
                $existing_docs = json_decode($entrega->documentos_json, true) ?? [];
            } else {
                // Create new
                $entrega = new Entrega;
                $entrega->cliente_id = $cliente_id;
                $entrega->projeto_id = $projeto_id;
                $entrega->mes_referencia = $mes;
                $entrega->ano_referencia = $ano;
                $entrega->created_at = date('Y-m-d H:i:s'); // Set creation time if new
                $existing_docs = [];
            }
            
            // Get expected documents
            $doc_items = ProjetoDocumento::where('projeto_id', '=', $projeto_id)->load();
            
            $documentos_salvos = $existing_docs; // Start with existing
            $missing = [];
            $new_files_count = 0;
            
            foreach ($doc_items as $item) {
                $field_name = 'doc_' . $item->id;
                
                if (!empty($param[$field_name])) {
                    $verifyPath = $param[$field_name];
                    
                    // Sanitize path to avoid traversal
                    $verifyPath = str_replace(['../', '..\\'], '', $verifyPath);
                    
                    // AdiantiUploaderService returns just the filename, but file is in tmp/
                    // If the path doesn't start with tmp/, prepend it.
                    if (strpos($verifyPath, 'tmp/') !== 0) {
                        $verifyPath = 'tmp/' . $verifyPath;
                    }

                    $pathParts = explode('/', $verifyPath);
                    $fileName = end($pathParts);
                    
                    if (file_exists($verifyPath)) {
                        // Move to permanent location
                        // Structure: app/uploads/projetos/{projeto_id}/{cliente_id}/{submission_date}/filename
                        $subDir = date('Y-m-d_H-i-s');
                        $targetDir = "app/uploads/projetos/{$projeto_id}/{$cliente_id}/{$subDir}";
                        $targetFile = $targetDir . '/' . $fileName;
                        
                        if (!file_exists($targetDir)) {
                            mkdir($targetDir, 0777, true);
                        }
                        
                        if (rename($verifyPath, $targetFile)) {
                             $documentos_salvos[$item->nome_documento] = $targetFile;
                             $new_files_count++;
                        } else {
                             throw new Exception("Falha ao mover arquivo: {$fileName}");
                        }
                    }
                } else {
                    // Check if exists in previously saved docs
                    if (!isset($documentos_salvos[$item->nome_documento])) {
                        if (isset($item->obrigatorio) && ($item->obrigatorio == '1' || $item->obrigatorio === 1)) {
                             $missing[] = $item->nome_documento;
                        }
                    }
                }
            }
            
            if (empty($documentos_salvos)) {
                throw new Exception("Nenhum documento foi anexado.");
            }
            
            if (!empty($missing)) {
                throw new Exception("Documentos obrigatórios faltando: " . implode(', ', $missing));
            }

            // Update attributes
            $entrega->documentos_json = json_encode($documentos_salvos);
            $entrega->status = 'pendente'; // Reset to pending on update
            $entrega->data_entrega = date('Y-m-d H:i:s'); // Update timestamp
            
            // Should usually reset validation info?
            $entrega->observacoes = null; // Clear rejection notes? Or keep history?
            // Maybe prepend old notes? For now, let's keep it simple.
            
            $entrega->store();
            
            // Salvar dados necessários para notificações
            $entrega_id = $entrega->id;
            $cliente_nome = TSession::getValue('username');
            $periodo = str_pad($mes, 2, '0', STR_PAD_LEFT) . '/' . $ano;

            TTransaction::close();
            // *** Transação principal fechada — entrega salva com sucesso ***

            // Enviar notificações em transações isoladas (cada método abre/fecha a sua)
            try {
                // Notificação customizada para gestores (aparece no NotificationList)
                NotificationService::notifyManagers(
                    "Entrega Realizada - {$cliente_nome}",
                    "O cliente {$cliente_nome} enviou documentos referentes a {$periodo}.",
                    'info',
                    'entrega',
                    $entrega_id,
                    'class=EntregaValidacao&method=onView&id=' . $entrega_id
                );
                
                // Notificação do sistema (barra superior Adianti) — precisa de transação própria
                TTransaction::open('database');
                $gestores = Usuario::where('tipo', 'IN', ['admin', 'gestor'])->load();
                
                if ($gestores) {
                    $sent_to = [];
                    foreach ($gestores as $user) {
                        if (in_array($user->id, $sent_to)) continue;
                        
                        SystemNotification::register(
                            $user->id,
                            "Entrega Realizada - {$cliente_nome}",
                            "O cliente {$cliente_nome} enviou documentos referentes a {$periodo}.",
                            'class=EntregaValidacao&method=onView&id=' . $entrega_id,
                            'Validar Entrega',
                            'fa fa-file-text-o'
                        );
                        $sent_to[] = $user->id;
                    }
                }
                TTransaction::close();
                
                // Confirmação para o próprio cliente
                NotificationService::notifyClient(
                    $cliente_id,
                    "Entrega Enviada - {$periodo}",
                    "Seus documentos referentes a {$periodo} foram recebidos e estão aguardando validação.",
                    'success',
                    'entrega',
                    $entrega_id,
                    'class=EntregaList'
                );
            } catch (Exception $e) {
                // Ignorar erros de notificação — a entrega já foi salva
            }

            new TMessage('info', 'Documentos enviados com sucesso!', new TAction(['EntregaList', 'onReload']));
            
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }
    
    public function onEdit($param)
    {
        // Edit mode logic if needed
    }
}
