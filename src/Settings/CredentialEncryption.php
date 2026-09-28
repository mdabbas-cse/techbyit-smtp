<?php
namespace TechByIt\SMTP\Settings;

final class CredentialEncryption {
	private const CIPHER = 'aes-256-gcm';

	private function key(): string {
		return hash_hkdf( 'sha256', wp_salt( 'auth' ) . wp_salt( 'secure_auth' ), 32, 'mailflow-smtp-credentials-v1' );
	}

	public function encrypt( string $plaintext ): string {
		$iv         = random_bytes( 12 );
		$tag        = '';
		$ciphertext = openssl_encrypt( $plaintext, self::CIPHER, $this->key(), OPENSSL_RAW_DATA, $iv, $tag );
		if ( false === $ciphertext || 16 !== strlen( $tag ) ) {
			throw new \RuntimeException( 'Credential encryption is unavailable.' );
		}
		return 'v1:' . base64_encode( $iv . $tag . $ciphertext );
	}

	public function decrypt( string $encoded ): string {
		if ( strpos( $encoded, 'v1:' ) !== 0 ) {
			throw new \RuntimeException( 'Stored credential cannot be read.' );
		}
		$bytes = base64_decode( substr( $encoded, 3 ), true );
		if ( false === $bytes || strlen( $bytes ) < 28 ) {
			throw new \RuntimeException( 'Stored credential cannot be read.' );
		}
		$plaintext = openssl_decrypt( substr( $bytes, 28 ), self::CIPHER, $this->key(), OPENSSL_RAW_DATA, substr( $bytes, 0, 12 ), substr( $bytes, 12, 16 ) );
		if ( false === $plaintext ) {
			throw new \RuntimeException( 'Stored credential cannot be read.' );
		}
		return $plaintext;
	}
}
