<?php
namespace TechByIt\SMTP\Mail;

use TechByIt\SMTP\Settings\SettingsRepository;

final class WordPressMailIntegration {
	private $manager;
	private $settings;
	private $configurator;
	private $message;
	private $selection;
	private $started;
	private $last_result;

	public function __construct( MailManager $manager, SettingsRepository $settings, PHPMailerConfigurator $configurator ) {
		$this->manager      = $manager;
		$this->settings     = $settings;
		$this->configurator = $configurator;
	}
	public function register(): void {
		add_filter( 'wp_mail', array( $this, 'capture' ), PHP_INT_MAX );
		add_filter( 'pre_wp_mail', array( $this, 'before_send' ), PHP_INT_MAX, 2 );
		add_filter( 'wp_mail_from', array( $this, 'from_email' ) );
		add_filter( 'wp_mail_from_name', array( $this, 'from_name' ) );
		add_action( 'phpmailer_init', array( $this, 'configure' ) );
		add_action( 'wp_mail_succeeded', array( $this, 'sent' ) );
		add_action( 'wp_mail_failed', array( $this, 'failed' ) );
	}
	public function capture( array $args ): array {
		$this->message     = MailMessage::from_wp_mail( $args );
		$this->selection   = null;
		$this->last_result = null;
		return $args;
	}
	public function before_send( $pre, array $args ) {
		if ( null !== $pre ) {
			return $pre; }
		$this->message   = MailMessage::from_wp_mail( $args );
		$this->selection = $this->manager->active_transport();
		if ( $this->selection->passthrough() ) {
			return null; }
		$this->started = microtime( true );
		if ( ! $this->selection->success() ) {
			$this->last_result = $this->failure_result( $this->selection->error_code() );
			do_action( 'mailflow_smtp_mail_failed', $this->last_result, $this->message );
			return false;
		}
		do_action( 'mailflow_smtp_before_send', $this->message, $this->selection->provider() );
		return null;
	}
	public function from_email( string $email ): string {
		if ( ! $this->selection || ! $this->selection->success() || $this->selection->passthrough() || ( $this->message && $this->message->from() ) ) {
			return $email; }
		$configured = $this->settings->get()['from_email'];
		return '' !== $configured && is_email( $configured ) ? $configured : $email;
	}
	public function from_name( string $name ): string {
		if ( ! $this->selection || ! $this->selection->success() || $this->selection->passthrough() || ( $this->message && $this->message->from() ) ) {
			return $name; }
		$configured = $this->settings->get()['from_name'];
		return '' !== $configured ? $configured : $name;
	}
	public function configure( $mailer ): void {
		if ( ! $this->selection || ! $this->selection->success() || $this->selection->passthrough() ) {
			return; }
		$this->configurator->configure( $mailer, $this->selection->configuration() );
	}
	public function sent( array $data ): void {
		if ( ! $this->selection || $this->selection->passthrough() || ! $this->selection->success() ) {
			return; }
		global $phpmailer;
		$id                = $phpmailer && method_exists( $phpmailer, 'getLastMessageID' ) ? $phpmailer->getLastMessageID() : null;
		$this->last_result = new MailResult( true, $this->selection->provider(), $id ? $id : null, null, null, $this->duration() );
		do_action( 'mailflow_smtp_mail_sent', $this->last_result, $this->message );
	}
	public function failed( $error ): void {
		if ( ! $this->selection || $this->selection->passthrough() || ! $this->selection->success() ) {
			return; }
		$diagnostic        = $error instanceof \WP_Error ? $error->get_error_message() : '';
		$classified        = MailErrorClassifier::classify( $diagnostic );
		$this->last_result = new MailResult( false, $this->selection->provider(), null, $classified['code'], $classified['message'], $this->duration() );
		do_action( 'mailflow_smtp_mail_failed', $this->last_result, $this->message );
	}
	private function failure_result( string $code ): MailResult {
		$messages = array(
			'unknown_provider'          => 'The selected mail provider is unavailable.',
			'provider_not_configured'   => 'The selected mail provider is not configured.',
			'transport_unavailable'     => 'Sending is not available for the selected provider yet.',
			'configuration_unavailable' => 'Mail configuration is temporarily unavailable.',
			'mail_send_failed'          => 'The email could not be sent. Check the mailer settings.',
		);
		return new MailResult( false, $this->selection ? $this->selection->provider() : '', null, $code, $messages[ $code ] ?? $messages['mail_send_failed'], $this->duration() );
	}
	private function duration(): int {
		return $this->started ? (int) round( ( microtime( true ) - $this->started ) * 1000 ) : 0; }
	public function last_result(): ?MailResult {
		return $this->last_result; }
}
