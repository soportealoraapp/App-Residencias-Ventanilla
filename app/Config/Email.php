<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Email extends BaseConfig
{
    public string $fromEmail  = '';
    public string $fromName   = '';
    public string $recipients = '';

    public string $userAgent = 'CodeIgniter';

    public string $protocol = 'smtp';

    public string $mailPath = '/usr/sbin/sendmail';

    public string $SMTPHost = '';

    public string $SMTPAuthMethod = 'login';

    public string $SMTPUser = '';

    public string $SMTPPass = '';

    public int $SMTPPort = 465;

    public int $SMTPTimeout = 10;

    public bool $SMTPKeepAlive = false;

    public string $SMTPCrypto = 'ssl';

    public bool $wordWrap = true;

    public int $wrapChars = 76;

    public string $mailType = 'html';

    public string $charset = 'UTF-8';

    public bool $validate = false;

    public int $priority = 3;

    public string $CRLF = "\r\n";

    public string $newline = "\r\n";

    public bool $BCCBatchMode = false;

    public int $BCCBatchSize = 200;

    public bool $DSN = false;

    public function __construct()
    {
        parent::__construct();

        $this->fromEmail = $_ENV['EMAIL_FROM'] ?? $this->fromEmail;
        $this->fromName  = $_ENV['EMAIL_FROM_NAME'] ?? $this->fromName;
        $this->SMTPHost  = $_ENV['EMAIL_SMTP_HOST'] ?? $this->SMTPHost;
        $this->SMTPUser  = $_ENV['EMAIL_SMTP_USER'] ?? $this->SMTPUser;
        $this->SMTPPass  = $_ENV['EMAIL_SMTP_PASS'] ?? $this->SMTPPass;
        $this->SMTPPort  = (int) ($_ENV['EMAIL_SMTP_PORT'] ?? $this->SMTPPort);

        // smtp (por defecto), sendmail o mail, según lo que use el servidor.
        $protocolo = strtolower(trim((string) ($_ENV['EMAIL_PROTOCOL'] ?? '')));
        if (in_array($protocolo, ['smtp', 'sendmail', 'mail'], true)) {
            $this->protocol = $protocolo;
        }

        // El cifrado tiene que corresponder al puerto: 465 usa SSL directo y
        // 587 (o 25) negocia TLS con STARTTLS. Antes iba fijo en 'ssl', así que
        // la configuración de la guía (puerto 587) no podía conectar.
        // EMAIL_SMTP_CRYPTO acepta ssl, tls o none (sin cifrado, solo en red interna).
        $cifrado = strtolower(trim((string) ($_ENV['EMAIL_SMTP_CRYPTO'] ?? '')));
        if ($cifrado === '') {
            $cifrado = $this->SMTPPort === 465 ? 'ssl' : 'tls';
        }
        $this->SMTPCrypto = $cifrado === 'none' ? '' : $cifrado;
    }
}
