<?php
namespace TechByIt\SMTP\Rest;

use TechByIt\SMTP\Mail\TestMailService;

final class TestMailController {
	private $service;
	public function __construct( TestMailService $service ) {
		$this->service = $service; }
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) ); }
	public function can_manage(): bool {
		return current_user_can( 'manage_options' ); }
	public function register_routes(): void {
		foreach ( array( 'techbyit-smtp/v1', 'flowmail-smtp/v1', 'mailflow-smtp/v1' ) as $namespace ) {
			register_rest_route(
				$namespace,
				'/test-email',
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'send' ),
					'permission_callback' => array( $this, 'can_manage' ),
				)
			);
		}
	}
	public function send( $request ): \WP_REST_Response {
		$input = $request->get_json_params();
		if ( ! is_array( $input ) || ! isset( $input['to'] ) || ! is_string( $input['to'] ) || ! is_email( $input['to'] ) || sanitize_email( $input['to'] ) !== $input['to'] ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'code'    => 'invalid_recipient',
					'message' => 'Enter a valid recipient email address.',
				),
				400
			);
		}
		foreach ( array(
			'subject' => 200,
			'message' => 10000,
		) as $key => $limit ) {
			if ( isset( $input[ $key ] ) && ( ! is_string( $input[ $key ] ) || strlen( $input[ $key ] ) > $limit || preg_match( '/[\r\n\x00-\x1F\x7F]/', 'subject' === $key ? $input[ $key ] : '' ) ) ) {
				return new \WP_REST_Response(
					array(
						'success' => false,
						'code'    => 'invalid_' . $key,
						'message' => 'The test email content is invalid.',
					),
					400
				);
			}
		}
		$result = $this->service->send( $input['to'], isset( $input['subject'] ) ? sanitize_text_field( $input['subject'] ) : null, isset( $input['message'] ) ? sanitize_textarea_field( $input['message'] ) : null );
		if ( ! $result->success() ) {
			return new \WP_REST_Response(
				array(
					'success'  => false,
					'code'     => $result->error_code(),
					'message'  => $result->error_message(),
					'provider' => $result->provider(),
				),
				422
			);
		}
		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => array(
					'message'  => 'Test email sent successfully.',
					'provider' => $result->provider(),
				),
			),
			200
		);
	}
}
