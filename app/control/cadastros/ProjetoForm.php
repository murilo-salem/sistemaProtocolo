<?php

class ProjetoForm extends TPage
{
    protected $form;
    protected $documentos_list;
    
    public function __construct()
    {
        parent::__construct();

        // AppSecurity::checkAccess('gestor');  // só gestores podem acessar
        
        $this->form = new BootstrapFormBuilder('form_projeto');
        $this->form->setFormTitle('Projeto');
        
        $id = new THidden('id');
        $nome = new TEntry('nome');
        $descricao = new TText('descricao');
        $dia_vencimento = new TSpinner('dia_vencimento');
        $ativo = new TCheckButton('ativo');
        $ativo->setIndexValue('on'); // Value when checked
        
        $nome->setSize('100%');
        $descricao->setSize('100%', 100);
        $dia_vencimento->setSize('100%');
        $dia_vencimento->setRange(1, 31, 1);
        
        $nome->addValidation('Nome', new TRequiredValidator);
        $dia_vencimento->addValidation('Dia Vencimento', new TRequiredValidator);
        
        // Lista de documentos
        $this->documentos_list = new TFieldList;
        $this->documentos_list->width = '100%';
        $this->documentos_list->enableSorting();

        $doc_nome = new TEntry('nome_doc[]');
        $doc_nome->setSize('100%');
        
        $this->documentos_list->addField('Nome do Documento', $doc_nome, ['width' => '100%']);

        $this->documentos_list->addHeader();
        // $this->documentos_list->addCloneAction(); // Movido para após addDetail

        if (empty($_REQUEST['id'])) {
            $this->documentos_list->addDetail( new stdClass );
            $this->documentos_list->addCloneAction();
        }
        
        $this->form->addFields([$id]);
        $this->form->addFields([new TLabel('Nome*')], [$nome]);
        $this->form->addFields([new TLabel('Descrição')], [$descricao]);
        $this->form->addFields([new TLabel('Dia Vencimento*')], [$dia_vencimento]);
        $this->form->addFields([new TLabel('Ativo')], [$ativo]);
        $this->form->addFields([new TLabel('Documentos Necessários')], [$this->documentos_list]);
        
        $btn_save = $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fa:save green');
        $btn_back = $this->form->addAction('Voltar', new TAction(['ProjetoList', 'onReload']), 'fa:arrow-left blue');
        
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add($this->form);
        
        parent::add($container);
    }
    
    public function onEdit($param)
    {
        try {
            if (isset($param['id'])) {
                TTransaction::open('database');
                
                $projeto = new Projeto($param['id']);
                
                $data = $projeto->toArray();
                
                // Load documents from child table
                $items = ProjetoDocumento::where('projeto_id', '=', $projeto->id)->load();
                
                if ($items) {
                    foreach ($items as $item_obj) {
                        $item = new stdClass;
                        // Use 'nome_doc' without brackets for the property name
                        // TFieldList handles the array notation based on the field definition
                        $item->nome_doc = $item_obj->nome_documento;
                        $this->documentos_list->addDetail($item);
                    }
                } else {
                    $this->documentos_list->addDetail( new stdClass );
                }
                
                $this->documentos_list->addCloneAction();

                // Convert 'ativo' from database (1/0) to checkbox value ('on' or empty)
                $data['ativo'] = ($projeto->ativo == 1) ? 'on' : '';
                
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
            $this->form->validate();

            TTransaction::open('database');

            $projeto = new Projeto;
            $projeto->fromArray((array) $param);
            
            // Handle checkbox - convert to 1 or 0
            $projeto->ativo = (!empty($param['ativo']) && $param['ativo'] !== '0') ? 1 : 0;

            $projeto->store(); // Store first to get ID
            
            // Clear old documents
            ProjetoDocumento::where('projeto_id', '=', $projeto->id)->delete();
            
            // DEBUG: Log all param keys related to documents
            file_put_contents('C:/xampp/htdocs/sistemaProtocolo/debug_projeto.log', 
                "=== Save at " . date('H:i:s') . " ===\n" .
                "Full param: " . print_r($param, true) . "\n", 
                FILE_APPEND);
            
            // Normalize nome_doc to array (TFieldList may send string if single row)
            $nome_docs = [];
            if (isset($param['nome_doc'])) {
                if (is_array($param['nome_doc'])) {
                    $nome_docs = $param['nome_doc'];
                } else {
                    // Single value sent as string
                    $nome_docs = [$param['nome_doc']];
                }
            }
            
            // Insert new documents
            foreach ($nome_docs as $doc_name) {
                if (!empty(trim($doc_name))) {
                    $detail = new ProjetoDocumento;
                    $detail->projeto_id = $projeto->id;
                    $detail->nome_documento = trim($doc_name);
                    $detail->obrigatorio = 1; 
                    $detail->store();
                }
            }

            TTransaction::close();

            new TMessage('info', 'Projeto salvo com sucesso');
            TApplication::gotoPage('ProjetoList');

        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

}
