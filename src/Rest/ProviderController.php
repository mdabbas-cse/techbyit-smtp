<?php
namespace TechByIt\SMTP\Rest;

use TechByIt\SMTP\Providers\ProviderManager;
use TechByIt\SMTP\Support\ConfigurationException;

final class ProviderController {
	private $manager;

	public function __construct( ProviderManager $manager ) {
		$this->manager = $manager;
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
				'/providers',
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'list_providers' ),
					'permission_callback' => array( $this, 'can_manage' ),
				)
			);
			register_rest_route(
				$namespace,
				'/providers/(?P<provider>[a-z0-9_]+)',
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_provider' ),
					'permission_callback' => array( $this, 'can_manage' ),
				)
			);
			register_rest_route(
				$namespace,
				'/providers/(?P<provider>[a-z0-9_]+)/settings',
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
			register_rest_route(
				$namespace,
				'/providers/(?P<provider>[a-z0-9_]+)/activate',
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'activate' ),
					'permission_callback' => array( $this, 'can_manage' ),
				)
			);
		}
	}

	public function list_providers(): \WP_REST_Response {
		try {
			return ApiResponse::success( $this->manager->providers() );
		} catch ( \RuntimeException $exception ) {
			return ApiResponse::server_error();
		}
	}

	public function get_provider( $request ): \WP_REST_Response {
		try {
			$provider = $this->manager->provider( $request['provider'] );
			return ApiResponse::success( array_merge( $provider->metadata(), array( 'sending_supported' => $this->manager->summary( $provider )['sending_supported'] ) ) );
		} catch ( ConfigurationException $exception ) {
			return ApiResponse::failure( $exception );
		}
	}

	public function get_settings( $request ): \WP_REST_Response {
		try {
			return ApiResponse::success( $this->manager->get_provider_settings( $request['provider'] ) );
		} catch ( ConfigurationException $exception ) {
			return ApiResponse::failure( $exception );
		} catch ( \RuntimeException $exception ) {
			return ApiResponse::server_error();
		}
	}

	public function save_settings( $request ): \WP_REST_Response {
		$input = $request->get_json_params();
		if ( ! is_array( $input ) ) {
			return ApiResponse::failure( new ConfigurationException( 'Send a JSON object with provider settings.' ) );
		}
		try {
			return ApiResponse::success( $this->manager->save_configuration( $request['provider'], $input ) );
		} catch ( ConfigurationException $exception ) {
			return ApiResponse::failure( $exception );
		} catch ( \RuntimeException $exception ) {
			return ApiResponse::server_error();
		}
	}

	public function activate( $request ): \WP_REST_Response {
		try {
			return ApiResponse::success( $this->manager->activate( $request['provider'] ) );
		} catch ( ConfigurationException $exception ) {
			return ApiResponse::failure( $exception );
		} catch ( \RuntimeException $exception ) {
			return ApiResponse::server_error();
		}
	}
}
