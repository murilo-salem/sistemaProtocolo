<?php

class ProjetoForm extends TPage
{
    protected $form;
    protected $documentos_list;
    
    public function __construct()
    {
        parent::__construct();
        
        // Create form
        $this->form = new TForm('form_projeto');
        
        // Hidden fields
        $id = new THidden('id');
        $current_step = new THidden('current_step');
        $current_step->setValue('1');
        
        // Step 1: Basic Data
        $nome = new TEntry('nome');
        $nome->setProperty('placeholder', 'Digite o nome do projeto');
        $nome->setSize('100%');
        $nome->addValidation('Nome', new TRequiredValidator);
        
        $descricao = new TText('descricao');
        $descricao->setProperty('placeholder', 'Descreva brevemente o projeto...');
        $descricao->setSize('100%', 100);
        
        // Step 2: Configuration
        $company_template_id = new TDBCombo('company_template_id', 'database', 'CompanyTemplate', 'id', 'name');
        $company_template_id->setSize('100%');
        $company_template_id->setDefaultOption('Selecione um modelo...');
        $company_template_id->setChangeAction(new TAction([$this, 'onChangeCompany']));
        
        $dia_vencimento = new TSpinner('dia_vencimento');
        $dia_vencimento->setSize('100%');
        $dia_vencimento->setRange(1, 31, 1);
        $dia_vencimento->setValue(10);
        $dia_vencimento->addValidation('Dia Vencimento', new TRequiredValidator);
        
        $ativo = new TRadioGroup('ativo');
        $ativo->setUseButton();
        $ativo->addItems(['1' => 'Ativo', '0' => 'Inativo']);
        $ativo->setValue('1');
        $ativo->setValue('1');
        $ativo->setLayout('horizontal');

        $is_template = new TRadioGroup('is_template');
        $is_template->setUseButton();
        $is_template->addItems(['1' => 'Sim', '0' => 'Não']);
        $is_template->setValue('1'); // Default to Template
        $is_template->setLayout('horizontal');
        
        // Document list
        $this->documentos_list = new TFieldList;
        $this->documentos_list->generateAria();
        $this->documentos_list->width = '100%';
        //$this->documentos_list->disableSorting(); // Removed undefined method
        //$this->documentos_list->setName('documentos_list'); // Removing name to avoid custom tag wrappers if any

        $this->doc_nome = new TEntry('nome_doc[]');
        $this->doc_nome->setProperty('placeholder', 'Nome do documento');
        $this->doc_nome->setSize('100%');
        
        $this->documentos_list->addField('Nome do Documento', $this->doc_nome, ['width' => '100%']);
        $this->documentos_list->addHeader();

        if (empty($_REQUEST['id'])) {
            $this->documentos_list->addDetail(new stdClass);
            $this->documentos_list->addCloneAction();
        }
        
        // Build wizard HTML structure
        $html = new TElement('div');
        $html->class = 'wizard-container';
        
        $style = new TElement('style');
        $style->add('
            .wizard-step {
                position: absolute;
                top: 0;
                left: -9999px;
                opacity: 0;
                width: 100%;
                transition: opacity 0.3s;
            }
            .wizard-step.active {
                position: relative;
                left: 0;
                opacity: 1;
            }
        ');
        $html->add($style);
        
        // Wizard Header with Stepper
        $header = new TElement('div');
        $header->class = 'wizard-header';
        
        $stepper = new TElement('div');
        $stepper->class = 'wizard-stepper';
        $stepper->add('
            <div class="step active" data-step="1">
                <div class="step-number">1</div>
                <div class="step-label">Dados Básicos</div>
            </div>
            <div class="step-line"></div>
            <div class="step" data-step="2">
                <div class="step-number">2</div>
                <div class="step-label">Configurações</div>
            </div>
            <div class="step-line"></div>
            <div class="step" data-step="3">
                <div class="step-number">3</div>
                <div class="step-label">Documentos</div>
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
        $step1Title->add('<h2>Dados Básicos</h2><p>Informe os dados principais do projeto</p>');
        $step1->add($step1Title);
        
        $step1Fields = new TElement('div');
        $step1Fields->class = 'wizard-fields';
        
        // Nome field group
        $nomeGroup = new TElement('div');
        $nomeGroup->class = 'field-group';
        $nomeLabel = new TElement('label');
        $nomeLabel->add('Nome do Projeto *');
        $nomeGroup->add($nomeLabel);
        $nomeGroup->add($nome);
        $step1Fields->add($nomeGroup);
        
        // Descricao field group
        $descGroup = new TElement('div');
        $descGroup->class = 'field-group';
        $descLabel = new TElement('label');
        $descLabel->add('Descrição');
        $descGroup->add($descLabel);
        $descGroup->add($descricao);
        $step1Fields->add($descGroup);
        
        $step1->add($step1Fields);
        $body->add($step1);
        
        // Step 2 Content
        $step2 = new TElement('div');
        $step2->class = 'wizard-step';
        $step2->id = 'wizard-step-2';
        
        $step2Title = new TElement('div');
        $step2Title->class = 'step-title';
        $step2Title->add('<h2>Configurações</h2><p>Defina as configurações do projeto</p>');
        $step2->add($step2Title);
        
        $step2Fields = new TElement('div');
        $step2Fields->class = 'wizard-fields';
        
        // Template field group
        $templateGroup = new TElement('div');
        $templateGroup->class = 'field-group';
        $templateLabel = new TElement('label');
        $templateLabel->add('Tipo de Empresa / Modelo');
        $templateGroup->add($templateLabel);
        $templateGroup->add($company_template_id);
        $step2Fields->add($templateGroup);
        
        // Vencimento field group
        $vencGroup = new TElement('div');
        $vencGroup->class = 'field-group';
        $vencLabel = new TElement('label');
        $vencLabel->add('Dia de Vencimento *');
        $vencGroup->add($vencLabel);
        $vencGroup->add($dia_vencimento);
        
        // Quick day suggestions
        $daySuggestions = new TElement('div');
        $daySuggestions->class = 'field-suggestions';
        $daySuggestions->add('
            <span class="suggestion-chip" onclick="document.querySelector(\'[name=dia_vencimento]\').value=5">Dia 5</span>
            <span class="suggestion-chip" onclick="document.querySelector(\'[name=dia_vencimento]\').value=10">Dia 10</span>
            <span class="suggestion-chip" onclick="document.querySelector(\'[name=dia_vencimento]\').value=15">Dia 15</span>
            <span class="suggestion-chip" onclick="document.querySelector(\'[name=dia_vencimento]\').value=20">Dia 20</span>
        ');
        $vencGroup->add($daySuggestions);
        $step2Fields->add($vencGroup);
        
        // Status field group
        $statusGroup = new TElement('div');
        $statusGroup->class = 'field-group';
        $statusLabel = new TElement('label');
        $statusLabel->add('Status do Projeto');
        $statusGroup->add($statusLabel);
        $statusGroup->add($ativo);
        $step2Fields->add($statusGroup);

        // Is Template field group
        $templateFlagGroup = new TElement('div');
        $templateFlagGroup->class = 'field-group';
        $templateFlagLabel = new TElement('label');
        $templateFlagLabel->add('É um Modelo de Projeto?');
        $templateFlagGroup->add($templateFlagLabel);
        $templateFlagGroup->add($is_template);
        $step2Fields->add($templateFlagGroup);
        
        $step2->add($step2Fields);
        $body->add($step2);
        
        // Step 3 Content
        $step3 = new TElement('div');
        $step3->class = 'wizard-step';
        $step3->id = 'wizard-step-3';
        
        $step3Title = new TElement('div');
        $step3Title->class = 'step-title';
        $step3Title->add('<h2>Documentos</h2><p>Defina os documentos necessários para este projeto</p>');
        $step3->add($step3Title);
        
        $step3Fields = new TElement('div');
        $step3Fields->class = 'wizard-fields';
        
        $docGroup = new TElement('div');
        $docGroup->class = 'field-group';
        $docLabel = new TElement('label');
        $docLabel->add('Lista de Documentos Obrigatórios');
        $docGroup->add($docLabel);
        $docGroup->add($this->documentos_list);
        $step3Fields->add($docGroup);
        
        $step3->add($step3Fields);
        $body->add($step3);
        
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
        $btnSave->setAction(new TAction([$this, 'onSave']), 'Salvar Projeto');
        $btnSave->setImage('fa:check white');
        $btnSave->class = 'wizard-btn wizard-btn-save';
        $btnSave->id = 'wizard-save';
        $btnSave->style = 'display: none;';
        
        $btnCancel = new TElement('a');
        $btnCancel->href = 'index.php?class=ProjetoList';
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
        $this->form->setFields([$id, $current_step, $nome, $descricao, $company_template_id, $dia_vencimento, $ativo, $is_template, $this->doc_nome, $btnSave]);
        
        // Wizard JavaScript
        $script = new TElement('script');
        $script->add('
            var currentStep = 1;
            var totalSteps = 3;
            
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
        $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add($this->form);
        $container->add($script);
        
        parent::add($container);
    }
    
    public static function onChangeCompany($param)
    {
        if (!empty($param['company_template_id'])) {
            try {
                TTransaction::open('database');
                
                $docs = CompanyDocTemplate::where('company_template_id', '=', $param['company_template_id'])->load();
                
                TScript::create("tfieldlist_clear('documentos_list');");
                
                if ($docs) {
                    foreach ($docs as $doc) {
                       $data = new stdClass;
                       $data->nome_doc = $doc->document_name;
                       $json_data = json_encode($data);
                       TScript::create("tfieldlist_add_row('documentos_list', $json_data);");
                    }
                }
                
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
                
                $projeto = new Projeto($param['id']);
                
                $data = $projeto->toArray();
                
                $items = ProjetoDocumento::where('projeto_id', '=', $projeto->id)->load();
                
                if ($items) {
                    foreach ($items as $item_obj) {
                        $item = new stdClass;
                        $item->nome_doc = $item_obj->nome_documento;
                        $this->documentos_list->addDetail($item);
                    }
                } else {
                    $this->documentos_list->addDetail(new stdClass);
                }
                
                $this->documentos_list->addCloneAction();

                $data['ativo'] = ($projeto->ativo == 1) ? '1' : '0';
                $data['is_template'] = ($projeto->is_template == 1) ? '1' : '0';
                
                $this->form->setData((object) $data);
                
                TTransaction::close();
            }
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }
    
    public function onSave($param)
    {
        try {
            TTransaction::open('database');

            $projeto = new Projeto;
            $projeto->fromArray((array) $param);
            
            $projeto->ativo = (!empty($param['ativo']) && $param['ativo'] !== '0') ? 1 : 0;
            $projeto->is_template = (!empty($param['is_template']) && $param['is_template'] !== '0') ? 1 : 0;

            $projeto->store();
            
            ProjetoDocumento::where('projeto_id', '=', $projeto->id)->delete();
            
            $nome_docs = [];
            if (isset($param['nome_doc'])) {
                if (is_array($param['nome_doc'])) {
                    $nome_docs = $param['nome_doc'];
                } else {
                    $nome_docs = [$param['nome_doc']];
                }
            }
            
            foreach ($nome_docs as $doc_name) {
                if (!empty(trim($doc_name))) {
                    $detail = new ProjetoDocumento;
                    $detail->projeto_id = $projeto->id;
                    $detail->nome_documento = trim($doc_name);
                    $detail->obrigatorio = 1; 
                    $detail->status = 'pendente';
                    $detail->store();
                }
            }

            TTransaction::close();

            new TMessage('info', 'Projeto salvo com sucesso!');
            TApplication::gotoPage('ProjetoList');

        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

}
