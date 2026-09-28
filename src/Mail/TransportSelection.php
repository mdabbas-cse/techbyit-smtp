<?php
namespace TechByIt\SMTP\Mail;

final class TransportSelection {
	private $provider;
	private $configuration;
	private $error_code;
	private $passthrough;
	public function __construct( string $provider, array $configuration = array(), ?string $error_code = null, bool $passthrough = false ) {
		$this->provider      = $provider;
		$this->configuration = $configuration;
		$this->error_code    = $error_code;
		$this->passthrough   = $passthrough;
	}
	public function provider(): string {
		return $this->provider; }
	public function configuration(): array {
		return $this->configuration; }
	public function error_code(): ?string {
		return $this->error_code; }
	public function success(): bool {
		return null === $this->error_code; }
	public function passthrough(): bool {
		return $this->passthrough; }
}
