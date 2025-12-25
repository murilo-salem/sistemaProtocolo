<?php

class WelcomePage extends TPage
{
    public function __construct()
    {
        parent::__construct();

        $vbox = new TVBox;
        $vbox->add('<h2>Bem-vindo!</h2>');
        $vbox->add('<p>Escolha uma opção:</p>');

        $btnLogin = new TActionLink('Login', new TAction(['LoginForm', 'onShow']), 'fa:sign-in blue');
        $btnRegister = new TActionLink('Criar Conta', new TAction(['RegisterForm', 'onShow']), 'fa:user-plus green');

        $vbox->add($btnLogin);
        $vbox->add($btnRegister);

        parent::add($vbox);
    }
}
