<?php
/**
 * NewPasswordForm Reset Form
 */
class NewPasswordForm extends TPage
{
    protected $form; // form
    
    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param )
    {
        parent::__construct();
        
        $token = isset($param['token']) ? $param['token'] : '';
        
        // creates the form
        $this->form = new TForm('form_new_password');
        $this->form->class = 'tform';
        
        // create the form fields
        $password   = new TPassword('password');
        $repassword = new TPassword('repassword');
        $token_field = new THidden('token');
        
        // define the sizes
        $password->setSize('100%');
        $repassword->setSize('100%');
        $token_field->setValue($token);
        
        $password->placeholder = 'Nova Senha';
        $repassword->placeholder = 'Confirme a Nova Senha';
        
        // validations
        $password->addValidation('Nova Senha', new TRequiredValidator);
        $repassword->addValidation('Confirme a Nova Senha', new TRequiredValidator);
        
        $btn = new TButton('save');
        $btn->setAction(new TAction(array($this, 'onSave')), 'Salvar Nova Senha');
        $btn->setImage('fa:check white');
        $btn->addStyleClass('btn-primary');
        $btn->style = 'width: 100%';
        
        // =============================================
        // LAYOUT
        // =============================================
        $wrapper = new TElement('div');
        $wrapper->class = 'login-split-wrapper';
        
        $leftPanel = new TElement('div');
        $leftPanel->class = 'login-left-panel';
        
        $logo = new TElement('div');
        $logo->class = 'brand-logo';
        $logo->add('<i class="fa fa-file-text-o"></i> <span>CSS Sistemas</span>');
        $leftPanel->add($logo);
        
        $brandContent = new TElement('div');
        $brandContent->class = 'brand-content';
        $brandTitle = new TElement('h1');
        $brandTitle->class = 'brand-title';
        $brandTitle->add('Redefinir Senha');
        $brandSubtitle = new TElement('p');
        $brandSubtitle->class = 'brand-subtitle';
        $brandSubtitle->add('Crie uma nova senha segura para acessar sua conta.');
        
        $brandContent->add($brandTitle);
        $brandContent->add($brandSubtitle);
        $leftPanel->add($brandContent);
        
        $brandFooter = new TElement('div');
        $brandFooter->class = 'brand-footer';
        $brandFooter->add('© ' . date('Y') . ' CSS Sistemas. Todos os direitos reservados.');
        $leftPanel->add($brandFooter);
        
        $wrapper->add($leftPanel);
        
        $rightPanel = new TElement('div');
        $rightPanel->class = 'login-right-panel';
        
        $card = new TElement('div');
        $card->class = 'login-card-clean';
        
        $header = new TElement('div');
        $header->class = 'login-header';
        $title = new TElement('h2');
        $title->class = 'login-title';
        $title->add('Nova Senha');
        $header->add($title);
        $card->add($header);
        
        $fieldsContainer = new TElement('div');
        $fieldsContainer->class = 'login-fields';
        
        // Password Field
        $passGroup = new TElement('div');
        $passGroup->class = 'form-group-modern';
        $passLabel = new TElement('label');
        $passLabel->class = 'form-label-modern';
        $passLabel->add('<i class="fa fa-lock"></i> Nova Senha');
        $passInputWrapper = new TElement('div');
        $passInputWrapper->class = 'input-wrapper';
        $passInputWrapper->add($password);
        $passGroup->add($passLabel);
        $passGroup->add($passInputWrapper);
        $fieldsContainer->add($passGroup);
        
        // Repassword Field
        $repassGroup = new TElement('div');
        $repassGroup->class = 'form-group-modern';
        $repassLabel = new TElement('label');
        $repassLabel->class = 'form-label-modern';
        $repassLabel->add('<i class="fa fa-lock"></i> Confirme a Senha');
        $repassInputWrapper = new TElement('div');
        $repassInputWrapper->class = 'input-wrapper';
        $repassInputWrapper->add($repassword);
        $repassGroup->add($repassLabel);
        $repassGroup->add($repassInputWrapper);
        $fieldsContainer->add($repassGroup);
        
        $card->add($fieldsContainer);
        
        $btnContainer = new TElement('div');
        $btnContainer->class = 'login-btn-container';
        $btnContainer->add($btn);
        $card->add($btnContainer);
        
        $rightPanel->add($card);
        $wrapper->add($rightPanel);
        
        $this->form->add($wrapper);
        $this->form->setFields([$password, $repassword, $token_field, $btn]);
        
        parent::add($this->form);
    }
    
    /**
     * Save new password
     */
    public function onSave($param)
    {
        try
        {
            $this->form->validate();
            $data = $this->form->getData();
            
            if ($data->password !== $data->repassword) {
                throw new Exception('As senhas não conferem!');
            }
            
            TTransaction::open('database');
            
            // Validate token
            $reset = SystemUserReset::where('token', '=', $data->token)
                                    ->where('used', '=', 'N')
                                    ->where('expires_at', '>', date('Y-m-d H:i:s'))
                                    ->first();
            
            if ($reset)
            {
                $user = Usuario::where('email', '=', $reset->email)->first();
                
                if ($user)
                {
                    // Update password
                    $user->senha = password_hash($data->password, PASSWORD_DEFAULT);
                    $user->store();
                    
                    // Mark token as used
                    $reset->used = 'Y';
                    $reset->store();
                    
                    new TMessage('info', 'Senha alterada com sucesso! <br><br> <a href="index.php?class=LoginForm" class="btn btn-default">Ir para Login</a>');
                }
                else
                {
                    throw new Exception('Usuário associado ao token não encontrado.');
                }
            }
            else
            {
                throw new Exception('Token inválido ou expirado.');
            }
            
            TTransaction::close();
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
}
