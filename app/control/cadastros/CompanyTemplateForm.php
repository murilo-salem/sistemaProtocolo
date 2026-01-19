<?php
class CompanyTemplateForm extends TPage
{
    protected $form;
    protected $detail_list;
    
    public function __construct()
    {
        parent::__construct();
        
        $this->form = new BootstrapFormBuilder('form_company_template');
        $this->form->setFormTitle('Cadastro de Empresa');
        
        $id = new THidden('id');
        $name = new TEntry('name');
        $name->setSize('100%');
        $name->addValidation('Nome', new TRequiredValidator);
        
        // Detail List using TFieldList (correct component for Adianti 7/8)
        $this->detail_list = new TFieldList;
        $this->detail_list->width = '100%';
        $this->detail_list->setName('detail_list');
        $this->detail_list->enableSorting();
        
        $doc_name = new TEntry('document_name[]');
        $doc_name->setSize('100%');
        
        $this->detail_list->addField('Nome do Documento', $doc_name, ['width' => '100%']);
        $this->detail_list->addHeader();
        $this->detail_list->addDetail(new stdClass);
        $this->detail_list->addCloneAction();
        
        $this->form->addFields([$id]);
        $this->form->addFields([new TLabel('Nome da Empresa*')], [$name]);
        $this->form->addFields([new TLabel('Documentos Exigidos')], [$this->detail_list]);
        
        $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fa:save green');
        $this->form->addAction('Voltar', new TAction(['CompanyTemplateList', 'onReload']), 'fa:arrow-left blue');
        
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add($this->form);
        
        parent::add($container);
    }
    
    public function onEdit($param)
    {
        try {
            if (isset($param['id'])) {
                TTransaction::open('database');
                $object = new CompanyTemplate($param['id']);
                
                $this->form->setData($object);
                
                // Clear default row
                TFieldList::clear('detail_list');
                
                // Load details
                $items = CompanyDocTemplate::where('company_template_id', '=', $object->id)->load();
                
                if ($items) {
                    foreach ($items as $item) {
                        $detail = new stdClass;
                        $detail->document_name = $item->document_name;
                        $this->detail_list->addDetail($detail);
                    }
                } else {
                    $this->detail_list->addDetail(new stdClass);
                }
                $this->detail_list->addCloneAction();
                
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
            
            $this->form->validate();
            $data = $this->form->getData();
            
            $object = new CompanyTemplate;
            $object->fromArray((array) $data);
            $object->store();
            
            // Delete old documents
            CompanyDocTemplate::where('company_template_id', '=', $object->id)->delete();
            
            // Insert new documents
            if (!empty($param['document_name'])) {
                $doc_names = is_array($param['document_name']) ? $param['document_name'] : [$param['document_name']];
                foreach ($doc_names as $doc_name) {
                    if (!empty(trim($doc_name))) {
                        $detail = new CompanyDocTemplate;
                        $detail->company_template_id = $object->id;
                        $detail->document_name = trim($doc_name);
                        $detail->is_required = 1;
                        $detail->store();
                    }
                }
            }
            
            $data->id = $object->id;
            $this->form->setData($data);
            
            TTransaction::close();
            
            new TMessage('info', 'Empresa salva com sucesso');
            
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
}
