<?php
namespace TechByIt\SMTP\Providers;

interface ProviderInterface {
	public function id(): string;
	/** @return array<string, mixed> */
	public function metadata(): array;
	/** @return array<int, array<string, mixed>> */
	public function fields(): array;
	/** @return array<string, mixed> */
	public function validate( array $input, array $existing ): array;
}
