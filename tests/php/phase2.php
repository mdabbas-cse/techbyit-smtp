<?php
declare(strict_types=1);

use TechByIt\SMTP\Providers\ProviderManager;
use TechByIt\SMTP\Providers\ProviderRegistry;
use TechByIt\SMTP\Settings\CredentialEncryption;
use TechByIt\SMTP\Settings\ProviderSettingsRepository;
use TechByIt\SMTP\Settings\SettingsRepository;
use TechByIt\SMTP\Support\ConfigurationException;

$GLOBALS['test_options'] = array();
function get_option( $name, $default = false ) {
	return $GLOBALS['test_options'][ $name ] ?? $default; }
function add_option( $name, $value, $deprecated = '', $autoload = 'yes' ) {
	$GLOBALS['test_options'][ $name ] = $value;
	return true; }
function update_option( $name, $value, $autoload = null ) {
	$GLOBALS['test_options'][ $name ] = $value;
	return true; }
function wp_salt( $scheme = 'auth' ) {
	return 'test-only-wordpress-salt-' . $scheme . ( $GLOBALS['test_salt_suffix'] ?? '' ); }
function sanitize_text_field( $value ) {
	return trim( strip_tags( $value ) ); }
function sanitize_email( $value ) {
	return trim( $value ); }
function is_email( $value ) {
	return filter_var( $value, FILTER_VALIDATE_EMAIL ) ?: false; }

$registry = ProviderRegistry::defaults();
if ( count( $registry->all() ) !== 9 || $registry->get( 'custom_smtp' )->id() !== 'custom_smtp' || $registry->get( 'missing' ) !== null ) {
	throw new RuntimeException( 'Provider registration or lookup failed.' );
}
try {
	$registry->register( $registry->get( 'custom_smtp' ) );
	throw new RuntimeException( 'Duplicate provider was accepted.' );
} catch ( InvalidArgumentException $expected ) {
}
echo "Provider registry tests passed.\n";

foreach ( $registry->all() as $provider ) {
	$metadata = $provider->metadata();
	if ( $metadata['id'] !== $provider->id() || empty( $metadata['fields'] ) || empty( $metadata['authentication'] ) ) {
		throw new RuntimeException( 'Provider metadata is incomplete.' );
	}
}

$cipher    = new CredentialEncryption();
$encrypted = $cipher->encrypt( 'secret-123' );
if ( $encrypted === 'secret-123' || $cipher->decrypt( $encrypted ) !== 'secret-123' ) {
	throw new RuntimeException( 'Credential encryption failed.' );
}
try {
	$cipher->decrypt( $encrypted . 'tampered' );
	throw new RuntimeException( 'Tampered credential was accepted.' );
} catch ( RuntimeException $expected ) {
}
echo "Credential encryption tests passed.\n";

$settings          = new SettingsRepository();
$provider_settings = new ProviderSettingsRepository( $cipher );
$manager           = new ProviderManager( $registry, $provider_settings, $settings );

try {
	$manager->provider( 'unknown' );
	throw new RuntimeException( 'Unknown provider resolved.' );
} catch ( ConfigurationException $expected ) {
}

$settings->save_general(
	array(
		'from_name'  => 'TechByIt SMTP',
		'from_email' => 'sender@example.com',
	)
);
if ( $settings->get()['from_email'] !== 'sender@example.com' ) {
	throw new RuntimeException( 'General settings were not saved.' );
}
try {
	$settings->save_general( array( 'from_email' => 'bad-email' ) );
	throw new RuntimeException( 'Invalid sender address was accepted.' );
} catch ( ConfigurationException $expected ) {
}

try {
	$manager->save_configuration( 'custom_smtp', array( 'port' => 70000 ) );
	throw new RuntimeException( 'Invalid SMTP configuration was accepted.' );
} catch ( ConfigurationException $expected ) {
	if ( ! isset( $expected->errors()['host'] ) || ! isset( $expected->errors()['port'] ) ) {
		throw new RuntimeException( 'Validation did not identify host and port.' );
	}
}

$public = $manager->save_configuration(
	'custom_smtp',
	array(
		'host'           => 'smtp.example.com',
		'port'           => 587,
		'encryption'     => 'tls',
		'authentication' => true,
		'username'       => 'mailer',
		'password'       => 'secret-123',
	)
);
if ( $public['values']['password'] !== array( 'configured' => true ) || strpos( json_encode( $public ), 'secret-123' ) !== false ) {
	throw new RuntimeException( 'Credential leaked in public settings.' );
}
if ( strpos( serialize( $GLOBALS['test_options'] ), 'secret-123' ) !== false ) {
	throw new RuntimeException( 'Credential stored in plaintext.' );
}
$manager->save_configuration( 'custom_smtp', array( 'password' => '' ) );
if ( $manager->get_provider_settings( 'custom_smtp' )['values']['password'] !== array( 'configured' => true ) ) {
	throw new RuntimeException( 'Blank password replacement erased the secret.' );
}
$manager->activate( 'custom_smtp' );
if ( $settings->get()['active_provider'] !== 'custom_smtp' ) {
	throw new RuntimeException( 'Active provider was not saved.' );
}
echo "Settings and activation tests passed.\n";

foreach ( array(
	array( 'port' => true ),
	array( 'encryption' => 'starttls' ),
	array( 'host' => 'bad..host' ),
	array( 'password' => '   ' ),
) as $invalid_input ) {
	try {
		$manager->save_configuration( 'custom_smtp', $invalid_input );
		throw new RuntimeException( 'Invalid SMTP update was accepted.' );
	} catch ( ConfigurationException $expected ) {
	}
}
echo "Provider field validation tests passed.\n";

$GLOBALS['test_salt_suffix'] = '-rotated';
if ( $manager->get_provider_settings( 'custom_smtp' )['values']['password'] !== array( 'configured' => false ) ) {
	throw new RuntimeException( 'Unreadable credential was reported as configured.' );
}
$manager->save_configuration( 'custom_smtp', array( 'password' => 'replacement-secret' ) );
if ( $manager->get_provider_settings( 'custom_smtp' )['values']['password'] !== array( 'configured' => true ) ) {
	throw new RuntimeException( 'Credential could not be replaced after salt rotation.' );
}
echo "Credential replacement after salt rotation passed.\n";

$manager->save_configuration(
	'gmail',
	array(
		'client_id'     => 'client-id',
		'client_secret' => 'secret',
		'sender_email'  => 'admin@example.com',
	)
);
try {
	$manager->activate( 'gmail' );
	throw new RuntimeException( 'Disconnected OAuth provider was activated.' );
} catch ( ConfigurationException $expected ) {
}
if ( $manager->get_provider_settings( 'gmail' )['authorization']['status'] !== 'disconnected' ) {
	throw new RuntimeException( 'OAuth state is misleading.' );
}
echo "OAuth state tests passed.\n";
