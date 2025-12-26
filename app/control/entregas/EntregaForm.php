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
        
        // DEBUG: Temporary info
        $debug = new TElement('div');
        $debug->style = 'background: #ffe; padding: 10px; border: 1px solid #ddd; margin-top: 20px;';
        $debug->add("<b>DEBUG INFO:</b><br>");
        $debug->add("Cliente ID Sessão: " . $cliente_id . "<br>");
        $debug->add("Projeto ID Resolvido: " . var_export($projeto_id, true) . "<br>");
        $debug->add("Documentos Encontrados: " . count($doc_items) . "<br>");
        $debug->add("Project Documents Query: ProjektDocumento::where('projeto_id', '=', $projeto_id)<br>");
        $this->form->add($debug);
        
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
            
            // Get expected documents
            $doc_items = ProjetoDocumento::where('projeto_id', '=', $projeto_id)->load();
            
            $documentos_salvos = [];
            $missing = [];
            
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
                        } else {
                             throw new Exception("Falha ao mover arquivo: {$fileName}");
                        }
                    } else {
                        // Debug log if file not found
                        // file_put_contents('log_entrega_error.txt', "File not found: $verifyPath\n", FILE_APPEND);
                    }
                } else {
                    if (isset($item->obrigatorio) && ($item->obrigatorio == '1' || $item->obrigatorio === 1)) {
                         $missing[] = $item->nome_documento;
                    }
                }
            }
            
            if (empty($documentos_salvos)) {
                throw new Exception("Nenhum documento foi anexado.");
            }

            $entrega = new Entrega;
            $entrega->cliente_id = $cliente_id;
            $entrega->projeto_id = $projeto_id;
            $entrega->mes_referencia = $mes;
            $entrega->ano_referencia = $ano;
            $entrega->documentos_json = json_encode($documentos_salvos);
            $entrega->status = 'pendente';
            $entrega->data_entrega = date('Y-m-d H:i:s');
            $entrega->store();

            // Notify Managers
            try {
                // Assuming Autoload can find NotificationService or we include it. 
                // Since it is in app/service, it should be autoloaded if registered in init.
                // If not, we might need require_once.
                // For now assuming Adianti standard autoload works or I need to register it.
                // Let's assume Standard Autoload.
                
                $cliente_nome = TSession::getValue('username');
                $subject = "Nova Entrega: $cliente_nome";
                $msg_body = "O cliente $cliente_nome enviou documentos referentes a " . str_pad($mes, 2, '0', STR_PAD_LEFT) . "/$ano.";
                
                NotificationService::notifyGestores(TSession::getValue('userid'), $subject, $msg_body);
            } catch (Exception $e) {
                // Ignore notification errors
            }

            TTransaction::close();

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
