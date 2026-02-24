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
        
        // Step 2: Project Templates DataGrid (For existing companies)
        $this->documentos_list = new TDataGrid;
        $this->documentos_list->style = 'width: 100%; margin-bottom: 20px';
        $this->documentos_list->id = 'datagrid_modelos';
        
        $col_id = new TDataGridColumn('id', 'ID', 'center', '50px');
        $col_nome = new TDataGridColumn('nome', 'Nome do Modelo', 'left');
        $col_docs_count = new TDataGridColumn('total_docs', 'Qtd', 'center', '50px');
        $col_docs_summary = new TDataGridColumn('docs_summary', 'Lista de Documentos', 'left');
        
        $this->documentos_list->addColumn($col_id);
        $this->documentos_list->addColumn($col_nome);
        $this->documentos_list->addColumn($col_docs_count);
        $this->documentos_list->addColumn($col_docs_summary);
        
        $action_edit = new TDataGridAction(['ProjetoForm', 'onEdit']);
        $action_edit->setLabel('Editar Modelo');
        $action_edit->setImage('fa:edit blue');
        $action_edit->setField('id');
        $this->documentos_list->addAction($action_edit);
        
        $this->documentos_list->createModel();

        // Step 2: TFieldList for NEW companies (dynamic in-memory templates)
        $this->modelos_list_new = new TFieldList;
        $this->modelos_list_new->generateAria();
        $this->modelos_list_new->width = '100%';
        $this->modelos_list_new->id = 'fieldlist_modelos_novos';

        $modelo_nome = new TEntry('modelo_nome[]');
        $modelo_nome->setSize('100%');
        $modelo_nome->setProperty('placeholder', 'Nome do projeto');

        $modelo_desc = new TEntry('modelo_desc[]');
        $modelo_desc->setSize('100%');
        $modelo_desc->setProperty('placeholder', 'Descrição');

        $modelo_venc = new TSpinner('modelo_venc[]');
        $modelo_venc->setSize('100%');
        $modelo_venc->setRange(1, 31, 1);
        $modelo_venc->setValue(10);

        $modelo_docs = new TEntry('modelo_docs[]');
        $modelo_docs->setSize('100%');
        $modelo_docs->setProperty('placeholder', 'RG, CNH, Comprovante... (separado por vírgula)');

        $this->modelos_list_new->addField('Nome do Modelo', $modelo_nome, ['width' => '25%']);
        $this->modelos_list_new->addField('Descrição', $modelo_desc, ['width' => '25%']);
        $this->modelos_list_new->addField('Vencimento', $modelo_venc, ['width' => '15%']);
        $this->modelos_list_new->addField('Documentos', $modelo_docs, ['width' => '35%']);
        $this->modelos_list_new->addHeader();
        $this->modelos_list_new->addDetail(new stdClass);
        $this->modelos_list_new->addCloneAction();
        
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
        $addBtn->add('<i class="fa fa-plus"></i> Novo Modelo Avançado');
        $step2Fields->add($addBtn);
        
        $wrapperNovos = new TElement('div');
        $wrapperNovos->id = 'wrapper_modelos_novos';
        $titleNovos = new TElement('h5');
        $titleNovos->add('Modelos Iniciais (Serão criados junto com a empresa)');
        $wrapperNovos->add($titleNovos);
        $wrapperNovos->add($this->modelos_list_new);
        $step2Fields->add($wrapperNovos);
        
        // List from DB (hidden on create)
        $wrapperGrid = new TElement('div');
        $wrapperGrid->id = 'wrapper_modelos_salvos';
        $wrapperGrid->style = 'display:none;';
        $wrapperGrid->add($this->documentos_list);
        $step2Fields->add($wrapperGrid);
        
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
        $this->form->setFields([$id, $current_step, $nome, $modelo_nome, $modelo_desc, $modelo_venc, $modelo_docs, $btnSave]);
        
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
                
                // Setup "Add Template" button & layout for EXSITING
                TScript::create("
                    document.getElementById('btn-add-template').style.display = 'inline-block';
                    document.getElementById('btn-add-template').href = 'index.php?class=ProjetoForm&company_template_id={$empresa->id}&is_template=1';
                    document.getElementById('wrapper_modelos_novos').style.display = 'none';
                    document.getElementById('wrapper_modelos_salvos').style.display = 'block';
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
            
            // Handle in-memory templates from TFieldList (New models)
            if (!empty($param['modelo_nome']) && is_array($param['modelo_nome'])) {
                $nomes = $param['modelo_nome'];
                $descs = $param['modelo_desc'] ?? [];
                $vencs = $param['modelo_venc'] ?? [];
                $docs  = $param['modelo_docs'] ?? [];
                
                for ($i = 0; $i < count($nomes); $i++) {
                    if (!empty(trim($nomes[$i]))) {
                        // Create Project Template
                        $proj = new Projeto;
                        $proj->nome = trim($nomes[$i]);
                        $proj->descricao = trim($descs[$i] ?? '');
                        $proj->company_template_id = $empresa->id;
                        $proj->dia_vencimento = (int) ($vencs[$i] ?? 10);
                        $proj->is_template = 1;
                        $proj->ativo = 1;
                        $proj->created_at = date('Y-m-d H:i:s');
                        $proj->store();
                        
                        // Parse Documents (comma-separated list)
                        $str_docs = $docs[$i] ?? '';
                        if (!empty(trim($str_docs))) {
                            $doc_names = explode(',', $str_docs);
                            foreach ($doc_names as $doc_name) {
                                if (!empty(trim($doc_name))) {
                                    $pd = new ProjetoDocumento;
                                    $pd->projeto_id = $proj->id;
                                    $pd->nome_documento = trim($doc_name);
                                    $pd->obrigatorio = 1;
                                    $pd->status = 'pendente';
                                    $pd->store();
                                }
                            }
                        }
                    }
                }
            }
            
            TTransaction::close();

            $action = new TAction(['EmpresaList', 'onReload']);
            new TMessage('info', 'Empresa e modelos (se houver) salvos com sucesso!', $action);

        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
}
