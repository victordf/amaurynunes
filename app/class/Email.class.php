<?php
/**
 * Created by PhpStorm.
 * User: victor
 * Date: 11/09/16
 * Time: 11:57
 */

namespace Bsv;

use PHPMailer;

class Email {

    protected $mailer;

    public $error;

    const sisEmail = 'bsvsolucoes@gmail.com';
    const sisNome  = 'BSV Soluções';
    const sisPass  = '!@#$5678';

    /**
     * Email constructor.
     * @param null $user
     * @param null $pass
     */
    public function __construct($user = null, $pass = null)
    {
        $this->mailer = new PHPMailer();

        $this->mailer->isSMTP();
        $this->mailer->Host = 'smtp.gmail.com';
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = !empty($user) ? $user : self::sisEmail;
        $this->mailer->Password = !empty($pass) ? $pass : self::sisPass;
        $this->mailer->SMTPSecure = 'tls';
        $this->mailer->Port = 587;
        $this->setFormato();
        $this->setEmailFrom();
    }

    /**
     * Altera o formato do email, para ser enviado em html ou não.
     *
     * @param bool $isHtml (Default = false)
     */
    public function setFormato($isHtml = false){
        $this->mailer->isHTML($isHtml);
    }

    /**
     * Adiciona um anexo ao email.
     *
     * @param $arquivo
     * @throws phpmailerException
     */
    public function addAnexo($arquivo){
        $this->mailer->addAttachment($arquivo);
    }

    /**
     * Adiciona o email que irá enviar o email.
     *
     * @param string $email
     * @param string $nome
     */
    public function setEmailFrom($email = self::sisEmail, $nome = self::sisNome){
        $this->mailer->setFrom($email, $nome);
    }

    /**
     * Adiciona os emails que irão receber o email enviado.
     *
     * @param array $emails - array(<Nome> => <Email>)
     */
    public function addEmailTo($emails = array()){
        if(is_array($emails) && count($emails) > 0){
            foreach ($emails as $nome => $email) {
                $this->mailer->addAddress($email, $nome);
            }
        }
    }

    /**
     * Envia o email.
     *
     * @param $assunto
     * @param $corpo
     * @return bool
     * @throws phpmailerException
     */
    public function send($assunto, $corpo){
        $this->mailer->Subject = $assunto;
        $this->mailer->Body = $corpo;
        if(!$this->mailer->send()){
            var_dump($this->mailer->ErrorInfo);
            die();
            $this->error = $this->mailer->ErrorInfo;
            return false;
        } else {
            return true;
        }
    }
}