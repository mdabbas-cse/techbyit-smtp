<?php
namespace TechByIt\SMTP\Mail;

/** Maps PHPMailer diagnostics to fixed, credential-safe public errors. */
final class MailErrorClassifier {
	public static function classify( string $diagnostic ): array {
		$text = strtolower( $diagnostic );
		if ( false !== strpos( $text, 'authenticat' ) || false !== strpos( $text, 'login failed' ) ) {
			return array(
				'code'    => 'smtp_authentication_failed',
				'message' => 'The SMTP server rejected authentication.',
			);
		}
		if ( false !== strpos( $text, 'certificate' ) || false !== strpos( $text, 'starttls' ) || false !== strpos( $text, 'tls' ) || false !== strpos( $text, 'ssl' ) ) {
			return array(
				'code'    => 'smtp_tls_failed',
				'message' => 'The secure SMTP connection failed.',
			);
		}
		if ( false !== strpos( $text, 'connect' ) || false !== strpos( $text, 'timed out' ) || false !== strpos( $text, 'getaddrinfo' ) ) {
			return array(
				'code'    => 'smtp_connection_failed',
				'message' => 'The SMTP server could not be reached.',
			);
		}
		if ( false !== strpos( $text, 'recipient' ) || false !== strpos( $text, 'address' ) ) {
			return array(
				'code'    => 'invalid_recipient',
				'message' => 'An email recipient was rejected.',
			);
		}
		if ( false !== strpos( $text, 'attach' ) || false !== strpos( $text, 'file could not be accessed' ) ) {
			return array(
				'code'    => 'attachment_failed',
				'message' => 'An email attachment could not be added.',
			);
		}
		return array(
			'code'    => 'mail_send_failed',
			'message' => 'The email could not be sent. Check the mailer settings.',
		);
	}
}
