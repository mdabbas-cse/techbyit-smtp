<?php
/** Run inside the local WordPress container after activating the plugin. */
declare(strict_types=1);

$_SERVER['HTTP_HOST'] = 'localhost:8080';
require dirname( __DIR__, 5 ) . '/wp-load.php';

function techbyit_assert( $condition, string $message ): void {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

function techbyit_post( string $path, array $body ): WP_REST_Response {
	$request = new WP_REST_Request( 'POST', '/techbyit-smtp/v1/' . $path );
	$request->set_header( 'Content-Type', 'application/json' );
	$request->set_body( wp_json_encode( $body ) );
	return rest_do_request( $request );
}

function techbyit_get( string $path, array $query = array() ): WP_REST_Response {
	$request = new WP_REST_Request( 'GET', '/techbyit-smtp/v1/' . $path );
	$request->set_query_params( $query );
	return rest_do_request( $request );
}

function techbyit_delete( string $path ): WP_REST_Response {
	return rest_do_request( new WP_REST_Request( 'DELETE', '/techbyit-smtp/v1/' . $path ) );
}

foreach ( array( 'providers', 'providers/custom_smtp', 'providers/custom_smtp/settings', 'settings', 'logs', 'logs/1', 'dashboard' ) as $path ) {
	techbyit_assert( rest_do_request( '/techbyit-smtp/v1/' . $path )->get_status() === 401, 'Guest accessed ' . $path . '.' );
}
foreach ( array( 'providers/custom_smtp/settings', 'providers/custom_smtp/activate', 'settings', 'test-email' ) as $path ) {
	techbyit_assert( techbyit_post( $path, array() )->get_status() === 401, 'Guest wrote ' . $path . '.' );
}
foreach ( array( 'logs', 'logs/1' ) as $path ) {
	techbyit_assert( techbyit_delete( $path )->get_status() === 401, 'Guest deleted ' . $path . '.' );
}
wp_set_current_user( 1 );
techbyit_assert( '2' === get_option( 'mailflow_smtp_db_version' ), 'Log schema migration did not run.' );
global $wpdb;
$log_table = $wpdb->prefix . 'mailflow_smtp_logs';
foreach ( array( 'error_code', 'attachments' ) as $column ) {
	techbyit_assert( null !== $wpdb->get_var( $wpdb->prepare( 'SHOW COLUMNS FROM %i LIKE %s', $log_table, $column ) ), 'Log column missing: ' . $column );
}
techbyit_assert( false !== wp_next_scheduled( \TechByIt\SMTP\Logs\MailLogger::PURGE_HOOK ), 'Log retention is not scheduled.' );
techbyit_assert( techbyit_delete( 'logs' )->get_status() === 200, 'Logs could not be cleared.' );
$catalog = rest_do_request( '/techbyit-smtp/v1/providers' );
techbyit_assert( rest_do_request( '/flowmail-smtp/v1/providers' )->get_status() === 200, 'Previous REST compatibility route is unavailable.' );
techbyit_assert( rest_do_request( '/mailflow-smtp/v1/providers' )->get_status() === 200, 'Legacy REST route is unavailable.' );
techbyit_assert( $catalog->get_status() === 200 && count( $catalog->get_data()['data'] ) === 9, 'Provider list failed.' );
$schema = rest_do_request( '/techbyit-smtp/v1/providers/custom_smtp' );
techbyit_assert( $schema->get_status() === 200 && count( $schema->get_data()['data']['fields'] ) === 7, 'Dynamic SMTP schema failed.' );
$bootstrap = rest_do_request( '/techbyit-smtp/v1/bootstrap' );
techbyit_assert( $bootstrap->get_data()['version'] === TECHBYIT_SMTP_VERSION, 'Phase 1 bootstrap changed.' );

$saved = techbyit_post(
	'providers/custom_smtp/settings',
	array(
		'host'           => 'smtp.example.com',
		'port'           => 587,
		'encryption'     => 'tls',
		'authentication' => true,
		'username'       => 'mailer',
		'password'       => 'integration-only-secret',
	)
);
techbyit_assert( $saved->get_status() === 200, 'SMTP settings were not saved.' );
$read = rest_do_request( '/techbyit-smtp/v1/providers/custom_smtp/settings' );
techbyit_assert( $read->get_data()['data']['values']['password'] === array( 'configured' => true ), 'Secret configured flag missing.' );
foreach ( array( $saved->get_data(), $read->get_data(), get_option( 'mailflow_smtp_provider_settings' ) ) as $value ) {
	techbyit_assert( strpos( wp_json_encode( $value ), 'integration-only-secret' ) === false, 'Secret leaked to response or storage.' );
}
$stored_option = get_option( 'mailflow_smtp_provider_settings' );
techbyit_assert( 1 === $stored_option['version'], 'Provider option is not versioned.' );
global $wpdb;
$autoload = $wpdb->get_var( $wpdb->prepare( 'SELECT autoload FROM %i WHERE option_name = %s', $wpdb->options, 'mailflow_smtp_provider_settings' ) );
techbyit_assert( ! in_array( $autoload, array( 'yes', 'on', 'auto', 'auto-on' ), true ), 'Credentials option is autoloaded.' );
$activated = techbyit_post( 'providers/custom_smtp/activate', array() );
techbyit_assert( $activated->get_status() === 200, 'Configured SMTP provider could not be activated.' );
$general = rest_do_request( '/techbyit-smtp/v1/settings' );
techbyit_assert( $general->get_data()['data']['active_provider'] === 'custom_smtp', 'Active provider was not stored.' );
$general_saved = techbyit_post(
	'settings',
	array(
		'from_name'          => 'TechByIt SMTP',
		'from_email'         => 'sender@example.test',
		'logging_enabled'    => true,
		'log_body'           => true,
		'log_retention_days' => 30,
	)
);
techbyit_assert( $general_saved->get_status() === 200 && $general_saved->get_data()['data']['from_email'] === 'sender@example.test', 'General settings save failed.' );

$invalid = techbyit_post( 'providers/custom_smtp/settings', array( 'port' => 70000 ) );
techbyit_assert( $invalid->get_status() === 400 && isset( $invalid->get_data()['errors']['port'] ), 'Port validation failed.' );
$unknown = rest_do_request( '/techbyit-smtp/v1/providers/unknown' );
techbyit_assert( $unknown->get_status() === 404, 'Unknown provider did not return 404.' );
$oauth = techbyit_post(
	'providers/gmail/settings',
	array(
		'client_id'     => 'test-client',
		'client_secret' => 'oauth-secret',
		'sender_email'  => 'admin@example.test',
	)
);
techbyit_assert( $oauth->get_status() === 200, 'OAuth client configuration failed.' );
$oauth_activation = techbyit_post( 'providers/gmail/activate', array() );
techbyit_assert( $oauth_activation->get_status() === 400, 'Disconnected OAuth provider was activated.' );
echo "WordPress provider REST integration passed.\n";

$invalid_test = techbyit_post( 'test-email', array( 'to' => 'invalid-address' ) );
techbyit_assert( 400 === $invalid_test->get_status(), 'Invalid test email was accepted.' );
$rest_update = techbyit_post( 'providers/custom_smtp/settings', array( 'host' => 'localhost', 'port' => 1, 'encryption' => 'none', 'authentication' => false ) );
techbyit_assert( 200 === $rest_update->get_status(), 'Local failure transport was not saved.' );
$captured = array();
$failed_result = null;
$capture_mailer = static function ( $mailer ) use ( &$captured ): void {
	$captured = array(
		'mailer' => $mailer->Mailer,
		'host' => $mailer->Host,
		'port' => $mailer->Port,
		'smtp_auth' => $mailer->SMTPAuth,
		'from' => $mailer->From,
		'from_name' => $mailer->FromName,
		'html' => $mailer->ContentType,
		'to' => $mailer->getToAddresses(),
		'cc' => $mailer->getCcAddresses(),
		'bcc' => $mailer->getBccAddresses(),
		'reply' => $mailer->getReplyToAddresses(),
		'attachments' => $mailer->getAttachments(),
	);
};
$capture_failure = static function ( $result ) use ( &$failed_result ): void { $failed_result = $result; };
add_action( 'phpmailer_init', $capture_mailer, 1000 );
add_action( 'mailflow_smtp_mail_failed', $capture_failure );
$attachment = tempnam( sys_get_temp_dir(), 'techbyit-' );
file_put_contents( $attachment, 'Attachment content' );
$sent = wp_mail( 'to@example.test', 'Compatibility test', '<b>Hello</b>', array(
	'From: Explicit Sender <explicit@example.test>',
	'Cc: cc@example.test',
	'Bcc: bcc@example.test',
	'Reply-To: reply@example.test',
	'Content-Type: text/html; charset=UTF-8',
), array( $attachment ) );
unlink( $attachment );
techbyit_assert( false === $sent, 'Unavailable local SMTP unexpectedly sent mail.' );
techbyit_assert( 'smtp' === $captured['mailer'] && 'localhost' === $captured['host'] && 1 === $captured['port'] && false === $captured['smtp_auth'], 'SMTP was not configured in WordPress PHPMailer.' );
techbyit_assert( 'explicit@example.test' === $captured['from'] && 'Explicit Sender' === $captured['from_name'], 'Explicit From header was overridden.' );
techbyit_assert( 'text/html' === $captured['html'] && count( $captured['to'] ) === 1 && count( $captured['cc'] ) === 1 && count( $captured['bcc'] ) === 1 && count( $captured['reply'] ) === 1 && count( $captured['attachments'] ) === 1, 'WordPress mail recipients or content were lost.' );
techbyit_assert( $failed_result instanceof \TechByIt\SMTP\Mail\MailResult && 'smtp_connection_failed' === $failed_result->error_code(), 'Safe structured mail failure was not emitted.' );
techbyit_assert( strpos( wp_json_encode( $failed_result->public_data() ), 'integration-only-secret' ) === false, 'MailResult leaked a credential.' );
$test_failure = techbyit_post( 'test-email', array( 'to' => 'admin@example.test' ) );
techbyit_assert( 422 === $test_failure->get_status() && 'smtp_connection_failed' === $test_failure->get_data()['code'], 'Test email endpoint did not report SMTP failure.' );
techbyit_assert( strpos( wp_json_encode( $test_failure->get_data() ), 'integration-only-secret' ) === false, 'Test endpoint leaked a credential.' );
remove_action( 'phpmailer_init', $capture_mailer, 1000 );
remove_action( 'mailflow_smtp_mail_failed', $capture_failure );
echo "WordPress mail and test email integration passed.\n";

$failed_logs = techbyit_get( 'logs', array( 'status' => 'failed', 'search' => 'Compatibility test' ) );
techbyit_assert( 200 === $failed_logs->get_status() && 1 === $failed_logs->get_data()['data']['total'], 'Failed message was not logged.' );
$failed_log = $failed_logs->get_data()['data']['items'][0];
techbyit_assert( 'smtp_connection_failed' === $failed_log['error_code'] && array( 'to@example.test' ) === $failed_log['to'] && ! isset( $failed_log['body'] ), 'Failed log summary is wrong or exposes the body.' );
$failed_details = techbyit_get( 'logs/' . $failed_log['id'] )->get_data()['data'];
techbyit_assert( '<b>Hello</b>' === $failed_details['body'] && array( 'bcc@example.test' ) === $failed_details['bcc'] && array( wp_basename( $attachment ) ) === $failed_details['attachments'], 'Failed log details are incomplete.' );
techbyit_assert( 'explicit@example.test' === $failed_details['from_email'] && 'text/html' === $failed_details['content_type'], 'Failed log sender or content type is wrong.' );
techbyit_assert( strpos( wp_json_encode( $failed_details ), 'integration-only-secret' ) === false && strpos( wp_json_encode( $failed_details ), sys_get_temp_dir() ) === false, 'Log details leaked a credential or attachment path.' );
techbyit_assert( 400 === techbyit_get( 'logs', array( 'status' => 'bogus' ) )->get_status(), 'Invalid log filter was accepted.' );
techbyit_assert( 404 === techbyit_get( 'logs/999999999' )->get_status(), 'Missing log did not return 404.' );
echo "Failed mail logging passed.\n";

$sink = proc_open( 'php ' . escapeshellarg( __DIR__ . '/smtp-sink.php' ), array( 0 => array( 'pipe', 'r' ), 1 => array( 'file', '/dev/null', 'w' ), 2 => array( 'file', '/dev/null', 'w' ) ), $pipes );
techbyit_assert( is_resource( $sink ), 'Local SMTP sink did not start.' );
fclose( $pipes[0] );
usleep( 300000 );
$saved_local = techbyit_post( 'providers/custom_smtp/settings', array( 'host' => 'localhost', 'port' => 2525, 'encryption' => 'none', 'authentication' => false ) );
techbyit_assert( 200 === $saved_local->get_status(), 'Local SMTP sink configuration failed.' );
$sent_result = null;
$capture_success = static function ( $result ) use ( &$sent_result ): void { $sent_result = $result; };
add_action( 'mailflow_smtp_mail_sent', $capture_success );
$default_sender = array();
$capture_sender = static function ( $mailer ) use ( &$default_sender ): void { $default_sender = array( $mailer->From, $mailer->FromName ); };
add_action( 'phpmailer_init', $capture_sender, 1000 );
$simple_sent = wp_mail( 'admin@example.test', 'TechByIt SMTP local success', 'Hello from the local SMTP sink.' );
techbyit_assert( true === $simple_sent && $sent_result instanceof \TechByIt\SMTP\Mail\MailResult && $sent_result->success(), 'Custom SMTP did not deliver through WordPress.' );
techbyit_assert( $default_sender === array( 'sender@example.test', 'TechByIt SMTP' ), 'Configured global From settings were not applied.' );
$rest_success = techbyit_post( 'test-email', array( 'to' => 'admin@example.test' ) );
techbyit_assert( 200 === $rest_success->get_status() && true === $rest_success->get_data()['success'], 'Test email endpoint did not deliver through active SMTP.' );
techbyit_assert( strpos( wp_json_encode( $rest_success->get_data() ), 'integration-only-secret' ) === false, 'Successful test endpoint leaked a credential.' );
$preempt_test = static function ( $pre ) { return null === $pre ? true : $pre; };
add_filter( 'pre_wp_mail', $preempt_test, 1000 );
$preempted = techbyit_post( 'test-email', array( 'to' => 'admin@example.test' ) );
techbyit_assert( 422 === $preempted->get_status() && 'mail_preempted' === $preempted->get_data()['code'], 'Another mail plugin was mistaken for active provider delivery.' );
remove_filter( 'pre_wp_mail', $preempt_test, 1000 );
remove_action( 'mailflow_smtp_mail_sent', $capture_success );
remove_action( 'phpmailer_init', $capture_sender, 1000 );
proc_close( $sink );
echo "Local SMTP delivery and REST success passed.\n";

$sent_logs = techbyit_get( 'logs', array( 'status' => 'sent', 'search' => 'local success' ) )->get_data()['data'];
techbyit_assert( 1 === $sent_logs['total'] && 'sender@example.test' === $sent_logs['items'][0]['from_email'] && null !== $sent_logs['items'][0]['sent_at'], 'Sent message was not logged with its effective sender.' );
$sent_details = techbyit_get( 'logs/' . $sent_logs['items'][0]['id'] )->get_data()['data'];
techbyit_assert( '' !== $sent_details['message_id'] && 'Hello from the local SMTP sink.' === $sent_details['body'], 'Sent log details are incomplete.' );
$today = wp_date( 'Y-m-d' );
techbyit_assert( techbyit_get( 'logs', array( 'date_from' => $today, 'date_to' => $today ) )->get_data()['data']['total'] >= 4, 'Date filter excluded today\'s logs.' );
techbyit_assert( 0 === techbyit_get( 'logs', array( 'date_to' => '2000-01-01' ) )->get_data()['data']['total'], 'Date filter included newer logs.' );
$paged = techbyit_get( 'logs', array( 'per_page' => 1, 'page' => 2 ) )->get_data()['data'];
techbyit_assert( 1 === count( $paged['items'] ) && 2 === $paged['page'] && $paged['total'] === $paged['total_pages'], 'Log pagination failed.' );
$dashboard = techbyit_get( 'dashboard' )->get_data()['data'];
techbyit_assert( $dashboard['last_7_days']['sent'] >= 2 && $dashboard['last_7_days']['failed'] >= 2 && $dashboard['recent_failures'] && true === $dashboard['logging_enabled'], 'Dashboard statistics are wrong.' );
techbyit_assert( strpos( wp_json_encode( $dashboard ), 'Hello' ) === false, 'Dashboard exposed a message body.' );

$repository = new \TechByIt\SMTP\Logs\LogRepository();
$old_time   = gmdate( 'Y-m-d H:i:s', time() - 40 * DAY_IN_SECONDS );
$old_id     = $repository->create( array( 'provider' => 'custom_smtp', 'status' => 'sent', 'subject' => 'Expired', 'recipients' => '{"to":["old@example.test"]}', 'attempt_count' => 1, 'created_at' => $old_time, 'updated_at' => $old_time ) );
$before     = techbyit_get( 'logs' )->get_data()['data']['total'];
do_action( \TechByIt\SMTP\Logs\MailLogger::PURGE_HOOK );
techbyit_assert( null === $repository->find( $old_id ) && techbyit_get( 'logs' )->get_data()['data']['total'] === $before - 1, 'Retention purge removed the wrong rows.' );
techbyit_assert( 200 === techbyit_delete( 'logs/' . $failed_log['id'] )->get_status() && 404 === techbyit_get( 'logs/' . $failed_log['id'] )->get_status(), 'Log entry could not be deleted.' );

techbyit_post( 'settings', array( 'logging_enabled' => false ) );
$before = techbyit_get( 'logs' )->get_data()['data']['total'];
wp_mail( 'admin@example.test', 'Not logged', 'Logging disabled.' );
techbyit_assert( techbyit_get( 'logs' )->get_data()['data']['total'] === $before, 'Disabled logging still recorded mail.' );
techbyit_post( 'settings', array( 'logging_enabled' => true, 'log_body' => false ) );
echo "Mail log REST, dashboard, and retention passed.\n";

( new \TechByIt\SMTP\Settings\SettingsRepository() )->set_active_provider( '' );
$pass_through = static function ( $pre ) { return null === $pre ? true : $pre; };
add_filter( 'pre_wp_mail', $pass_through, 1000 );
techbyit_assert( true === wp_mail( 'admin@example.test', 'WordPress fallback', 'No provider active.' ), 'Unconfigured plugin blocked WordPress mail fallback.' );
remove_filter( 'pre_wp_mail', $pass_through, 1000 );
echo "Unconfigured wp_mail compatibility passed.\n";
