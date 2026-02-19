<?php
/**
 * PasswordRecoveryForm Request Form
 */
class PasswordRecoveryForm extends TPage
{
    protected $form; // form
    
    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param )
    {
        parent::__construct();
        
        // creates the form
        $this->form = new TForm('form_password_recovery');
        $this->form->class = 'tform';
        
        // create the form fields
        $login   = new TEntry('login');
        $email   = new TEntry('email');
        
        // define the sizes
        $login->setSize('100%');
        $email->setSize('100%');
        
        $login->placeholder = 'Login';
        $email->placeholder = 'Email';
        
        // validations
        $login->addValidation('Login', new TRequiredValidator);
        $email->addValidation('Email', new TEmailValidator);
        
        // create the functionality button
        $btn = new TButton('send');
        $btn->setAction(new TAction(array($this, 'onSend')), 'Enviar nova senha');
        $btn->setImage('fa:check white');
        $btn->addStyleClass('btn-primary');
        $btn->style = 'width: 100%';
        
        // =============================================
        // LAYOUT SIMILAR TO LOGIN FORM
        // =============================================
        $wrapper = new TElement('div');
        $wrapper->class = 'login-split-wrapper';
        
        // LEFT PANEL (Brand) - Reused from LoginForm logic but simplified or same
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
        $brandTitle->add('Recuperação de Senha');
        $brandSubtitle = new TElement('p');
        $brandSubtitle->class = 'brand-subtitle';
        $brandSubtitle->add('Informe seu login e email cadastrados para receber instruções de recuperação.');
        
        $brandContent->add($brandTitle);
        $brandContent->add($brandSubtitle);
        $leftPanel->add($brandContent);
        
        $brandFooter = new TElement('div');
        $brandFooter->class = 'brand-footer';
        $brandFooter->add('© ' . date('Y') . ' CSS Sistemas. Todos os direitos reservados.');
        $leftPanel->add($brandFooter);
        
        $wrapper->add($leftPanel);
        
        // RIGHT PANEL (Form)
        $rightPanel = new TElement('div');
        $rightPanel->class = 'login-right-panel';
        
        $card = new TElement('div');
        $card->class = 'login-card-clean';
        
        $header = new TElement('div');
        $header->class = 'login-header';
        $title = new TElement('h2');
        $title->class = 'login-title';
        $title->add('Recuperar Acesso');
        $header->add($title);
        $card->add($header);
        
        $fieldsContainer = new TElement('div');
        $fieldsContainer->class = 'login-fields';
        
        // Login Field
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
        
        // Email Field
        $emailGroup = new TElement('div');
        $emailGroup->class = 'form-group-modern';
        $emailLabel = new TElement('label');
        $emailLabel->class = 'form-label-modern';
        $emailLabel->add('<i class="fa fa-envelope"></i> Email');
        $emailInputWrapper = new TElement('div');
        $emailInputWrapper->class = 'input-wrapper';
        $emailInputWrapper->add($email);
        $emailGroup->add($emailLabel);
        $emailGroup->add($emailInputWrapper);
        $fieldsContainer->add($emailGroup);
        
        $card->add($fieldsContainer);
        
        $btnContainer = new TElement('div');
        $btnContainer->class = 'login-btn-container';
        $btnContainer->add($btn);
        $card->add($btnContainer);
        
        // Back to Login Link
        $backLinkDiv = new TElement('div');
        $backLinkDiv->style = 'text-align: center; margin-top: 15px;';
        $backLink = new TElement('a');
        $backLink->href = 'index.php?class=LoginForm';
        $backLink->class = 'forgot-link';
        $backLink->add('<i class="fa fa-arrow-left"></i> Voltar para Login');
        $backLinkDiv->add($backLink);
        $card->add($backLinkDiv);
        
        $rightPanel->add($card);
        $wrapper->add($rightPanel);
        
        $this->form->add($wrapper);
        $this->form->setFields([$login, $email, $btn]);
        
        parent::add($this->form);
    }
    
    /**
     * Send new password
     */
    public function onSend($param)
    {
        try
        {
            $this->form->validate();
            $data = $this->form->getData();
            
            TTransaction::open('database');
            
            $user = Usuario::where('login', '=', $data->login)
                           ->where('email', '=', $data->email)
                           ->where('ativo', '=', '1') // Only active users
                           ->first();
            
            if ($user)
            {
                // Generate token
                $token = md5(uniqid('auth') . microtime());
                $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
                
                // Save token
                $reset = new SystemUserReset;
                $reset->email = $user->email;
                $reset->token = $token;
                $reset->created_at = date('Y-m-d H:i:s');
                $reset->expires_at = $expires_at;
                $reset->used = 'N';
                $reset->store();
                
                // Prepare email
                $link = "http://{$_SERVER['HTTP_HOST']}{$_SERVER['SCRIPT_NAME']}?class=NewPasswordForm&token={$token}";
                
                $message  = "Olá {$user->nome},<br><br>";
                $message .= "Recebemos uma solicitação de recuperação de senha para sua conta.<br>";
                $message .= "Clique no link abaixo para redefinir sua senha:<br><br>";
                $message .= "<a href='{$link}'>{$link}</a><br><br>";
                $message .= "Se você não solicitou isso, ignore este email.<br>";
                $message .= "Este link expira em 24 horas.";
                
                try {
                   // Load mail config
                   $ini = parse_ini_file('app/config/mail.ini');
                   
                   require_once 'vendor/autoload.php';
                   
                   $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                   $mail->isSMTP();
                   $mail->Host       = $ini['host'];
                   $mail->SMTPAuth   = (bool) $ini['auth'];
                   $mail->Username   = $ini['user']; 
                   $mail->Password   = $ini['pass'];
                   $mail->SMTPSecure = $ini['secure'];
                   $mail->Port       = $ini['port'];
                   $mail->CharSet    = 'UTF-8';
                   
                   // Recipients
                   $mail->setFrom($ini['from'], $ini['from_name']);
                   $mail->addAddress($user->email, $user->nome);
                   
                   // Content
                   $mail->isHTML(true);
                   $mail->Subject = 'Recuperação de Senha';
                   $mail->Body    = $message;
                   $mail->AltBody = strip_tags($message);
                   
                   $mail->send();
                   
                   new TMessage('info', "Email de recuperação enviado com sucesso para <b>{$user->email}</b>.<br>Verifique sua caixa de entrada (e spam).");
                   
                } catch (Exception $e) {
                     new TMessage('error', 'Erro ao enviar email: ' . $mail->ErrorInfo);
                }
            }
            else
            {
                new TMessage('error', 'Usuário ou email não encontrado!');
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
