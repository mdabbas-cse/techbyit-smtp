<?php
namespace TechByIt\SMTP\Providers;

use TechByIt\SMTP\Providers\Builtin\AmazonSESProvider;
use TechByIt\SMTP\Providers\Builtin\BrevoProvider;
use TechByIt\SMTP\Providers\Builtin\CustomSMTPProvider;
use TechByIt\SMTP\Providers\Builtin\GmailProvider;
use TechByIt\SMTP\Providers\Builtin\GoogleWorkspaceProvider;
use TechByIt\SMTP\Providers\Builtin\MailgunProvider;
use TechByIt\SMTP\Providers\Builtin\Microsoft365Provider;
use TechByIt\SMTP\Providers\Builtin\PostmarkProvider;
use TechByIt\SMTP\Providers\Builtin\SendGridProvider;

final class ProviderRegistry {
	/** @var array<string, ProviderInterface> */
	private $providers = array();

	public static function defaults(): self {
		$registry = new self();
		foreach ( array(
			new CustomSMTPProvider(),
			new GmailProvider(),
			new GoogleWorkspaceProvider(),
			new Microsoft365Provider(),
			new AmazonSESProvider(),
			new SendGridProvider(),
			new MailgunProvider(),
			new BrevoProvider(),
			new PostmarkProvider(),
		) as $provider ) {
			$registry->register( $provider );
		}
		return $registry;
	}

	public function register( ProviderInterface $provider ): void {
		$id       = $provider->id();
		$metadata = $provider->metadata();
		if ( ! preg_match( '/^[a-z0-9_]+$/', $id ) || isset( $this->providers[ $id ] ) ) {
			throw new \InvalidArgumentException( 'Invalid or duplicate provider ID.' );
		}
		if ( ( $metadata['id'] ?? '' ) !== $id ) {
			throw new \InvalidArgumentException( 'Provider metadata ID does not match.' );
		}
		if ( ! in_array( $metadata['authentication'] ?? '', array( 'smtp_credentials', 'api_key', 'oauth2', 'access_key', 'provider_specific' ), true ) ) {
			throw new \InvalidArgumentException( 'Unsupported authentication type.' );
		}
		$fields = $metadata['fields'] ?? null;
		if ( ! is_array( $fields ) || ! $fields ) {
			throw new \InvalidArgumentException( 'Provider fields are required.' );
		}
		$names = array();
		foreach ( $fields as $field ) {
			$name = $field['name'] ?? '';
			$type = $field['type'] ?? '';
			if ( ! is_string( $name ) || ! preg_match( '/^[a-z0-9_]+$/', $name ) || in_array( $name, $names, true )
				|| ! in_array( $type, array( 'text', 'email', 'number', 'select', 'toggle', 'password' ), true ) ) {
				throw new \InvalidArgumentException( 'Invalid provider field schema.' );
			}
			if ( ! empty( $field['secret'] ) && 'password' !== $type ) {
				throw new \InvalidArgumentException( 'Secret fields must use password input.' );
			}
			if ( 'select' === $type && ( ! isset( $field['options'] ) || ! is_array( $field['options'] ) || ! $field['options'] ) ) {
				throw new \InvalidArgumentException( 'Select field requires options.' );
			}
			$names[] = $name;
		}
		$this->providers[ $id ] = $provider;
	}

	public function get( string $id ): ?ProviderInterface {
		return $this->providers[ $id ] ?? null;
	}

	/** @return array<string, ProviderInterface> */
	public function all(): array {
		return $this->providers;
	}
}
