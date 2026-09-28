<?php
namespace TechByIt\SMTP\Mail;

final class MailResult {
	private $success;
	private $provider;
	private $message_id;
	private $error_code;
	private $error_message;
	private $duration;

	public function __construct( bool $success, string $provider, ?string $message_id = null, ?string $error_code = null, ?string $error_message = null, int $duration = 0 ) {
		$this->success       = $success;
		$this->provider      = $provider;
		$this->message_id    = $message_id;
		$this->error_code    = $error_code;
		$this->error_message = $error_message;
		$this->duration      = $duration;
	}
	public function success(): bool {
		return $this->success; }
	public function provider(): string {
		return $this->provider; }
	public function message_id(): ?string {
		return $this->message_id; }
	public function error_code(): ?string {
		return $this->error_code; }
	public function error_message(): ?string {
		return $this->error_message; }
	public function duration(): int {
		return $this->duration; }
	public function public_data(): array {
		return array(
			'success'       => $this->success,
			'provider'      => $this->provider,
			'message_id'    => $this->message_id,
			'error_code'    => $this->error_code,
			'error_message' => $this->error_message,
			'duration'      => $this->duration,
		);
	}
}
