<?php

class EntregaForm extends TPage
{
    protected $form;

    public function __construct($param)
    {
        parent::__construct();

        $this->form = new BootstrapFormBuilder('form_entrega');
        $this->form->setFormTitle('Envio de Documentos');

        $cliente_id = TSession::getValue('userid');
        $projeto_id = $param['projeto_id'] ?? null;

        $cliente_field = new THidden('cliente_id');
        $cliente_field->setValue($cliente_id);
        $projeto_field = new THidden('projeto_id');
        $projeto_field->setValue($projeto_id);

        $this->form->addFields([$cliente_field, $projeto_field]);

        TTransaction::open('database');
        $projeto = new Projeto($projeto_id);
        $docs = json_decode($projeto->documentos_json, true);
        TTransaction::close();

        if ($docs) {
            foreach ($docs as $doc) {
                $file = new TFile('arquivo_' . md5($doc));
                $file->setAllowedExtensions(['pdf', 'jpg', 'png']);
                $file->setSize('100%');
                $this->form->addFields([new TLabel($doc)], [$file]);
            }
        }

        $mes = new TCombo('mes_referencia');
        $mes->addItems([
            1=>'Janeiro',2=>'Fevereiro',3=>'Março',4=>'Abril',5=>'Maio',6=>'Junho',
            7=>'Julho',8=>'Agosto',9=>'Setembro',10=>'Outubro',11=>'Novembro',12=>'Dezembro'
        ]);

        $ano = new TEntry('ano_referencia');
        $ano->setValue(date('Y'));

        $this->form->addFields([new TLabel('Mês')], [$mes]);
        $this->form->addFields([new TLabel('Ano')], [$ano]);

        $this->form->addAction('Enviar', new TAction([$this, 'onSave']), 'fa:upload green');

        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
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

            $projeto = new Projeto($projeto_id);
            $docs = json_decode($projeto->documentos_json, true);

            $documentos_salvos = [];

            foreach ($docs as $doc) {
                $field = 'arquivo_' . md5($doc);
                if (!empty($param[$field])) {
                    $source = 'tmp/' . $param[$field];
                    $destino = "app/uploads/projetos/{$projeto_id}/{$cliente_id}/{$param[$field]}";
                    if (!file_exists(dirname($destino))) {
                        mkdir(dirname($destino), 0777, true);
                    }
                    rename($source, $destino);
                    $documentos_salvos[$doc] = $destino;
                }
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

            TTransaction::close();

            new TMessage('info', 'Documentos enviados com sucesso!');
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }
}
