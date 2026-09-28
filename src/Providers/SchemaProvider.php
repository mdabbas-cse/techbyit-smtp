<?php
namespace TechByIt\SMTP\Providers;

abstract class SchemaProvider implements ProviderInterface {
	/** @return array<string, mixed> */
	abstract protected function definition(): array;

	public function id(): string {
		return $this->definition()['id'];
	}

	public function fields(): array {
		return $this->definition()['fields'];
	}

	public function metadata(): array {
		return $this->definition();
	}

	public function validate( array $input, array $existing ): array {
		return ( new ConfigurationValidator() )->validate( $this->fields(), $input, $existing );
	}
}
