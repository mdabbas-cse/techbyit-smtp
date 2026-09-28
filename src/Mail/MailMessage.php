<?php
namespace TechByIt\SMTP\Mail;

/** A safe view of wp_mail arguments for hooks and future logging. */
final class MailMessage {
	private $data;

	private function __construct( array $data ) {
		$this->data = $data; }

	public static function from_wp_mail( array $args ): self {
		$headers = $args['headers'] ?? array();
		if ( is_string( $headers ) ) {
			$headers = preg_match( '/\r(?!\n)/', $headers ) ? array() : preg_split( '/\r?\n/', $headers );
		}
		$parsed       = array(
			'cc'           => array(),
			'bcc'          => array(),
			'reply-to'     => array(),
			'from'         => array(),
			'content-type' => '',
			'charset'      => '',
		);
		$safe_headers = array();
		foreach ( is_array( $headers ) ? $headers : array() as $line ) {
			if ( ! is_string( $line ) || preg_match( '/[\r\n\x00-\x1F\x7F]/', $line ) || ! preg_match( '/^([A-Za-z-]+):\s*(.*)$/', $line, $match ) ) {
				continue;
			}
			$key = strtolower( $match[1] );
			if ( in_array( $key, array( 'cc', 'bcc', 'reply-to' ), true ) ) {
				$parsed[ $key ] = self::addresses( $match[2] );
				if ( $parsed[ $key ] ) {
					$safe_headers[] = ucfirst( $key ) . ': ' . implode( ', ', $parsed[ $key ] ); }
			} elseif ( 'from' === $key ) {
				$emails = self::addresses( $match[2] );
				if ( $emails ) {
					$parsed['from'] = array(
						'email' => $emails[0],
						'name'  => trim( preg_replace( '/<[^>]+>/', '', $match[2] ), " \"'" ),
					);
					$safe_headers[] = 'From: ' . $emails[0];
				}
			} elseif ( 'content-type' === $key && preg_match( '~^(text/plain|text/html)\b~i', $match[2], $type ) ) {
				$parsed['content-type'] = strtolower( $type[1] );
				if ( preg_match( '/charset\s*=\s*["\']?([A-Za-z0-9_-]+)/i', $match[2], $charset ) ) {
					$parsed['charset'] = $charset[1];
				}
				$safe_headers[] = 'Content-Type: ' . $parsed['content-type'] . ( $parsed['charset'] ? '; charset=' . $parsed['charset'] : '' );
			}
		}
		$attachments = $args['attachments'] ?? array();
		if ( is_string( $attachments ) ) {
			$attachments = preg_split( '/\r?\n/', $attachments ); }
		return new self(
			array(
				'to'           => self::addresses( $args['to'] ?? array() ),
				'cc'           => $parsed['cc'],
				'bcc'          => $parsed['bcc'],
				'reply_to'     => $parsed['reply-to'],
				'from'         => $parsed['from'],
				'subject'      => (string) ( $args['subject'] ?? '' ),
				'body'         => (string) ( $args['message'] ?? '' ),
				'headers'      => $safe_headers,
				'attachments'  => is_array( $attachments ) ? array_values( array_filter( $attachments, 'is_string' ) ) : array(),
				'content_type' => '' !== $parsed['content-type'] ? $parsed['content-type'] : 'text/plain',
				'charset'      => $parsed['charset'],
			)
		);
	}

	private static function addresses( $input ): array {
		$values = is_array( $input ) ? $input : explode( ',', (string) $input );
		$result = array();
		foreach ( $values as $value ) {
			if ( ! is_string( $value ) || preg_match( '/[\r\n\x00-\x1F\x7F]/', $value ) ) {
				continue; }
			$email = trim( preg_replace( '/^.*<([^>]+)>$/', '$1', $value ) );
			if ( filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
				$result[] = $email; }
		}
		return $result;
	}

	public function to(): array {
		return $this->data['to']; }
	public function cc(): array {
		return $this->data['cc']; }
	public function bcc(): array {
		return $this->data['bcc']; }
	public function reply_to(): array {
		return $this->data['reply_to']; }
	public function from(): array {
		return $this->data['from']; }
	public function subject(): string {
		return $this->data['subject']; }
	public function body(): string {
		return $this->data['body']; }
	public function headers(): array {
		return $this->data['headers']; }
	public function attachments(): array {
		return $this->data['attachments']; }
	public function content_type(): string {
		return $this->data['content_type']; }
	public function charset(): string {
		return $this->data['charset']; }
}
