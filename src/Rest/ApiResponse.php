<?php
namespace TechByIt\SMTP\Rest;

use TechByIt\SMTP\Support\ConfigurationException;

final class ApiResponse {
	public static function success( $data ): \WP_REST_Response {
		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => $data,
			),
			200
		);
	}

	public static function failure( ConfigurationException $exception ): \WP_REST_Response {
		$unknown = $exception->getMessage() === 'Unknown provider.';
		return new \WP_REST_Response(
			array(
				'success' => false,
				'code'    => $unknown ? 'unknown_provider' : 'validation_error',
				'message' => $exception->getMessage(),
				'errors'  => $exception->errors(),
			),
			$unknown ? 404 : 400
		);
	}

	public static function server_error(): \WP_REST_Response {
		return new \WP_REST_Response(
			array(
				'success' => false,
				'code'    => 'configuration_unavailable',
				'message' => 'Configuration is temporarily unavailable.',
				'errors'  => array(),
			),
			500
		);
	}
}
