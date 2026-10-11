<?php
namespace TechByIt\SMTP\Logs;

use TechByIt\SMTP\Mail\MailMessage;
use TechByIt\SMTP\Mail\MailResult;
use TechByIt\SMTP\Settings\SettingsRepository;

/**
 * Records messages routed by TechByIt SMTP through the plugin mail hooks.
 * Logging failures are swallowed so they can never stop a message from sending.
 */
final class MailLogger {
	public const PURGE_HOOK      = 'techbyit_smtp_purge_logs';
	private const MAX_BODY_BYTES = 1048576;

	private $logs;
	private $settings;
	/** @var \SplObjectStorage<MailMessage, int> */
	private $pending;

	public function __construct( LogRepository $logs, SettingsRepository $settings ) {
		$this->logs     = $logs;
		$this->settings = $settings;
		$this->pending  = new \SplObjectStorage();
	}

	public function register(): void {
		add_action( 'mailflow_smtp_before_send', array( $this, 'started' ), 10, 2 );
		add_action( 'mailflow_smtp_mail_sent', array( $this, 'finished' ), 10, 2 );
		add_action( 'mailflow_smtp_mail_failed', array( $this, 'finished' ), 10, 2 );
		add_action( self::PURGE_HOOK, array( $this, 'purge' ) );
		add_action( 'init', array( self::class, 'schedule' ) );
	}

	public static function schedule(): void {
		if ( ! wp_next_scheduled( self::PURGE_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::PURGE_HOOK );
		}
	}

	public static function unschedule(): void {
		wp_clear_scheduled_hook( self::PURGE_HOOK );
	}

	public function started( $message, $provider ): void {
		$options = $this->options();
		if ( ! $message instanceof MailMessage || ! $options || ! $options['logging_enabled'] ) {
			return;
		}
		try {
			$id = $this->logs->create( $this->entry( $message, (string) $provider, $options ) );
			if ( $id ) {
				$this->pending[ $message ] = $id;
			}
		} catch ( \Throwable $exception ) {
			return;
		}
	}

	public function finished( $result, $message ): void {
		if ( ! $result instanceof MailResult || ! $message instanceof MailMessage ) {
			return;
		}
		$now    = gmdate( 'Y-m-d H:i:s' );
		$fields = array(
			'status'        => $result->success() ? 'sent' : 'failed',
			'message_id'    => substr( (string) $result->message_id(), 0, 255 ),
			'error_code'    => substr( (string) $result->error_code(), 0, 64 ),
			'error_message' => $result->success() ? null : (string) $result->error_message(),
			'updated_at'    => $now,
			'sent_at'       => $result->success() ? $now : null,
		);
		try {
			if ( isset( $this->pending[ $message ] ) ) {
				$id = $this->pending[ $message ];
				unset( $this->pending[ $message ] );
				$this->logs->update( $id, $result->success() ? array_merge( $fields, $this->effective_sender() ) : $fields );
				return;
			}
			// Configuration failures stop the message before the send hook, so no row exists yet.
			$options = $this->options();
			if ( $options && $options['logging_enabled'] ) {
				$this->logs->create( array_merge( $this->entry( $message, $result->provider(), $options ), $fields ) );
			}
		} catch ( \Throwable $exception ) {
			return;
		}
	}

	public function purge(): void {
		$options = $this->options();
		try {
			$this->logs->purge_older_than( $options ? $options['log_retention_days'] : SettingsRepository::RETENTION_DEFAULT );
		} catch ( \Throwable $exception ) {
			return;
		}
	}

	private function entry( MailMessage $message, string $provider, array $options ): array {
		$from = $message->from();
		$body = $message->body();
		$now  = gmdate( 'Y-m-d H:i:s' );
		return array(
			'message_id'    => '',
			'provider'      => substr( $provider, 0, 64 ),
			'from_email'    => substr( $from ? $from['email'] : $options['from_email'], 0, 320 ),
			'from_name'     => mb_substr( $from ? (string) $from['name'] : $options['from_name'], 0, 255 ),
			'recipients'    => $this->json(
				array(
					'to'       => $message->to(),
					'cc'       => $message->cc(),
					'bcc'      => $message->bcc(),
					'reply_to' => $message->reply_to(),
				)
			),
			'subject'       => mb_substr( $message->subject(), 0, 1000 ),
			'headers'       => $this->json( $message->headers() ),
			// Bodies are stored only when enabled, and only when they are bounded, valid UTF-8 text.
			'body'          => $options['log_body'] && strlen( $body ) <= self::MAX_BODY_BYTES && preg_match( '//u', $body ) ? $body : null,
			'content_type'  => $message->content_type(),
			// Attachments are never copied; only file names are kept so a later resend can explain why it is unavailable.
			'attachments'   => $this->json( array_values( array_map( 'wp_basename', $message->attachments() ) ) ),
			'status'        => 'sending',
			'error_code'    => '',
			'error_message' => null,
			'attempt_count' => 1,
			'created_at'    => $now,
			'updated_at'    => $now,
			'sent_at'       => null,
		);
	}

	private function effective_sender(): array {
		global $phpmailer;
		if ( ! is_object( $phpmailer ) || ! property_exists( $phpmailer, 'From' ) ) {
			return array();
		}
		// PHPMailer defines these public properties with upstream casing.
		// phpcs:disable WordPress.NamingConventions.ValidVariableName
		return array(
			'from_email' => substr( (string) $phpmailer->From, 0, 320 ),
			'from_name'  => mb_substr( (string) $phpmailer->FromName, 0, 255 ),
		);
		// phpcs:enable WordPress.NamingConventions.ValidVariableName
	}

	private function json( array $value ): string {
		$encoded = wp_json_encode( $value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		return is_string( $encoded ) ? $encoded : '[]';
	}

	private function options(): ?array {
		try {
			return $this->settings->get();
		} catch ( \RuntimeException $exception ) {
			return null;
		}
	}
}
