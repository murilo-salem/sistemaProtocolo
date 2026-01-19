<?php

class LoginForm extends TPage
{
    protected $form;

    public function __construct()
    {
        parent::__construct();

        // Create modern login form with custom HTML structure
        $this->form = new TForm('form_login');
        
        // Login field with icon
        $login = new TEntry('login');
        $login->setProperty('placeholder', 'Digite seu login');
        $login->setSize('100%');
        $login->addValidation('Login', new TRequiredValidator);
        
        // Password field with icon
        $senha = new TPassword('senha');
        $senha->setProperty('placeholder', 'Digite sua senha');
        $senha->setSize('100%');
        $senha->addValidation('Senha', new TRequiredValidator);
        
        // Remember me checkbox
        $lembrar = new TCheckButton('lembrar');
        $lembrar->setIndexValue('1');
        
        // =============================================
        // SPLIT SCREEN LAYOUT - Two Column Structure
        // =============================================
        
        $wrapper = new TElement('div');
        $wrapper->class = 'login-split-wrapper';
        
        // =============================================
        // LEFT PANEL - Brand/Marketing Panel
        // =============================================
        $leftPanel = new TElement('div');
        $leftPanel->class = 'login-left-panel';
        
        // Logo at top left
        $logo = new TElement('div');
        $logo->class = 'brand-logo';
        $logo->add('<i class="fa fa-file-text-o"></i> <span>DocManager</span>');
        $leftPanel->add($logo);
        
        // Centered content
        $brandContent = new TElement('div');
        $brandContent->class = 'brand-content';
        
        $brandTitle = new TElement('h1');
        $brandTitle->class = 'brand-title';
        $brandTitle->add('Bem-vindo ao Sistema de Gestão de Documentos');
        
        $brandSubtitle = new TElement('p');
        $brandSubtitle->class = 'brand-subtitle';
        $brandSubtitle->add('Gerencie seus documentos de forma simples, segura e eficiente. Tenha controle total sobre entregas, prazos e aprovações.');
        
        // Feature list
        $featureList = new TElement('ul');
        $featureList->class = 'brand-features';
        $featureList->add('<li><i class="fa fa-check-circle"></i> Controle de entregas e prazos</li>');
        $featureList->add('<li><i class="fa fa-check-circle"></i> Validação automática de documentos</li>');
        $featureList->add('<li><i class="fa fa-check-circle"></i> Comunicação integrada via chat</li>');
        $featureList->add('<li><i class="fa fa-check-circle"></i> Dashboard com métricas em tempo real</li>');
        
        $brandContent->add($brandTitle);
        $brandContent->add($brandSubtitle);
        $brandContent->add($featureList);
        $leftPanel->add($brandContent);
        
        // Footer with copyright
        $brandFooter = new TElement('div');
        $brandFooter->class = 'brand-footer';
        $brandFooter->add('© ' . date('Y') . ' DocManager. Todos os direitos reservados.');
        $leftPanel->add($brandFooter);
        
        $wrapper->add($leftPanel);
        
        // =============================================
        // RIGHT PANEL - Login Form Panel
        // =============================================
        $rightPanel = new TElement('div');
        $rightPanel->class = 'login-right-panel';
        
        // Login Card (Clean version for white background)
        $card = new TElement('div');
        $card->class = 'login-card-clean';
        
        // Header
        $header = new TElement('div');
        $header->class = 'login-header';
        $title = new TElement('h2');
        $title->class = 'login-title';
        $title->add('Acesse sua conta');
        $subtitle = new TElement('p');
        $subtitle->class = 'login-subtitle';
        $subtitle->add('Entre com suas credenciais para continuar');
        $header->add($title);
        $header->add($subtitle);
        $card->add($header);
        
        // Form Fields Container
        $fieldsContainer = new TElement('div');
        $fieldsContainer->class = 'login-fields';
        
        // Login Field Group
        $loginGroup = new TElement('div');
        $loginGroup->class = 'form-group-modern';
        $loginLabel = new TElement('label');
        $loginLabel->class = 'form-label-modern';
        $loginLabel->add('<i class="fa fa-user"></i> Login');
        $loginInputWrapper = new TElement('div');
        $loginInputWrapper->class = 'input-wrapper';
        $loginInputWrapper->add($login);
        $loginGroup->add($loginLabel);
        $loginGroup->add($loginInputWrapper);
        $fieldsContainer->add($loginGroup);
        
        // Password Field Group
        $senhaGroup = new TElement('div');
        $senhaGroup->class = 'form-group-modern';
        $senhaLabel = new TElement('label');
        $senhaLabel->class = 'form-label-modern';
        $senhaLabel->add('<i class="fa fa-lock"></i> Senha');
        $senhaInputWrapper = new TElement('div');
        $senhaInputWrapper->class = 'input-wrapper';
        $senhaInputWrapper->add($senha);
        $senhaGroup->add($senhaLabel);
        $senhaGroup->add($senhaInputWrapper);
        $fieldsContainer->add($senhaGroup);
        
        // Remember Me & Forgot Password Row
        $optionsRow = new TElement('div');
        $optionsRow->class = 'login-options';
        
        $rememberWrapper = new TElement('div');
        $rememberWrapper->class = 'remember-wrapper';
        $rememberWrapper->add($lembrar);
        $rememberLabel = new TElement('span');
        $rememberLabel->class = 'remember-label';
        $rememberLabel->add('Lembrar-me');
        $rememberWrapper->add($rememberLabel);
        
        $forgotLink = new TElement('a');
        $forgotLink->class = 'forgot-link';
        $forgotLink->href = '#';
        $forgotLink->onclick = "new TMessage('info', 'Entre em contato com o administrador para recuperar sua senha.'); return false;";
        $forgotLink->add('Esqueceu a senha?');
        
        $optionsRow->add($rememberWrapper);
        $optionsRow->add($forgotLink);
        $fieldsContainer->add($optionsRow);
        
        $card->add($fieldsContainer);
        
        // Submit Button
        $btnContainer = new TElement('div');
        $btnContainer->class = 'login-btn-container';
        
        $btn = new TButton('btn_login');
        $btn->setAction(new TAction([$this, 'onLogin']), 'Entrar');
        $btn->setImage('fa:sign-in white');
        $btn->class = 'btn btn-login-modern';
        $btn->id = 'btn_login';
        
        $btnContainer->add($btn);
        $card->add($btnContainer);
        
        // Security Footer
        $footer = new TElement('div');
        $footer->class = 'login-footer';
        $footer->add('<i class="fa fa-shield"></i> Acesso seguro e criptografado');
        $card->add($footer);
        
        $rightPanel->add($card);
        $wrapper->add($rightPanel);
        
        // Register form fields
        $this->form->setFields([$login, $senha, $lembrar, $btn]);
        $this->form->add($wrapper);
        
        parent::add($this->form);
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
