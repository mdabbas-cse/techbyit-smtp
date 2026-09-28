<?php
namespace TechByIt\SMTP\Rest;

use TechByIt\SMTP\Settings\SettingsRepository;
use TechByIt\SMTP\Support\ConfigurationException;

final class SettingsController {
	private $settings;

	public function __construct( SettingsRepository $settings ) {
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
				'/settings',
				array(
					array(
						'methods'             => 'GET',
						'callback'            => array( $this, 'get_settings' ),
						'permission_callback' => array( $this, 'can_manage' ),
					),
					array(
						'methods'             => 'POST',
						'callback'            => array( $this, 'save_settings' ),
						'permission_callback' => array( $this, 'can_manage' ),
					),
				)
			);
		}
	}

	public function get_settings(): \WP_REST_Response {
		try {
			return ApiResponse::success( $this->settings->get() );
		} catch ( \RuntimeException $exception ) {
			return ApiResponse::server_error();
		}
	}

	public function save_settings( $request ): \WP_REST_Response {
		$input = $request->get_json_params();
		if ( ! is_array( $input ) ) {
			return ApiResponse::failure( new ConfigurationException( 'Send a JSON object with settings.' ) );
		}
		try {
			return ApiResponse::success( $this->settings->save_general( $input ) );
		} catch ( ConfigurationException $exception ) {
			return ApiResponse::failure( $exception );
		} catch ( \RuntimeException $exception ) {
			return ApiResponse::server_error();
		}
	}
}
