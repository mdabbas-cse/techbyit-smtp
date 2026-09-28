<?php
declare(strict_types=1);
require __DIR__ . '/phpmailer-stub.php';

use TechByIt\SMTP\Mail\MailMessage;
use TechByIt\SMTP\Mail\PHPMailerConfigurator;
use TechByIt\SMTP\Mail\MailManager;
use TechByIt\SMTP\Mail\MailErrorClassifier;

$message = MailMessage::from_wp_mail( array(
	'to' => 'one@example.test, two@example.test',
	'subject' => 'Hello',
	'message' => '<b>Body</b>',
	'headers' => array( 'From: Sender <sender@example.test>', 'Cc: cc@example.test', 'Bcc: bcc@example.test', 'Reply-To: reply@example.test', 'Content-Type: text/html; charset=UTF-8' ),
	'attachments' => array( '/tmp/techbyit-attachment.txt' ),
) );
if ( $message->to() !== array( 'one@example.test', 'two@example.test' ) || $message->cc() !== array( 'cc@example.test' ) || $message->bcc() !== array( 'bcc@example.test' ) || $message->reply_to() !== array( 'reply@example.test' ) || $message->content_type() !== 'text/html' || $message->charset() !== 'UTF-8' || $message->attachments() !== array( '/tmp/techbyit-attachment.txt' ) || $message->from()['email'] !== 'sender@example.test' ) {
	throw new RuntimeException( 'MailMessage normalization failed.' );
}
$injection = MailMessage::from_wp_mail( array( 'to' => 'one@example.test', 'subject' => 'Hi', 'message' => 'Body', 'headers' => "Cc: good@example.test\r\nBcc: bad@example.test\rInjected: yes" ) );
if ( $injection->bcc() !== array() ) {
	throw new RuntimeException( 'Malformed header was trusted.' );
}
$sensitive_header = MailMessage::from_wp_mail( array( 'to' => 'one@example.test', 'subject' => 'Hi', 'message' => 'Body', 'headers' => array( 'Authorization: Bearer test-only-secret', 'X-API-Key: test-only-secret', 'Cc: cc@example.test' ) ) );
if ( strpos( serialize( $sensitive_header->headers() ), 'test-only-secret' ) !== false || $sensitive_header->cc() !== array( 'cc@example.test' ) ) {
	throw new RuntimeException( 'Sensitive headers were retained in the mail DTO.' );
}
$named_sender = MailMessage::from_wp_mail( array( 'to' => 'one@example.test', 'subject' => 'Hi', 'message' => 'Body', 'headers' => array( 'From: "Acme, Inc" <sender@example.test>' ) ) );
if ( $named_sender->from()['email'] !== 'sender@example.test' ) {
	throw new RuntimeException( 'Quoted From display name was not preserved.' );
}
$classified = MailErrorClassifier::classify( 'SMTP Error: Could not authenticate with password test-only-secret' );
if ( $classified['code'] !== 'smtp_authentication_failed' || strpos( serialize( $classified ), 'test-only-secret' ) !== false ) {
	throw new RuntimeException( 'SMTP authentication error leaked a credential.' );
}
if ( MailErrorClassifier::classify( 'Connection timed out' )['code'] !== 'smtp_connection_failed' || MailErrorClassifier::classify( 'certificate verify failed' )['code'] !== 'smtp_tls_failed' ) {
	throw new RuntimeException( 'Common SMTP errors were not classified.' );
}

$manager = new MailManager( $manager );
$active = $manager->active_transport();
if ( ! $active->success() || $active->provider() !== 'custom_smtp' ) {
	throw new RuntimeException( 'Configured SMTP provider did not resolve.' );
}
$mailer = new PHPMailer\PHPMailer\PHPMailer();
( new PHPMailerConfigurator() )->configure( $mailer, $active->configuration() );
if ( $mailer->Mailer !== 'smtp' || $mailer->Host !== 'smtp.example.com' || $mailer->Port !== 587 || $mailer->SMTPSecure !== 'tls' || ! $mailer->SMTPAuth || $mailer->Password !== 'replacement-secret' || $mailer->SMTPDebug !== 0 ) {
	throw new RuntimeException( 'PHPMailer SMTP configuration failed.' );
}
( new PHPMailerConfigurator() )->configure( $mailer, array( 'host' => 'smtp.example.test', 'port' => 465, 'encryption' => 'ssl', 'authentication' => false ) );
if ( 'ssl' !== $mailer->SMTPSecure || $mailer->SMTPAutoTLS || $mailer->SMTPAuth || '' !== $mailer->Password || 10 !== $mailer->Timeout ) {
	throw new RuntimeException( 'SSL or disabled-auth configuration failed.' );
}
( new PHPMailerConfigurator() )->configure( $mailer, array( 'host' => 'localhost', 'port' => 25, 'encryption' => 'none', 'authentication' => false, 'timeout' => 7 ) );
if ( '' !== $mailer->SMTPSecure || $mailer->SMTPAutoTLS || 7 !== $mailer->Timeout ) {
	throw new RuntimeException( 'Plain SMTP or timeout configuration failed.' );
}
$settings->set_active_provider( 'sendgrid' );
$unsupported = $manager->active_transport();
if ( $unsupported->success() || $unsupported->error_code() !== 'provider_not_configured' ) {
	throw new RuntimeException( 'Unconfigured provider did not fail safely.' );
}
$settings->set_active_provider( '' );
if ( ! $manager->active_transport()->passthrough() ) {
	throw new RuntimeException( 'Unconfigured plugin changed WordPress mail.' );
}
$stored_general = $GLOBALS['test_options'][ TechByIt\SMTP\Settings\SettingsRepository::OPTION ];
$GLOBALS['test_options'][ TechByIt\SMTP\Settings\SettingsRepository::OPTION ] = array( 'version' => 999 );
if ( 'configuration_unavailable' !== $manager->active_transport()->error_code() ) {
	throw new RuntimeException( 'Corrupt settings caused an unsafe mail failure.' );
}
$GLOBALS['test_options'][ TechByIt\SMTP\Settings\SettingsRepository::OPTION ] = $stored_general;
$settings->set_active_provider( 'custom_smtp' );
$stored_providers = $GLOBALS['test_options'][ TechByIt\SMTP\Settings\ProviderSettingsRepository::OPTION ];
$GLOBALS['test_options'][ TechByIt\SMTP\Settings\ProviderSettingsRepository::OPTION ]['providers']['custom_smtp']['values'] = 'corrupt';
if ( 'configuration_unavailable' !== $manager->active_transport()->error_code() ) {
	throw new RuntimeException( 'Corrupt provider settings caused an unsafe mail failure.' );
}
$GLOBALS['test_options'][ TechByIt\SMTP\Settings\ProviderSettingsRepository::OPTION ] = $stored_providers;
$settings->set_active_provider( '' );
$manager_under_test = new TechByIt\SMTP\Providers\ProviderManager( $registry, $provider_settings, $settings );
$manager_under_test->save_configuration( 'sendgrid', array( 'api_key' => 'test-only-key' ) );
try {
	$manager_under_test->activate( 'sendgrid' );
	throw new RuntimeException( 'Provider without a sending transport was activated.' );
} catch ( TechByIt\SMTP\Support\ConfigurationException $expected ) {
}
echo "Phase 3 mail unit tests passed.\n";
