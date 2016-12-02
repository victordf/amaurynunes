<?php
/**
 * Created by PhpStorm.
 * User: victor
 * Date: 11/09/16
 * Time: 11:57
 */

namespace App;

use PHPMailer;
use Symfony\Component\Config\Definition\Exception\Exception;

class Email {

    protected $mailer;

    public $error;

    const sisEmail = 'victor@bsvsolucoes.com.br';
    const sisNome  = 'BSV Soluções';
    const sisPass  = 'bsv.2016df';

    /**
     * Email constructor.
     * @param null $user
     * @param null $pass
     */
    public function __construct($user = null, $pass = null){
        $this->mailer = new PHPMailer();

        $this->mailer->isSMTP();
        $this->mailer->SMTPAuth = true;
        $this->mailer->Host = 'mail.bsvsolucoes.com.br';
        $this->mailer->Username = !empty($user) ? $user : self::sisEmail;
        $this->mailer->Password = !empty($pass) ? $pass : self::sisPass;
        $this->mailer->SMTPOptions = array (
            'ssl' => array(
                'verify_peer'  => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true
            )
        );
        $this->mailer->Port = 25;
        $this->setFormato();
        $this->setEmailFrom();
    }


    /**
     * Altera o formato do email, para ser enviado em html ou não.
     *
     * @param bool $isHtml
     * @return $this
     */
    public function setFormato($isHtml = false){
        $this->mailer->isHTML($isHtml);
        return $this;
    }

    /**
     * Adiciona um anexo ao email.
     *
     * @param $arquivo
     * @throws phpmailerException
     * @return $this
     */
    public function addAnexo($arquivo){
        $this->mailer->addAttachment($arquivo);
        return $this;
    }

    /**
     * Adiciona o email que irá enviar o email.
     *
     * @param string $email
     * @param string $nome
     * @return $this
     */
    public function setEmailFrom($email = self::sisEmail, $nome = self::sisNome){
        $this->mailer->setFrom($email, $nome);
        return $this;
    }

    /**
     * Adiciona os emails que irão receber o email enviado.
     *
     * @param array $emails - array(<Nome> => <Email>)
     * @return $this
     */
    public function addEmailTo($emails = array()){
        if(is_array($emails) && count($emails) > 0){
            foreach ($emails as $nome => $email) {
                $this->mailer->addAddress($email, $nome);
            }
        }
        return $this;
    }

    /**
     * Envia o email.
     *
     * @param $assunto
     * @param $corpo
     * @return bool
     * @return $this
     */
    public function send($assunto, $corpo){
        $this->mailer->Subject = $assunto;
        $this->mailer->Body = $corpo;
        return $this->mailer->send();
    }
}