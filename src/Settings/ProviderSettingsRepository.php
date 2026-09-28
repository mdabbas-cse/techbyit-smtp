<?php
namespace TechByIt\SMTP\Settings;

use TechByIt\SMTP\Providers\ProviderInterface;

final class ProviderSettingsRepository {
	public const OPTION = 'mailflow_smtp_provider_settings';
	private $cipher;

	public function __construct( CredentialEncryption $cipher ) {
		$this->cipher = $cipher;
	}

	private function read(): array {
		$stored = get_option( self::OPTION, array() );
		if ( ! is_array( $stored ) || ( $stored['version'] ?? 1 ) !== 1 || ! is_array( $stored['providers'] ?? array() ) ) {
			throw new \RuntimeException( 'Stored provider settings version is unsupported.' );
		}
		return array(
			'version'   => 1,
			'providers' => $stored['providers'] ?? array(),
		);
	}

	public function private_values( ProviderInterface $provider, array $replacements = array() ): array {
		$stored = $this->read()['providers'][ $provider->id() ]['values'] ?? array();
		if ( ! is_array( $stored ) ) {
			throw new \RuntimeException( 'Stored provider settings are invalid.' );
		}
		$values = array();
		foreach ( $provider->fields() as $field ) {
			$name = $field['name'];
			if ( ! array_key_exists( $name, $stored ) ) {
				continue;
			}
			if ( ! empty( $field['secret'] ) && isset( $replacements[ $name ] ) && is_string( $replacements[ $name ] ) && '' !== $replacements[ $name ] ) {
				continue;
			}
			$values[ $name ] = ! empty( $field['secret'] ) ? $this->cipher->decrypt( $stored[ $name ] ) : $stored[ $name ];
		}
		return $values;
	}

	public function save( ProviderInterface $provider, array $values ): void {
		$stored = $this->read();
		$safe   = array();
		foreach ( $provider->fields() as $field ) {
			$name = $field['name'];
			if ( ! array_key_exists( $name, $values ) ) {
				continue;
			}
			$safe[ $name ] = ! empty( $field['secret'] ) ? $this->cipher->encrypt( $values[ $name ] ) : $values[ $name ];
		}
		$stored['providers'][ $provider->id() ] = array(
			'values'        => $safe,
			'authorization' => array( 'status' => 'disconnected' ),
		);
		if ( get_option( self::OPTION, null ) === null ) {
			add_option( self::OPTION, $stored, '', 'no' );
		} else {
			update_option( self::OPTION, $stored, false );
		}
	}

	public function public_values( ProviderInterface $provider ): array {
		$values = $this->read()['providers'][ $provider->id() ]['values'] ?? array();
		if ( ! is_array( $values ) ) {
			throw new \RuntimeException( 'Stored provider settings are invalid.' );
		}
		$public = array();
		foreach ( $provider->fields() as $field ) {
			$name = $field['name'];
			if ( ! empty( $field['secret'] ) ) {
				$configured = false;
				if ( isset( $values[ $name ] ) && is_string( $values[ $name ] ) && '' !== $values[ $name ] ) {
					try {
						$configured = $this->cipher->decrypt( $values[ $name ] ) !== '';
					} catch ( \RuntimeException $exception ) {
						$configured = false;
					}
				}
				$public[ $name ] = array( 'configured' => $configured );
			} elseif ( array_key_exists( $name, $values ) ) {
				$public[ $name ] = $values[ $name ];
			}
		}
		return $public;
	}

	public function authorization_status( ProviderInterface $provider ): string {
		$status = $this->read()['providers'][ $provider->id() ]['authorization']['status'] ?? 'disconnected';
		return in_array( $status, array( 'disconnected', 'connected', 'expired' ), true ) ? $status : 'disconnected';
	}
}
