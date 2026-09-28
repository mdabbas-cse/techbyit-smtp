<?php
namespace TechByIt\SMTP\Mail;

use PHPMailer\PHPMailer\PHPMailer;

final class PHPMailerConfigurator {
	public function configure( PHPMailer $mailer, array $config ): void {
		// PHPMailer defines these public properties with upstream casing.
		// phpcs:disable WordPress.NamingConventions.ValidVariableName
		$mailer->isSMTP();
		$mailer->Host        = $config['host'];
		$mailer->Port        = (int) $config['port'];
		$mailer->SMTPSecure  = 'none' === $config['encryption'] ? '' : $config['encryption'];
		$mailer->SMTPAutoTLS = 'tls' === $config['encryption'];
		$mailer->SMTPAuth    = ! empty( $config['authentication'] );
		$mailer->Username    = $mailer->SMTPAuth ? (string) ( $config['username'] ?? '' ) : '';
		$mailer->Password    = $mailer->SMTPAuth ? (string) ( $config['password'] ?? '' ) : '';
		$mailer->Timeout     = (int) ( $config['timeout'] ?? 10 );
		$mailer->SMTPDebug   = 0;
		// phpcs:enable WordPress.NamingConventions.ValidVariableName
	}
}
