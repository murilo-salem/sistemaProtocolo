<?php

class ClienteForm extends TPage
{
    protected $form;
    
    public function __construct()
    {
        parent::__construct();
        
        // Create form
        $this->form = new TForm('form_cliente');
        
        // Hidden fields
        $id = new THidden('id');
        $current_step = new THidden('current_step');
        $current_step->setValue('1');
        
        // Step 1: Personal Data
        $nome = new TEntry('nome');
        $nome->setProperty('placeholder', 'Nome completo do cliente');
        $nome->setSize('100%');
        $nome->addValidation('Nome', new TRequiredValidator);
        
        $email = new TEntry('email');
        $email->setProperty('placeholder', 'email@exemplo.com');
        $email->setSize('100%');
        $email->addValidation('Email', new TRequiredValidator);
        $email->addValidation('Email', new TEmailValidator);
        
        // Step 2: Access & Project
        $login = new TEntry('login');
        $login->setProperty('placeholder', 'Será gerado automaticamente');
        $login->setSize('100%');
        $login->setEditable(FALSE);
        
        $senha = new TEntry('senha');
        $senha->setProperty('placeholder', 'Será gerada automaticamente');
        $senha->setSize('100%');
        $senha->setEditable(FALSE);
        
        // Company dropdown for filtering projects
        $empresa_id = new TDBCombo('empresa_id', 'database', 'CompanyTemplate', 'id', 'name');
        $empresa_id->setDefaultOption('Selecione uma empresa (opcional)...');
        $empresa_id->setSize('100%');
        $empresa_id->setChangeAction(new TAction([$this, 'onChangeEmpresa']));
        
        $projetos = new TCombo('projetos');
        $projetos->setDefaultOption('Selecione uma opção...');
        $projetos->enableSearch();
        
        // Load Global Templates (no company) by default
        TTransaction::open('database');
        $global_templates = Projeto::where('is_template', '=', '1')
                                   ->where('ativo', '=', 1)
                                   ->where('company_template_id', 'IS', NULL)
                                   ->load();
        $opts = [];
        if ($global_templates) {
            foreach ($global_templates as $gt) {
                $opts[$gt->id] = $gt->nome . ' (Geral)';
            }
        }
        $projetos->addItems($opts);
        TTransaction::close();
        $projetos->setSize('100%');
        
        $ativo = new TRadioGroup('ativo');
        $ativo->setUseButton();
        $ativo->addItems(['1' => 'Ativo', '0' => 'Inativo']);
        $ativo->setValue('1');
        $ativo->setLayout('horizontal');
        
        // Build wizard HTML structure
        $html = new TElement('div');
        $html->class = 'wizard-container';
        
        // Wizard Header with Stepper
        $header = new TElement('div');
        $header->class = 'wizard-header';
        
        $stepper = new TElement('div');
        $stepper->class = 'wizard-stepper';
        $stepper->add('
            <div class="step active" data-step="1">
                <div class="step-number">1</div>
                <div class="step-label">Dados Pessoais</div>
            </div>
            <div class="step-line"></div>
            <div class="step" data-step="2">
                <div class="step-number">2</div>
                <div class="step-label">Acesso & Projeto</div>
            </div>
        ');
        $header->add($stepper);
        $html->add($header);
        
        // Wizard Body
        $body = new TElement('div');
        $body->class = 'wizard-body';
        
        // Step 1 Content
        $step1 = new TElement('div');
        $step1->class = 'wizard-step active';
        $step1->id = 'wizard-step-1';
        
        $step1Title = new TElement('div');
        $step1Title->class = 'step-title';
        $step1Title->add('<h2>Dados Pessoais</h2><p>Informe os dados de identificação do cliente</p>');
        $step1->add($step1Title);
        
        $step1Fields = new TElement('div');
        $step1Fields->class = 'wizard-fields';
        
        // Nome field group
        $nomeGroup = new TElement('div');
        $nomeGroup->class = 'field-group';
        $nomeLabel = new TElement('label');
        $nomeLabel->add('Nome Completo *');
        $nomeGroup->add($nomeLabel);
        $nomeGroup->add($nome);
        $step1Fields->add($nomeGroup);
        
        // Email field group
        $emailGroup = new TElement('div');
        $emailGroup->class = 'field-group';
        $emailLabel = new TElement('label');
        $emailLabel->add('E-mail *');
        $emailGroup->add($emailLabel);
        $emailGroup->add($email);
        $step1Fields->add($emailGroup);
        
        $step1->add($step1Fields);
        $body->add($step1);
        
        // Step 2 Content
        $step2 = new TElement('div');
        $step2->class = 'wizard-step';
        $step2->id = 'wizard-step-2';
        
        $step2Title = new TElement('div');
        $step2Title->class = 'step-title';
        $step2Title->add('<h2>Acesso & Projeto</h2><p>Configure as credenciais e vincule a um projeto</p>');
        $step2->add($step2Title);
        
        $step2Fields = new TElement('div');
        $step2Fields->class = 'wizard-fields';
        
        // Credentials info
        $credInfo = new TElement('div');
        $credInfo->class = 'info-box';
        $credInfo->add('<i class="fa fa-info-circle"></i> O login e senha serão gerados automaticamente ao salvar o cadastro.');
        $step2Fields->add($credInfo);
        
        // Login field group
        $loginGroup = new TElement('div');
        $loginGroup->class = 'field-group';
        $loginLabel = new TElement('label');
        $loginLabel->add('Login (gerado automaticamente)');
        $loginGroup->add($loginLabel);
        $loginGroup->add($login);
        $step2Fields->add($loginGroup);
        
        // Senha field group
        $senhaGroup = new TElement('div');
        $senhaGroup->class = 'field-group';
        $senhaLabel = new TElement('label');
        $senhaLabel->add('Senha (gerada automaticamente)');
        $senhaGroup->add($senhaLabel);
        $senhaGroup->add($senha);
        $step2Fields->add($senhaGroup);
        
        // Company field group
        $empresaGroup = new TElement('div');
        $empresaGroup->class = 'field-group';
        $empresaLabel = new TElement('label');
        $empresaLabel->add('Empresa (Opcional)');
        $empresaGroup->add($empresaLabel);
        $empresaGroup->add($empresa_id);
        $step2Fields->add($empresaGroup);
        
        // Project field group
        $projetoGroup = new TElement('div');
        $projetoGroup->class = 'field-group';
        $projetoLabel = new TElement('label');
        $projetoLabel->add('Projeto Vinculado (Opcional)');
        $projetoGroup->add($projetoLabel);
        $projetoGroup->add($projetos);
        
        // Info about filtering
        $projetoInfo = new TElement('div');
        $projetoInfo->class = 'info-box';
        $projetoInfo->add('<i class="fa fa-info-circle"></i> Selecione uma empresa para filtrar os projetos disponíveis.');
        $projetoGroup->add($projetoInfo);
        
        $step2Fields->add($projetoGroup);
        
        // Status field group
        $statusGroup = new TElement('div');
        $statusGroup->class = 'field-group';
        $statusLabel = new TElement('label');
        $statusLabel->add('Status do Cliente');
        $statusGroup->add($statusLabel);
        $statusGroup->add($ativo);
        $step2Fields->add($statusGroup);
        
        $step2->add($step2Fields);
        $body->add($step2);
        
        $html->add($body);
        
        // Wizard Footer with Navigation
        $footer = new TElement('div');
        $footer->class = 'wizard-footer';
        
        $btnBack = new TElement('button');
        $btnBack->type = 'button';
        $btnBack->class = 'wizard-btn wizard-btn-back';
        $btnBack->id = 'wizard-back';
        $btnBack->onclick = 'wizardPrev()';
        $btnBack->add('<i class="fa fa-arrow-left"></i> Voltar');
        
        $btnNext = new TElement('button');
        $btnNext->type = 'button';
        $btnNext->class = 'wizard-btn wizard-btn-next';
        $btnNext->id = 'wizard-next';
        $btnNext->onclick = 'wizardNext()';
        $btnNext->add('Próximo <i class="fa fa-arrow-right"></i>');
        
        $btnSave = new TButton('btn_save');
        $btnSave->setAction(new TAction([$this, 'onSave']), 'Salvar Cliente');
        $btnSave->setImage('fa:check white');
        $btnSave->class = 'wizard-btn wizard-btn-save';
        $btnSave->id = 'wizard-save';
        $btnSave->style = 'display: none;';
        
        $btnCancel = new TElement('a');
        $btnCancel->href = 'index.php?class=ClienteList';
        $btnCancel->class = 'wizard-btn wizard-btn-cancel';
        $btnCancel->add('<i class="fa fa-times"></i> Cancelar');
        
        $footer->add($btnCancel);
        $footer->add($btnBack);
        $footer->add($btnNext);
        $footer->add($btnSave);
        
        $html->add($footer);
        
        // Add hidden fields
        $this->form->add($id);
        $this->form->add($current_step);
        $this->form->add($html);
        
        // Register form fields
        $this->form->setFields([$id, $current_step, $nome, $email, $login, $senha, $empresa_id, $projetos, $ativo, $btnSave]);
        
        // Wizard JavaScript
        $script = new TElement('script');
        $script->add('
            var currentStep = 1;
            var totalSteps = 2;
            
            function updateWizard() {
                // Update steps
                document.querySelectorAll(".wizard-step").forEach(function(step, index) {
                    step.classList.remove("active");
                    if (index + 1 === currentStep) {
                        step.classList.add("active");
                    }
                });
                
                // Update stepper
                document.querySelectorAll(".wizard-stepper .step").forEach(function(step, index) {
                    step.classList.remove("active", "completed");
                    if (index + 1 === currentStep) {
                        step.classList.add("active");
                    } else if (index + 1 < currentStep) {
                        step.classList.add("completed");
                    }
                });
                
                // Update buttons
                document.getElementById("wizard-back").style.display = currentStep === 1 ? "none" : "inline-flex";
                document.getElementById("wizard-next").style.display = currentStep === totalSteps ? "none" : "inline-flex";
                document.getElementById("wizard-save").style.display = currentStep === totalSteps ? "inline-flex" : "none";
            }
            
            function wizardNext() {
                if (currentStep < totalSteps) {
                    currentStep++;
                    updateWizard();
                }
            }
            
            function wizardPrev() {
                if (currentStep > 1) {
                    currentStep--;
                    updateWizard();
                }
            }
            
            document.addEventListener("DOMContentLoaded", function() {
                updateWizard();
            });
        ');
        
        $container = new TVBox;
        $container->style = 'width: 100%';
        try {
            $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        } catch (Exception $e) {
            // ignore if not in menu
        }
        $container->add($this->form);
        $container->add($script);
        
        parent::add($container);
    }
    
    public static function onChangeEmpresa($param)
    {
        if (!empty($param['empresa_id'])) {
            try {
                TTransaction::open('database');
                
                // Load PROJECT TEMPLATES from selected company
                $projetos = Projeto::where('company_template_id', '=', $param['empresa_id'])
                                   ->where('ativo', '=', 1)
                                   ->where('is_template', '=', '1') // Only Templates
                                   ->load();
                
                $options = [];
                if ($projetos) {
                    foreach ($projetos as $projeto) {
                        $options[$projeto->id] = $projeto->nome;
                    }
                }
                
                // Reload the combo
                TCombo::reload('form_cliente', 'projetos', $options);
                
                TTransaction::close();
            } catch (Exception $e) {
                new TMessage('error', $e->getMessage());
            }
        } else {
            // If no company selected, load GLOBAL templates (no company)
            try {
                TTransaction::open('database');
                $projetos = Projeto::where('company_template_id', 'IS', NULL)
                                   ->where('ativo', '=', 1)
                                   ->where('is_template', '=', '1')
                                   ->load();
                
                $options = [];
                if ($projetos) {
                    foreach ($projetos as $projeto) {
                        $options[$projeto->id] = $projeto->nome . ' (Geral)';
                    }
                }
                TCombo::reload('form_cliente', 'projetos', $options);
                TTransaction::close();
            } catch (Exception $e) {
                new TMessage('error', $e->getMessage());
            }
        }
    }
    
    public function onEdit($param)
    {
        try {
            if (isset($param['id'])) {
                TTransaction::open('database');
                
                $usuario = new Usuario($param['id']);
                $data = $usuario->toArray();
                
                // Load linked project (INSTANCE)
                $vinculado = ClienteProjeto::where('cliente_id', '=', $usuario->id)->first();
                
                if ($vinculado) {
                    // We need to show the TEMPLATE that originated this instance, if possible.
                    // But we don't store "origin_template_id" on Projeto instance (we could, but currently we don't).
                    // So we can only show the INSTANCE name or just leave it blank if editing?
                    // "projetos" field is essentially "Template to Apply".
                    // If already applied, maybe we shouldn't show it selected?
                    // Or we show the instance? 
                    // Let's assume for now we don't re-select the template on Edit to avoid re-cloning.
                    //$data['projetos'] = $vinculado->projeto_id; 
                }
                
                // Load empresa from project if exists
                if ($vinculado && $vinculado->projeto_id) {
                    $projeto = new Projeto($vinculado->projeto_id);
                    $data['empresa_id'] = $projeto->company_template_id;
                }
                
                // Convert ativo to string for radio
                $data['ativo'] = $usuario->ativo ? '1' : '0';
                
                // Do not show the hashed password
                unset($data['senha']);
                $this->form->setData((object) $data);
                
                TTransaction::close();
            } else {
                $this->form->setData(new stdClass);
            }
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }
    
    public function onSave($param)
    {
        try {
            TTransaction::open('database');
            
            $usuario = new Usuario;
            
            if (!empty($param['id'])) {
                $usuario = new Usuario($param['id']);
            } else {
                // Generate login and password
                $login_base = strtolower(substr($param['nome'], 0, 5));
                $login_base = preg_replace('/[^a-z]/', '', $login_base);
                $login = $login_base . rand(100, 999);
                
                $senha_gerada = $this->gerarSenha(8);
                
                $usuario->login = $login;
                $usuario->senha = password_hash($senha_gerada, PASSWORD_DEFAULT);
                $usuario->tipo = 'cliente';
                
                $param['login'] = $login;
                $param['senha'] = $senha_gerada;
            }
            
            $usuario->nome = $param['nome'];
            $usuario->email = $param['email'];
            $usuario->ativo = (!empty($param['ativo']) && $param['ativo'] !== '0') ? 1 : 0;
            $usuario->store();
            
            // Check if user selected a Template to Clone
            $hasLink = false;
            if (!empty($usuario->id)) {
                $hasLink = ClienteProjeto::where('cliente_id', '=', $usuario->id)->count() > 0;
            }

            // Allow assignment if project selected AND (user is new OR user has no project yet)
            if (!empty($param['projetos']) && !$hasLink) { 
                $template_id = $param['projetos'];
                $template = new Projeto($template_id);
                
                // CLONE PROJECT (Create Instance)
                $instance = new Projeto;
                $instance->nome = $template->nome . " - " . $usuario->nome;
                $instance->descricao = $template->descricao;
                $instance->company_template_id = $template->company_template_id;
                $instance->dia_vencimento = $template->dia_vencimento;
                $instance->ativo = 1;
                $instance->is_template = '0'; // It's an instance
                $instance->created_at = date('Y-m-d H:i:s');
                $instance->store();
                
                // CLONE DOCUMENTS
                $docs = ProjetoDocumento::where('projeto_id', '=', $template_id)->load();
                if ($docs) {
                    foreach ($docs as $doc) {
                        $newDoc = new ProjetoDocumento;
                        $newDoc->projeto_id = $instance->id;
                        $newDoc->nome_documento = $doc->nome_documento;
                        $newDoc->obrigatorio = $doc->obrigatorio;
                        $newDoc->status = 'pendente';
                        $newDoc->store();
                    }
                }
                
                // Link Client to Instance
                $vinculo = new ClienteProjeto;
                $vinculo->cliente_id = $usuario->id;
                $vinculo->projeto_id = $instance->id;
                $vinculo->store();
            }
            // If editing, we generally keep existing link.
            
            TTransaction::close();
            
            if (isset($senha_gerada)) {
                $msg = "Cliente salvo com sucesso!\n\n";
                $msg .= "Login: {$param['login']}\n";
                $msg .= "Senha: {$senha_gerada}\n\n";
                $msg .= "Anote estas informações!";
                new TMessage('info', $msg);
                
                // Show the plain password in the form
                $data = new stdClass;
                $data->id = $usuario->id;
                $data->nome = $usuario->nome;
                $data->email = $usuario->email;
                $data->login = $usuario->login;
                $data->senha = $senha_gerada;
                $data->ativo = $usuario->ativo ? '1' : '0';
                $data->projetos = $param['projetos'] ?? null;
                
                $this->form->setData($data);
            } else {
                new TMessage('info', 'Cliente atualizado com sucesso');
                TApplication::gotoPage('ClienteList');
            }
            
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
    
    private function gerarSenha($length = 8)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $senha = '';
        for ($i = 0; $i < $length; $i++) {
            $senha .= $chars[rand(0, strlen($chars) - 1)];
        }
        return $senha;
    }
}
