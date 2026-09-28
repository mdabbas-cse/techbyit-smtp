<?php
namespace TechByIt\SMTP\Settings;

use TechByIt\SMTP\Support\ConfigurationException;

final class SettingsRepository {
	public const OPTION = 'mailflow_smtp_settings';

	public function get(): array {
		$stored = get_option( self::OPTION, array() );
		if ( ! is_array( $stored ) || ( $stored['version'] ?? 1 ) !== 1 ) {
			throw new \RuntimeException( 'Stored settings version is unsupported.' );
		}
		return array(
			'active_provider' => (string) ( $stored['active_provider'] ?? '' ),
			'from_name'       => (string) ( $stored['from_name'] ?? '' ),
			'from_email'      => (string) ( $stored['from_email'] ?? '' ),
		);
	}

	public function save_general( array $input ): array {
		$errors = array();
		foreach ( $input as $key => $value ) {
			if ( ! in_array( $key, array( 'from_name', 'from_email' ), true ) ) {
				$errors[ (string) $key ] = 'Unknown setting.';
			} elseif ( ! is_string( $value ) || strlen( $value ) > 320 ) {
				$errors[ (string) $key ] = 'Must be valid text.';
			}
		}
		if ( $errors ) {
			// Field errors are returned as REST data, not rendered as HTML here.
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new ConfigurationException( 'Please correct the settings.', $errors );
		}
		$settings = $this->get();
		if ( array_key_exists( 'from_name', $input ) ) {
			$settings['from_name'] = sanitize_text_field( $input['from_name'] );
		}
		if ( array_key_exists( 'from_email', $input ) ) {
			$raw_email = trim( $input['from_email'] );
			$email     = sanitize_email( $raw_email );
			if ( '' !== $raw_email && ( ! is_email( $raw_email ) || $email !== $raw_email ) ) {
				throw new ConfigurationException( 'Please correct the settings.', array( 'from_email' => 'Enter a valid email address.' ) );
			}
			$settings['from_email'] = $email;
		}
		$this->persist( $settings );
		return $settings;
	}

	public function set_active_provider( string $id ): void {
		$settings                    = $this->get();
		$settings['active_provider'] = $id;
		$this->persist( $settings );
	}

	private function persist( array $settings ): void {
		$value = array_merge( array( 'version' => 1 ), $settings );
		if ( get_option( self::OPTION, null ) === null ) {
			add_option( self::OPTION, $value, '', 'no' );
		} else {
			update_option( self::OPTION, $value, false );
		}
	}
}
