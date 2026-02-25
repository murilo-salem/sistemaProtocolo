<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class EmailService
{
    /**
     * Send an email using SMTP settings from mail.ini
     * 
     * @param string $to Email address
     * @param string $toName Recipient name
     * @param string $subject Email subject
     * @param string $body Email content (HTML)
     * @return bool Success status
     * @throws Exception
     */
    public static function send($to, $toName, $subject, $body)
    {
        try {
            $ini = parse_ini_file('app/config/mail.ini');
            if (!$ini) {
                throw new Exception('Arquivo de configuração de e-mail (mail.ini) não encontrado.');
            }

            require_once 'vendor/autoload.php';

            $mail = new PHPMailer(true);
            
            // Server settings
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
            $mail->addAddress($to, $toName);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = strip_tags($body);

            return $mail->send();
        } catch (PHPMailerException $e) {
            throw new Exception("Erro ao enviar e-mail (PHPMailer): " . $e->getMessage());
        } catch (Exception $e) {
            throw new Exception("Erro ao enviar e-mail: " . $e->getMessage());
        }
    }

    /**
     * Send credentials to a new user
     */
    public static function sendCredentials($user, $password)
    {
        $subject = 'Suas credenciais de acesso - ' . (parse_ini_file('app/config/mail.ini')['from_name'] ?? 'Sistema');
        
        $body  = "Olá <b>{$user->nome}</b>,<br><br>";
        $body .= "Sua conta foi criada com sucesso no sistema.<br>";
        $body .= "Abaixo estão suas credenciais de acesso:<br><br>";
        $body .= "<b>URL:</b> http://{$_SERVER['HTTP_HOST']}". dirname($_SERVER['SCRIPT_NAME']) ."<br>";
        $body .= "<b>Login:</b> {$user->login}<br>";
        $body .= "<b>Senha:</b> {$password}<br><br>";
        $body .= "Recomendamos que você altere sua senha após o primeiro acesso.<br><br>";
        $body .= "Atenciosamente,<br>Equipe de Suporte";

        return self::send($user->email, $user->nome, $subject, $body);
    }
}
