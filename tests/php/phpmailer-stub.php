<?php
namespace PHPMailer\PHPMailer;
class PHPMailer {
	public $Mailer = 'mail'; public $Host = ''; public $Port = 0; public $SMTPSecure = '';
	public $SMTPAutoTLS = true; public $SMTPAuth = false; public $Username = ''; public $Password = '';
	public $Timeout = 300; public $SMTPDebug = 0;
	public function isSMTP(): void { $this->Mailer = 'smtp'; }
}
