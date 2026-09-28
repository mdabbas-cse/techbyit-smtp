<?php
namespace TechByIt\SMTP\Support;

final class ConfigurationException extends \InvalidArgumentException {
	/** @var array<string, string> */
	private $field_errors;

	public function __construct( string $message, array $field_errors = array() ) {
		parent::__construct( $message );
		$this->field_errors = $field_errors;
	}

	/** @return array<string, string> */
	public function errors(): array {
		return $this->field_errors;
	}
}
