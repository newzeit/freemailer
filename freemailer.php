<?php
/**
 * New Zeit - https://newzeit.com.ar
 * Contacto: gonzalo.tapie@newzeit.com.ar
 */

/**
 * FreeMailer - Librería simple de correo para PHP
 * Compatible con PHP 5.4+
 */

class FreeMailer {
    const CRLF = "\r\n";

    public $CharSet = 'UTF-8';
    public $ContentType = 'text/plain';
    public $From = '';
    public $FromName = '';
    public $Sender = '';
    public $Subject = '';
    public $Body = '';
    public $AltBody = '';
    public $IsHTML = false;
    public $IsSMTP = false;

    protected $Host = '';
    protected $Port = 25;
    protected $SMTPAuth = false;
    protected $Username = '';
    protected $Password = '';
    protected $SMTPSecure = '';
    protected $SMTPDebug = 0;

    protected $Socket;
    protected $To = array();
    protected $Cc = array();
    protected $Bcc = array();
    protected $Attachments = array();
    protected $ReplyTo = array();
    protected $Headers = array();

    public function __construct() {
        $this->Socket = false;
    }

    public function IsSMTP() {
        $this->IsSMTP = true;
    }

    public function IsMail() {
        $this->IsSMTP = false;
    }

    public function IsHTML($ishtml = true) {
        $this->IsHTML = $ishtml;
        $this->ContentType = $ishtml ? 'text/html' : 'text/plain';
    }

    public function SMTPAuth($auth = true) {
        $this->SMTPAuth = $auth;
    }

    public function Host($host) {
        $this->Host = $host;
    }

    public function Port($port) {
        $this->Port = $port;
    }

    public function Username($username) {
        $this->Username = $username;
    }

    public function Password($password) {
        $this->Password = $password;
    }

    public function SMTPSecure($secure) {
        $this->SMTPSecure = $secure;
    }

    public function SMTPDebug($debug = 0) {
        $this->SMTPDebug = $debug;
    }

    public function From($address, $name = '') {
        $this->From = $address;
        $this->FromName = $name;
    }

    public function Sender($address) {
        $this->Sender = $address;
    }

    public function AddAddress($address, $name = '') {
        $this->To[] = array($address, $name);
    }

    public function AddCC($address, $name = '') {
        $this->Cc[] = array($address, $name);
    }

    public function AddBCC($address, $name = '') {
        $this->Bcc[] = array($address, $name);
    }

    public function AddReplyTo($address, $name = '') {
        $this->ReplyTo[] = array($address, $name);
    }

    public function AddAttachment($path, $name = '') {
        if (!file_exists($path)) {
            throw new Exception("Archivo de adjunto no encontrado: " . $path);
        }
        $filename = basename($path);
        $this->Attachments[] = array($path, $name ? $name : $filename);
    }

    public function Subject($subject) {
        $this->Subject = $subject;
    }

    public function Body($body) {
        $this->Body = $body;
    }

    public function AltBody($altbody) {
        $this->AltBody = $altbody;
    }

    public function Send() {
        if (empty($this->To) && empty($this->Bcc)) {
            throw new Exception("No se especificaron destinatarios");
        }

        if ($this->IsSMTP) {
            return $this->SmtpSend();
        } else {
            return $this->MailSend();
        }
    }

    protected function MailSend() {
        $to = $this->BuildAddresses($this->To);

        $this->BuildHeaders();

        $headers = '';
        foreach ($this->Headers as $key => $value) {
            $headers .= $key . ': ' . $value . self::CRLF;
        }

        $subject = $this->EncodeSubject($this->Subject);
        $body = $this->IsHTML ? $this->Body : $this->EncodeString($this->Body);

        if ($this->IsHTML && !empty($this->AltBody)) {
            $body = $this->BuildMultipart($body, $this->EncodeString($this->AltBody));
        }

        if (!empty($this->Attachments)) {
            $body = $this->BuildMultipartWithAttachments($body);
        }

        $additional_params = '-f' . ($this->Sender ? $this->Sender : $this->From);

        return mail($to, $subject, $body, $headers, $additional_params);
    }

    protected function SmtpSend() {
        $this->SmtpConnect();

        if ($this->SMTPAuth) {
            $this->SmtpCommand("AUTH LOGIN");
            $this->SmtpCommand(base64_encode($this->Username));
            $this->SmtpCommand(base64_encode($this->Password));
        }

        $this->SmtpCommand("MAIL FROM:<" . $this->From . ">");

        foreach ($this->To as $address) {
            $this->SmtpCommand("RCPT TO:<" . $address[0] . ">");
        }
        foreach ($this->Cc as $address) {
            $this->SmtpCommand("RCPT TO:<" . $address[0] . ">");
        }
        foreach ($this->Bcc as $address) {
            $this->SmtpCommand("RCPT TO:<" . $address[0] . ">");
        }

        $this->SmtpCommand("DATA");

        $this->BuildHeaders();

        $headerStr = '';
        foreach ($this->Headers as $key => $value) {
            $headerStr .= $key . ': ' . $value . self::CRLF;
        }

        $body = $this->IsHTML ? $this->Body : $this->EncodeString($this->Body);

        if ($this->IsHTML && !empty($this->AltBody)) {
            $body = $this->BuildMultipart($body, $this->EncodeString($this->AltBody));
        }

        if (!empty($this->Attachments)) {
            $body = $this->BuildMultipartWithAttachments($body);
        }

        $message = $headerStr . self::CRLF . $body;

        $message .= self::CRLF . '.' . self::CRLF;

        fwrite($this->Socket, $message);

        $response = fgets($this->Socket, 512);
        if ($this->SMTPDebug) {
            echo "<< " . $response;
        }

        $this->SmtpCommand("QUIT");
        fclose($this->Socket);

        return true;
    }

    protected function SmtpConnect() {
        $protocol = strtolower($this->SMTPSecure) == 'ssl' ? 'ssl://' : (strtolower($this->SMTPSecure) == 'tls' ? 'tls://' : '');
        $host = $protocol . $this->Host;

        $errno = 0;
        $errstr = '';

        $this->Socket = @fsockopen($host, $this->Port, $errno, $errstr, 30);

        if (!$this->Socket) {
            throw new Exception("No se pudo conectar al servidor SMTP: $errstr ($errno)");
        }

        $response = fgets($this->Socket, 512);
        if (substr($response, 0, 3) != '220') {
            throw new Exception("Handshake SMTP fallido: " . $response);
        }

        $this->SmtpCommand("EHLO " . $this->Host);

        if (strtolower($this->SMTPSecure) == 'tls') {
            $this->SmtpCommand("STARTTLS");
            stream_socket_enable_crypto($this->Socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $this->SmtpCommand("EHLO " . $this->Host);
        }
    }

    protected function SmtpCommand($command) {
        if ($this->SMTPDebug) {
            echo ">> " . $command . self::CRLF;
        }

        fwrite($this->Socket, $command . self::CRLF);

        $response = '';
        while (true) {
            $line = fgets($this->Socket, 512);
            $response .= $line;
            if ($this->SMTPDebug) {
                echo "<< " . $line;
            }
            if (strlen($line) < 4 || $line[3] === ' ') {
                break;
            }
        }

        $code = (int)substr($response, 0, 3);

        if ($code >= 200 && $code < 400) {
            return $response;
        }

        if ($command == 'QUIT') {
            return $response;
        }

        throw new Exception("Comando SMTP fallido: " . $command . " -> " . $response);
    }

    protected function BuildHeaders() {
        $this->Headers = array();

        $this->Headers['MIME-Version'] = '1.0';
        $this->Headers['Content-Type'] = $this->ContentType . '; charset=' . $this->CharSet;
        $this->Headers['Date'] = date('D, d M Y H:i:s O');

        if (!empty($this->From)) {
            $from = $this->FromName ? $this->EncodeHeader($this->FromName) . ' <' . $this->From . '>' : $this->From;
            $this->Headers['From'] = $from;
        }

        if (!empty($this->Sender)) {
            $this->Headers['Sender'] = $this->Sender;
        }

        if (!empty($this->To)) {
            $this->Headers['To'] = $this->BuildAddresses($this->To);
        }

        if (!empty($this->Cc)) {
            $this->Headers['Cc'] = $this->BuildAddresses($this->Cc);
        }

        if (!empty($this->Bcc)) {
            $this->Headers['Bcc'] = $this->BuildAddresses($this->Bcc);
        }

        if (!empty($this->ReplyTo)) {
            $this->Headers['Reply-To'] = $this->BuildAddresses($this->ReplyTo);
        }

        $this->Headers['Subject'] = $this->EncodeSubject($this->Subject);
    }

    protected function BuildAddresses($addresses) {
        $result = array();
        foreach ($addresses as $address) {
            if (!empty($address[1])) {
                $result[] = $this->EncodeHeader($address[1]) . ' <' . $address[0] . '>';
            } else {
                $result[] = $address[0];
            }
        }
        return implode(', ', $result);
    }

    protected function BuildMultipart($text, $alt) {
        $boundary = md5(uniqid(time()));
        $message = 'Este es un mensaje de varias partes en formato MIME.' . self::CRLF;
        $message .= '--' . $boundary . self::CRLF;
        $message .= 'Content-Type: text/plain; charset=' . $this->CharSet . self::CRLF;
        $message .= 'Content-Transfer-Encoding: 7bit' . self::CRLF . self::CRLF;
        $message .= $alt . self::CRLF;
        $message .= '--' . $boundary . self::CRLF;
        $message .= 'Content-Type: ' . $this->ContentType . '; charset=' . $this->CharSet . self::CRLF;
        $message .= 'Content-Transfer-Encoding: 7bit' . self::CRLF . self::CRLF;
        $message .= $text . self::CRLF;
        $message .= '--' . $boundary . '--' . self::CRLF;
        return $message;
    }

    protected function BuildMultipartWithAttachments($body) {
        $boundary = md5(uniqid(time()));
        $this->Headers['Content-Type'] = 'multipart/mixed; boundary=' . $boundary;

        $message = 'Este es un mensaje de varias partes en formato MIME.' . self::CRLF;
        $message .= '--' . $boundary . self::CRLF;

        if (isset($this->Headers['Content-Type'])) {
            $ctype = $this->Headers['Content-Type'];
            unset($this->Headers['Content-Type']);
            $message .= 'Content-Type: ' . $ctype . self::CRLF;
        }

        $message .= 'Content-Transfer-Encoding: 7bit' . self::CRLF . self::CRLF;
        $message .= $body . self::CRLF;

        foreach ($this->Attachments as $attachment) {
            $content = chunk_split(base64_encode(file_get_contents($attachment[0])));
            $message .= '--' . $boundary . self::CRLF;
            $message .= 'Content-Type: application/octet-stream; name="' . $attachment[1] . '"' . self::CRLF;
            $message .= 'Content-Transfer-Encoding: base64' . self::CRLF;
            $message .= 'Content-Disposition: attachment; filename="' . $attachment[1] . '"' . self::CRLF . self::CRLF;
            $message .= $content . self::CRLF;
        }

        $message .= '--' . $boundary . '--' . self::CRLF;
        return $message;
    }

    protected function EncodeHeader($string) {
        if (preg_match('/[\x80-\xFF]/', $string)) {
            $string = '=?' . $this->CharSet . '?B?' . base64_encode($string) . '?=';
        }
        return $string;
    }

    protected function EncodeSubject($subject) {
        return $this->EncodeHeader($subject);
    }

    protected function EncodeString($string) {
        return preg_replace('/^\./m', '..', $string);
    }

    public function ClearAddresses() {
        $this->To = array();
    }

    public function ClearCCs() {
        $this->Cc = array();
    }

    public function ClearBCCs() {
        $this->Bcc = array();
    }

    public function ClearReplyTos() {
        $this->ReplyTo = array();
    }

    public function ClearAttachments() {
        $this->Attachments = array();
    }

    public function ClearAll() {
        $this->ClearAddresses();
        $this->ClearCCs();
        $this->ClearBCCs();
        $this->ClearReplyTos();
        $this->ClearAttachments();
        $this->Headers = array();
    }

    public function GetError() {
        return $this->ErrorInfo;
    }
}