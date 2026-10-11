<?php
declare(strict_types=1);

use TechByIt\SMTP\Database\Installer;
use TechByIt\SMTP\Logs\LogRepository;
use TechByIt\SMTP\Logs\MailLogger;
use TechByIt\SMTP\Mail\MailMessage;
use TechByIt\SMTP\Mail\MailResult;
use TechByIt\SMTP\Support\ConfigurationException;

function wp_json_encode( $data, $options = 0, $depth = 512 ) {
	return json_encode( $data, $options, $depth ); }
function wp_basename( $path, $suffix = '' ) {
	return basename( $path, $suffix ); }
function wp_timezone() {
	return new DateTimeZone( 'Asia/Dhaka' ); }

/** Records the writes MailLogger makes without a database. */
final class TestLogWpdb {
	public $prefix     = 'wp_';
	public $insert_id  = 0;
	public $last_error = '';
	public $rows       = array();
	public $fail       = false;
	public function insert( $table, $data ) {
		if ( $this->fail ) {
			throw new RuntimeException( 'Database is down.' ); }
		$this->insert_id                = count( $this->rows ) + 1;
		$this->rows[ $this->insert_id ] = $data;
		return 1;
	}
	public function update( $table, $data, $where ) {
		if ( ! isset( $this->rows[ $where['id'] ] ) ) {
			return 0; }
		$this->rows[ $where['id'] ] = array_merge( $this->rows[ $where['id'] ], $data );
		return 1;
	}
}

function techbyit_expect_invalid( callable $callback, string $field, string $message ): void {
	try {
		$callback();
	} catch ( ConfigurationException $expected ) {
		if ( ! isset( $expected->errors()[ $field ] ) ) {
			throw new RuntimeException( $message . ' (wrong field)' );
		}
		return;
	}
	throw new RuntimeException( $message );
}

$sql = Installer::schema_sql( 'wp_', '' );
foreach ( array( "error_code varchar(64) NOT NULL DEFAULT ''", 'attachments longtext NULL', 'KEY created_at (created_at)' ) as $fragment ) {
	if ( strpos( $sql, $fragment ) === false ) {
		throw new RuntimeException( 'Log schema v2 missing: ' . $fragment );
	}
}
if ( Installer::VERSION !== '2' ) {
	throw new RuntimeException( 'Log schema version was not raised for the new columns.' );
}
echo "Log schema v2 tests passed.\n";

$defaults = $settings->get();
if ( true !== $defaults['logging_enabled'] || false !== $defaults['log_body'] || 30 !== $defaults['log_retention_days'] ) {
	throw new RuntimeException( 'Logging defaults are unsafe for existing sites.' );
}
techbyit_expect_invalid( static function () use ( $settings ) { $settings->save_general( array( 'logging_enabled' => 'yes' ) ); }, 'logging_enabled', 'Non-boolean logging flag was accepted.' );
techbyit_expect_invalid( static function () use ( $settings ) { $settings->save_general( array( 'log_body' => 1 ) ); }, 'log_body', 'Non-boolean body flag was accepted.' );
foreach ( array( 0, 366, '30', 7.5 ) as $invalid_days ) {
	techbyit_expect_invalid( static function () use ( $settings, $invalid_days ) { $settings->save_general( array( 'log_retention_days' => $invalid_days ) ); }, 'log_retention_days', 'Invalid retention was accepted.' );
}
$saved = $settings->save_general( array( 'logging_enabled' => false, 'log_body' => true, 'log_retention_days' => 7 ) );
if ( false !== $saved['logging_enabled'] || true !== $saved['log_body'] || 7 !== $saved['log_retention_days'] || 'sender@example.com' !== $saved['from_email'] ) {
	throw new RuntimeException( 'Logging settings were not saved or replaced the sender.' );
}
$stored_general = $GLOBALS['test_options'][ TechByIt\SMTP\Settings\SettingsRepository::OPTION ];
$GLOBALS['test_options'][ TechByIt\SMTP\Settings\SettingsRepository::OPTION ]['log_retention_days'] = 0;
if ( 30 !== $settings->get()['log_retention_days'] ) {
	throw new RuntimeException( 'Corrupt retention could purge every log.' );
}
$GLOBALS['test_options'][ TechByIt\SMTP\Settings\SettingsRepository::OPTION ] = $stored_general;
echo "Logging settings tests passed.\n";

$repository = new LogRepository();
$filters    = $repository->normalize_filters( array( 'status' => 'failed', 'provider' => 'custom_smtp', 'search' => ' invoice ', 'date_from' => '2026-10-01', 'date_to' => '2026-10-11', 'page' => '2', 'per_page' => '50', 'rest_route' => '/ignored' ) );
if ( $filters !== array( 'search' => 'invoice', 'status' => 'failed', 'provider' => 'custom_smtp', 'date_from' => '2026-10-01', 'date_to' => '2026-10-11', 'page' => 2, 'per_page' => 50 ) ) {
	throw new RuntimeException( 'Log filters were not normalized.' );
}
if ( 1 !== $repository->normalize_filters( array() )['page'] || 20 !== $repository->normalize_filters( array( 'status' => '' ) )['per_page'] ) {
	throw new RuntimeException( 'Log filter defaults are wrong.' );
}
foreach ( array(
	'status'    => 'deleted',
	'provider'  => 'Custom SMTP; DROP',
	'date_from' => '2026-02-30',
	'page'      => '0',
	'per_page'  => '101',
	'search'    => str_repeat( 'a', 201 ),
) as $field => $invalid_value ) {
	techbyit_expect_invalid( static function () use ( $repository, $field, $invalid_value ) { $repository->normalize_filters( array( $field => $invalid_value ) ); }, $field, 'Invalid log filter was accepted: ' . $field );
}
techbyit_expect_invalid( static function () use ( $repository ) { $repository->normalize_filters( array( 'status' => array( 'failed' ) ) ); }, 'status', 'Array filter was accepted.' );
techbyit_expect_invalid( static function () use ( $repository ) { $repository->normalize_filters( array( 'date_from' => '2026-10-11', 'date_to' => '2026-10-01' ) ); }, 'date_to', 'Reversed date range was accepted.' );
echo "Log filter validation tests passed.\n";

$wpdb   = new TestLogWpdb();
$logger = new MailLogger( $repository, $settings );
$settings->save_general( array( 'logging_enabled' => true, 'log_body' => false ) );
$message = MailMessage::from_wp_mail(
	array(
		'to'          => 'customer@example.test',
		'subject'     => 'Invoice ready',
		'message'     => 'Private body',
		'headers'     => array( 'Authorization: Bearer test-only-secret', 'Cc: cc@example.test', 'Bcc: bcc@example.test' ),
		'attachments' => array( '/var/private/uploads/invoice.pdf' ),
	)
);
$logger->started( $message, 'custom_smtp' );
$row = $wpdb->rows[1] ?? array();
if ( 'sending' !== ( $row['status'] ?? '' ) || 'custom_smtp' !== $row['provider'] || null !== $row['body'] || 1 !== $row['attempt_count'] ) {
	throw new RuntimeException( 'Sending log row was not created safely.' );
}
if ( '["invoice.pdf"]' !== $row['attachments'] || strpos( serialize( $row ), '/var/private' ) !== false || strpos( serialize( $row ), 'test-only-secret' ) !== false ) {
	throw new RuntimeException( 'Log row leaked an attachment path or sensitive header.' );
}
if ( json_decode( $row['recipients'], true ) !== array( 'to' => array( 'customer@example.test' ), 'cc' => array( 'cc@example.test' ), 'bcc' => array( 'bcc@example.test' ), 'reply_to' => array() ) || 'sender@example.com' !== $row['from_email'] ) {
	throw new RuntimeException( 'Log row recipients or default sender are wrong.' );
}
$GLOBALS['phpmailer'] = (object) array(
	'From'     => 'actual@example.test',
	'FromName' => 'Actual Sender',
);
$logger->finished( new MailResult( true, 'custom_smtp', '<id@example.test>', null, null, 12 ), $message );
$row = $wpdb->rows[1];
if ( 'sent' !== $row['status'] || '<id@example.test>' !== $row['message_id'] || null === $row['sent_at'] || 'actual@example.test' !== $row['from_email'] || 'Actual Sender' !== $row['from_name'] || count( $wpdb->rows ) !== 1 ) {
	throw new RuntimeException( 'Sent result did not update the existing log row.' );
}

$unconfigured = MailMessage::from_wp_mail( array( 'to' => 'one@example.test', 'subject' => 'Config failure', 'message' => 'Body' ) );
$logger->finished( new MailResult( false, 'sendgrid', null, 'transport_unavailable', 'Sending is not available for the selected provider yet.' ), $unconfigured );
$row = $wpdb->rows[2] ?? array();
if ( 'failed' !== ( $row['status'] ?? '' ) || 'transport_unavailable' !== $row['error_code'] || 'sendgrid' !== $row['provider'] || null !== $row['sent_at'] ) {
	throw new RuntimeException( 'Failure before sending was not logged.' );
}

$settings->save_general( array( 'log_body' => true ) );
$with_body = MailMessage::from_wp_mail( array( 'to' => 'one@example.test', 'subject' => 'Body', 'message' => '<p>Kept body</p>' ) );
$logger->started( $with_body, 'custom_smtp' );
if ( '<p>Kept body</p>' !== $wpdb->rows[3]['body'] ) {
	throw new RuntimeException( 'Enabled body logging did not store the body.' );
}
$binary = MailMessage::from_wp_mail( array( 'to' => 'one@example.test', 'subject' => 'Binary', 'message' => "\xB1\x31" ) );
$logger->started( $binary, 'custom_smtp' );
if ( null !== $wpdb->rows[4]['body'] ) {
	throw new RuntimeException( 'Invalid UTF-8 body was stored.' );
}

$settings->save_general( array( 'logging_enabled' => false ) );
$before   = count( $wpdb->rows );
$disabled = MailMessage::from_wp_mail( array( 'to' => 'one@example.test', 'subject' => 'Off', 'message' => 'Body' ) );
$logger->started( $disabled, 'custom_smtp' );
$logger->finished( new MailResult( false, 'custom_smtp', null, 'mail_send_failed', 'Failed.' ), $disabled );
if ( count( $wpdb->rows ) !== $before ) {
	throw new RuntimeException( 'Disabled logging still wrote rows.' );
}

$settings->save_general( array( 'logging_enabled' => true ) );
$wpdb->fail = true;
$logger->started( MailMessage::from_wp_mail( array( 'to' => 'one@example.test', 'subject' => 'Down', 'message' => 'Body' ) ), 'custom_smtp' );
$logger->finished( new MailResult( false, 'custom_smtp', null, 'mail_send_failed', 'Failed.' ), $disabled );
$logger->finished( 'not a result', null );
$wpdb->fail = false;
echo "Mail logger tests passed.\n";
