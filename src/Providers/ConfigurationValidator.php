<?php
namespace TechByIt\SMTP\Providers;

use TechByIt\SMTP\Support\ConfigurationException;

final class ConfigurationValidator {
	public function validate( array $fields, array $input, array $existing ): array {
		$known  = array_column( $fields, 'name' );
		$errors = array();
		foreach ( $input as $name => $value ) {
			if ( ! in_array( $name, $known, true ) ) {
				$errors[ (string) $name ] = 'Unknown field.';
			}
		}
		$values = $existing;
		foreach ( $fields as $field ) {
			$name = $field['name'];
			if ( ! array_key_exists( $name, $input ) ) {
				continue;
			}
			$value = $input[ $name ];
			$type  = $field['type'];
			if ( 'toggle' === $type ) {
				if ( ! is_bool( $value ) ) {
					$errors[ $name ] = 'Must be true or false.';
				} else {
					$values[ $name ] = $value;
				}
				continue;
			}
			if ( 'number' === $type ) {
				$numeric_input = is_int( $value ) || ( is_string( $value ) && ctype_digit( $value ) );
				$number        = $numeric_input ? filter_var( $value, FILTER_VALIDATE_INT ) : false;
				if ( false === $number || $number < ( $field['min'] ?? 1 ) || $number > ( $field['max'] ?? 65535 ) ) {
					$errors[ $name ] = 'Must be a valid number in the allowed range.';
				} else {
					$values[ $name ] = $number;
				}
				continue;
			}
			if ( ! is_string( $value ) || strlen( $value ) > ( $field['max_length'] ?? 4096 ) || preg_match( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value ) ) {
				$errors[ $name ] = 'Must be valid text.';
				continue;
			}
			if ( ! empty( $field['secret'] ) ) {
				if ( '' !== $value && '' === trim( $value ) ) {
					$errors[ $name ] = 'Credential cannot be blank.';
				} elseif ( '' !== $value ) {
					$values[ $name ] = $value;
				}
				continue;
			}
			$value = sanitize_text_field( $value );
			if ( 'select' === $type && ! in_array( $value, $field['options'], true ) ) {
				$errors[ $name ] = 'Choose an allowed option.';
				continue;
			}
			if ( 'email' === $type && '' !== $value && ! is_email( $value ) ) {
				$errors[ $name ] = 'Enter a valid email address.';
				continue;
			}
			if ( 'host' === ( $field['format'] ?? '' ) && '' !== $value && 'localhost' !== $value
				&& ! filter_var( $value, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME )
				&& ! filter_var( trim( $value, '[]' ), FILTER_VALIDATE_IP ) ) {
				$errors[ $name ] = 'Enter a valid SMTP host.';
				continue;
			}
			if ( 'domain' === ( $field['format'] ?? '' ) && '' !== $value && ! filter_var( $value, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME ) ) {
				$errors[ $name ] = 'Enter a valid domain.';
				continue;
			}
			if ( 'region' === ( $field['format'] ?? '' ) && '' !== $value && ! preg_match( '/^[a-z0-9-]+$/', $value ) ) {
				$errors[ $name ] = 'Enter a valid region.';
				continue;
			}
			$values[ $name ] = $value;
		}
		foreach ( $fields as $field ) {
			$name     = $field['name'];
			$required = ! empty( $field['required'] );
			if ( isset( $field['required_when'] ) ) {
				$required = ( $values[ $field['required_when'] ] ?? false ) === true;
			}
			if ( $required && ( ! isset( $values[ $name ] ) || '' === $values[ $name ] ) ) {
				$errors[ $name ] = 'This field is required.';
			}
		}
		if ( $errors ) {
			// Field errors are returned as REST data, not rendered as HTML here.
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new ConfigurationException( 'Please correct the highlighted fields.', $errors );
		}
		return $values;
	}
}
