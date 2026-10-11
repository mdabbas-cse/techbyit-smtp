<?php
namespace TechByIt\SMTP\Settings;

use TechByIt\SMTP\Support\ConfigurationException;

final class SettingsRepository {
	public const OPTION            = 'mailflow_smtp_settings';
	public const RETENTION_DEFAULT = 30;
	public const RETENTION_MAX     = 365;

	public function get(): array {
		$stored = get_option( self::OPTION, array() );
		if ( ! is_array( $stored ) || ( $stored['version'] ?? 1 ) !== 1 ) {
			throw new \RuntimeException( 'Stored settings version is unsupported.' );
		}
		$retention = (int) ( $stored['log_retention_days'] ?? self::RETENTION_DEFAULT );
		return array(
			'active_provider'    => (string) ( $stored['active_provider'] ?? '' ),
			'from_name'          => (string) ( $stored['from_name'] ?? '' ),
			'from_email'         => (string) ( $stored['from_email'] ?? '' ),
			'logging_enabled'    => (bool) ( $stored['logging_enabled'] ?? true ),
			'log_body'           => (bool) ( $stored['log_body'] ?? false ),
			'log_retention_days' => $retention >= 1 && $retention <= self::RETENTION_MAX ? $retention : self::RETENTION_DEFAULT,
		);
	}

	public function save_general( array $input ): array {
		$errors = array();
		foreach ( $input as $key => $value ) {
			if ( in_array( $key, array( 'logging_enabled', 'log_body' ), true ) ) {
				if ( ! is_bool( $value ) ) {
					$errors[ $key ] = 'Must be true or false.';
				}
			} elseif ( 'log_retention_days' === $key ) {
				if ( ! is_int( $value ) || $value < 1 || $value > self::RETENTION_MAX ) {
					$errors[ $key ] = 'Enter a whole number of days from 1 to ' . self::RETENTION_MAX . '.';
				}
			} elseif ( ! in_array( $key, array( 'from_name', 'from_email' ), true ) ) {
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
		foreach ( array( 'logging_enabled', 'log_body', 'log_retention_days' ) as $key ) {
			if ( array_key_exists( $key, $input ) ) {
				$settings[ $key ] = $input[ $key ];
			}
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
