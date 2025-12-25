<?php

class LoginForm extends TPage
{
    protected $form;

    public function __construct()
    {
        parent::__construct();

        $this->form = new BootstrapFormBuilder('form_login');
        $this->form->setFormTitle('Sistema de Gestão de Documentos');

        $login = new TEntry('login');
        $senha = new TPassword('senha');

        $this->form->addFields([new TLabel('Login')], [$login]);
        $this->form->addFields([new TLabel('Senha')], [$senha]);

        $login->addValidation('Login', new TRequiredValidator);
        $senha->addValidation('Senha', new TRequiredValidator);

        $btn = $this->form->addAction('Entrar', new TAction([$this, 'onLogin']), 'fa:sign-in green');
        $btn->class = 'btn btn-sm btn-primary';

        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add($this->form);
        parent::add($container);
    }

    public static function onLogin($param)
    {
        try {
            TTransaction::open('database');

            $usuario = Usuario::autenticar($param['login'], $param['senha']);

            if ($usuario) {
                TSession::setValue('userid', $usuario->id);
                TSession::setValue('username', $usuario->nome);
                TSession::setValue('usertype', $usuario->tipo);

                // redireciona para dashboard correto
                if ($usuario->tipo == 'gestor') {
                    TScript::create("window.location = 'index.php?class=DashboardGestor'");
                } else {
                    TScript::create("window.location = 'index.php?class=DashboardCliente'");
                }
            } else {
                throw new Exception('Login ou senha inválidos');
            }

            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    public static function onLogout()
    {
        TSession::freeSession();
        AdiantiCoreApplication::loadPage('LoginForm');
    }
}
