<?php
namespace TechByIt\SMTP\Rest;

use TechByIt\SMTP\Logs\LogRepository;
use TechByIt\SMTP\Settings\SettingsRepository;
use TechByIt\SMTP\Support\ConfigurationException;

final class LogController {
	private $logs;
	private $settings;

	public function __construct( LogRepository $logs, SettingsRepository $settings ) {
		$this->logs     = $logs;
		$this->settings = $settings;
	}

	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function can_manage(): bool {
		return current_user_can( 'manage_options' );
	}

	public function register_routes(): void {
		foreach ( array( 'techbyit-smtp/v1', 'flowmail-smtp/v1', 'mailflow-smtp/v1' ) as $namespace ) {
			register_rest_route(
				$namespace,
				'/logs',
				array(
					array(
						'methods'             => 'GET',
						'callback'            => array( $this, 'list_logs' ),
						'permission_callback' => array( $this, 'can_manage' ),
					),
					array(
						'methods'             => 'DELETE',
						'callback'            => array( $this, 'clear_logs' ),
						'permission_callback' => array( $this, 'can_manage' ),
					),
				)
			);
			register_rest_route(
				$namespace,
				'/logs/(?P<id>\d+)',
				array(
					array(
						'methods'             => 'GET',
						'callback'            => array( $this, 'get_log' ),
						'permission_callback' => array( $this, 'can_manage' ),
					),
					array(
						'methods'             => 'DELETE',
						'callback'            => array( $this, 'delete_log' ),
						'permission_callback' => array( $this, 'can_manage' ),
					),
				)
			);
			register_rest_route(
				$namespace,
				'/dashboard',
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'dashboard' ),
					'permission_callback' => array( $this, 'can_manage' ),
				)
			);
		}
	}

	public function list_logs( $request ): \WP_REST_Response {
		try {
			return ApiResponse::success( $this->logs->query( $this->logs->normalize_filters( $request->get_query_params() ) ) );
		} catch ( ConfigurationException $exception ) {
			return ApiResponse::failure( $exception );
		} catch ( \RuntimeException $exception ) {
			return ApiResponse::server_error();
		}
	}

	public function get_log( $request ): \WP_REST_Response {
		try {
			$log = $this->logs->find( (int) $request['id'] );
			return $log ? ApiResponse::success( $log ) : ApiResponse::not_found( 'log_not_found', 'The mail log entry was not found.' );
		} catch ( \RuntimeException $exception ) {
			return ApiResponse::server_error();
		}
	}

	public function delete_log( $request ): \WP_REST_Response {
		try {
			return $this->logs->delete( (int) $request['id'] )
				? ApiResponse::success( array( 'deleted' => 1 ) )
				: ApiResponse::not_found( 'log_not_found', 'The mail log entry was not found.' );
		} catch ( \RuntimeException $exception ) {
			return ApiResponse::server_error();
		}
	}

	public function clear_logs(): \WP_REST_Response {
		try {
			return ApiResponse::success( array( 'deleted' => $this->logs->clear() ) );
		} catch ( \RuntimeException $exception ) {
			return ApiResponse::server_error();
		}
	}

	public function dashboard(): \WP_REST_Response {
		try {
			$settings = $this->settings->get();
			return ApiResponse::success(
				array(
					'logging_enabled' => $settings['logging_enabled'],
					'retention_days'  => $settings['log_retention_days'],
					'last_7_days'     => $this->logs->stats( 7 ),
					'last_30_days'    => $this->logs->stats( 30 ),
					'recent_failures' => $this->logs->recent_failures( 5 ),
				)
			);
		} catch ( \RuntimeException $exception ) {
			return ApiResponse::server_error();
		}
	}
}
