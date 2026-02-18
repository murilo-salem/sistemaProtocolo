<?php

class EmpresaForm extends TPage
{
    protected $form;
    protected $documentos_list;
    
    public function __construct()
    {
        parent::__construct();
        
        // Create form
        $this->form = new TForm('form_empresa');
        
        // Hidden fields
        $id = new THidden('id');
        $current_step = new THidden('current_step');
        $current_step->setValue('1');
        
        // Step 1: Basic Data
        $nome = new TEntry('name');
        $nome->setProperty('placeholder', 'Digite o nome da empresa');
        $nome->setSize('100%');
        $nome->addValidation('Nome', new TRequiredValidator);
        
        // Step 2: Project Templates
        $this->documentos_list = new TDataGrid;
        $this->documentos_list->style = 'width: 100%; margin-bottom: 20px';
        
        $col_id = new TDataGridColumn('id', 'ID', 'center', '50px');
        $col_nome = new TDataGridColumn('nome', 'Nome do Modelo', 'left');
        $col_docs_count = new TDataGridColumn('total_docs', 'Qtd', 'center', '50px');
        $col_docs_summary = new TDataGridColumn('docs_summary', 'Lista de Documentos', 'left');
        
        $this->documentos_list->addColumn($col_id);
        $this->documentos_list->addColumn($col_nome);
        $this->documentos_list->addColumn($col_docs_count);
        $this->documentos_list->addColumn($col_docs_summary);
        
        // Actions
        $action_edit = new TDataGridAction(['ProjetoForm', 'onEdit']);
        $action_edit->setLabel('Editar Modelo');
        $action_edit->setImage('fa:edit blue');
        $action_edit->setField('id');
        $this->documentos_list->addAction($action_edit);
        
        $this->documentos_list->createModel();
        
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
                <div class="step-label">Dados da Empresa</div>
            </div>
            <div class="step-line"></div>
            <div class="step" data-step="2">
                <div class="step-number">2</div>
                <div class="step-label">Modelos de Projetos</div>
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
        $step1Title->add('<h2>Dados da Empresa</h2><p>Informe o nome da empresa/template</p>');
        $step1->add($step1Title);
        
        $step1Fields = new TElement('div');
        $step1Fields->class = 'wizard-fields';
        
        // Nome field group
        $nomeGroup = new TElement('div');
        $nomeGroup->class = 'field-group';
        $nomeLabel = new TElement('label');
        $nomeLabel->add('Nome da Empresa *');
        $nomeGroup->add($nomeLabel);
        $nomeGroup->add($nome);
        
        // Suggestion chips
        $suggestions = new TElement('div');
        $suggestions->class = 'field-suggestions';
        $suggestions->add('
            <span class="suggestion-chip" onclick="document.querySelector(\'[name=name]\').value=\'Recursos Humanos\'">Recursos Humanos</span>
            <span class="suggestion-chip" onclick="document.querySelector(\'[name=name]\').value=\'TI e Desenvolvimento\'">TI e Desenvolvimento</span>
            <span class="suggestion-chip" onclick="document.querySelector(\'[name=name]\').value=\'Administrativo\'">Administrativo</span>
        ');
        $nomeGroup->add($suggestions);
        
        $step1Fields->add($nomeGroup);
        
        $step1->add($step1Fields);
        $body->add($step1);
        
        // Step 2 Content
        $step2 = new TElement('div');
        $step2->class = 'wizard-step';
        $step2->id = 'wizard-step-2';
        
        $step2Title = new TElement('div');
        $step2Title->class = 'step-title';
        $step2Title->add('<h2>Modelos de Projetos</h2><p>Gerencie os modelos de projetos vinculados a esta empresa</p>');
        $step2->add($step2Title);
        
        $step2Fields = new TElement('div');
        $step2Fields->class = 'wizard-fields';
        
        // Info Box
        $infoBox = new TElement('div');
        $infoBox->class = 'info-box';
        $infoBox->add('<i class="fa fa-info-circle"></i> Aqui você define os tipos de projetos (Modelos) que esta empresa oferece. Cada modelo terá sua própria lista de documentos.');
        $step2Fields->add($infoBox);
        
        // Add Button (Only visible if saved)
        $addBtn = new TElement('a');
        $addBtn->class = 'btn btn-primary';
        $addBtn->style = 'display:none; margin-bottom: 15px;';
        $addBtn->id = 'btn-add-template';
        $addBtn->href = '#';
        $addBtn->add('<i class="fa fa-plus"></i> Novo Modelo');
        $step2Fields->add($addBtn);
        
        $msgSave = new TElement('div');
        $msgSave->id = 'msg-save-first';
        $msgSave->style = 'padding: 20px; text-align: center; color: #666; background: #f9fafb; border-radius: 8px; border: 1px dashed #ccc;';
        $msgSave->add('Salve a empresa primeiro para adicionar modelos.');
        $step2Fields->add($msgSave);
        
        // List
        $step2Fields->add($this->documentos_list);
        
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
        $btnSave->setAction(new TAction([$this, 'onSave']), 'Salvar Empresa');
        $btnSave->setImage('fa:check white');
        $btnSave->class = 'wizard-btn wizard-btn-save';
        $btnSave->id = 'wizard-save';
        $btnSave->style = 'display: none;';
        
        $btnCancel = new TElement('a');
        $btnCancel->href = 'index.php?class=EmpresaList';
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
        $this->form->setFields([$id, $current_step, $nome, $btnSave]);
        
        // Wizard JavaScript
        $script = new TElement('script');
        $script->add('
            var currentStep = 1;
            var totalSteps = 2;
            
            function updateWizard() {
                document.querySelectorAll(".wizard-step").forEach(function(step, index) {
                    step.classList.remove("active");
                    if (index + 1 === currentStep) {
                        step.classList.add("active");
                    }
                });
                
                document.querySelectorAll(".wizard-stepper .step").forEach(function(step, index) {
                    step.classList.remove("active", "completed");
                    if (index + 1 === currentStep) {
                        step.classList.add("active");
                    } else if (index + 1 < currentStep) {
                        step.classList.add("completed");
                    }
                });
                
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
        $container->add(new TXMLBreadCrumb('menu-gestor.xml', __CLASS__));
        $container->add($this->form);
        $container->add($script);
        
        parent::add($container);
    }

    public function onEdit($param)
    {
        try {
            if (isset($param['id'])) {
                TTransaction::open('database');
                
                $empresa = new CompanyTemplate($param['id']);
                $data = $empresa->toArray();
                
                // Load Project Templates
                $templates = Projeto::where('company_template_id', '=', $empresa->id)
                                    ->where('is_template', '=', '1')
                                    ->load();
                
                // Add items to DataGrid
                $this->documentos_list->clear();
                if ($templates) {
                    foreach ($templates as $template) {
                        $item = new stdClass;
                        $item->id = $template->id;
                        $item->nome = $template->nome;
                        
                        // Count docs
                        $docs = ProjetoDocumento::where('projeto_id', '=', $template->id)->load();
                        $item->total_docs = count($docs);
                        
                        // Summary of docs
                        $doc_names = [];
                        if ($docs) {
                            foreach ($docs as $doc) {
                                $doc_names[] = $doc->nome_documento;
                            }
                        }
                        $item->docs_summary = implode(', ', $doc_names);
                        
                        $this->documentos_list->addItem($item);
                    }
                }
                
                // Setup "Add Template" button
                TScript::create("
                    document.getElementById('btn-add-template').style.display = 'inline-block';
                    document.getElementById('btn-add-template').href = 'index.php?class=ProjetoForm&company_template_id={$empresa->id}&is_template=1';
                    document.getElementById('msg-save-first').style.display = 'none';
                ");
                
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

            $empresa = new CompanyTemplate;
            $empresa->fromArray((array) $param);
            $empresa->store();
            
            // We NO LONGER save CompanyDocTemplate here.
            // Templates are managed in their own form.
            
            TTransaction::close();

            new TMessage('info', 'Empresa salva com sucesso! Agora você pode adicionar Modelos de Projeto na aba seguinte.');
            
            // Reload to show "Add Template" button
            TApplication::loadPage('EmpresaForm', 'onEdit', ['id' => $empresa->id]);

        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
}
