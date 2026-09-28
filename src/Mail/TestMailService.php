<?php
namespace TechByIt\SMTP\Mail;

final class TestMailService {
	private $manager;
	private $integration;
	public function __construct( MailManager $manager, WordPressMailIntegration $integration ) {
		$this->manager     = $manager;
		$this->integration = $integration;
	}
	public function send( string $to, ?string $subject = null, ?string $message = null ): MailResult {
		$selection = $this->manager->active_transport();
		if ( $selection->passthrough() || ! $selection->success() ) {
			return new MailResult( false, $selection->provider(), null, $selection->passthrough() ? 'provider_not_configured' : $selection->error_code(), 'An active sending provider is required.' );
		}
		$subject = null !== $subject && '' !== $subject ? $subject : 'TechByIt SMTP Test Email';
		$message = null !== $message && '' !== $message ? $message : 'This is a test email sent using TechByIt SMTP.';
		$sent    = wp_mail( $to, $subject, $message );
		$result  = $this->integration->last_result();
		if ( $result ) {
			return $result; }
		return new MailResult( false, $selection->provider(), null, $sent ? 'mail_preempted' : 'mail_send_failed', $sent ? 'Another mail handler intercepted the test email.' : 'The test email could not be sent.' );
	}
}
