<?php
namespace TechByIt\SMTP\Providers;

interface SmtpTransportProviderInterface extends SendingProviderInterface {
	/** @return array<string, mixed> Validated PHPMailer SMTP configuration. */
	public function smtp_configuration( array $values ): array;
}
