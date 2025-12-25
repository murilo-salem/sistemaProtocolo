<?php

class WelcomePage extends TPage
{
    public function __construct()
    {
        parent::__construct();

        $wrapper = new TElement('div');
        $wrapper->style = 'display:flex; flex-direction:column; align-items:center; justify-content:center; height:80vh;';

        $title = new TElement('h2');
        $title->add('Bem-vindo!');
        $title->style = 'margin-bottom:20px;';

        $subtitle = new TElement('p');
        $subtitle->add('Escolha uma opção para continuar');
        $subtitle->style = 'margin-bottom:30px;';

        $btnLogin = new TActionLink('Login', new TAction(['LoginForm', 'onShow']), 'fa:sign-in blue');
        $btnLogin->class = 'btn btn-primary';
        $btnLogin->style = 'margin:10px;';

        $btnRegister = new TActionLink('Criar Conta', new TAction(['RegisterForm', 'onShow']), 'fa:user-plus green');
        $btnRegister->class = 'btn btn-success';
        $btnRegister->style = 'margin:10px;';

        $wrapper->add($title);
        $wrapper->add($subtitle);
        $wrapper->add($btnLogin);
        $wrapper->add($btnRegister);

        parent::add($wrapper);
    }
}
