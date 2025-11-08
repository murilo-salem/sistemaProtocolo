<?php

class ProjetoForm extends TPage
{
    protected $form;
    
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
        
        $nome->setSize('100%');
        $descricao->setSize('100%', 100);
        $dia_vencimento->setSize('100%');
        $dia_vencimento->setRange(1, 31, 1);
        
        $nome->addValidation('Nome', new TRequiredValidator);
        $dia_vencimento->addValidation('Dia Vencimento', new TRequiredValidator);
        
        // Lista de documentos
        $documentos_list = new TFieldList;
        $documentos_list->width = '100%';
        $documentos_list->setFieldPrefix('docs');
        
        $doc_nome = new TEntry('nome_doc');
        $doc_nome->setSize('100%');
        
        $documentos_list->addField('Nome do Documento', $doc_nome, ['width' => '100%']);
        
        $this->form->addFields([$id]);
        $this->form->addFields([new TLabel('Nome*')], [$nome]);
        $this->form->addFields([new TLabel('Descrição')], [$descricao]);
        $this->form->addFields([new TLabel('Dia Vencimento*')], [$dia_vencimento]);
        $this->form->addFields([new TLabel('Ativo')], [$ativo]);
        $this->form->addFields([new TLabel('Documentos Necessários')], [$documentos_list]);
        
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
                
                $$data = $projeto->toArray();
                
                $docs = json_decode($projeto->documentos_json, true);

                if ($docs) {
                    $docs_data['nome_doc'] = $docs;
                    $data = array_merge($data, $docs_data);
                }

                
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

            // Processar lista de documentos
            $documentos = [];
            if (isset($param['nome_doc']) && is_array($param['nome_doc'])) {
                foreach ($param['nome_doc'] as $doc) {
                    if (!empty($doc)) {
                        $documentos[] = $doc;
                    }
                }
            }

            $projeto->documentos_json = json_encode($documentos);
            $projeto->store();

            TTransaction::close();

            new TMessage('info', 'Projeto salvo com sucesso');
            TApplication::gotoPage('ProjetoList');

        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

}
