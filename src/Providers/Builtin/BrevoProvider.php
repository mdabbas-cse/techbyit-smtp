<?php
namespace TechByIt\SMTP\Providers\Builtin;

use TechByIt\SMTP\Providers\SchemaProvider;

final class BrevoProvider extends SchemaProvider {
	protected function definition(): array {
		return array(
			'id'             => 'brevo',
			'name'           => 'Brevo',
			'description'    => 'Use the Brevo API with an API key.',
			'icon'           => 'email',
			'authentication' => 'api_key',
			'features'       => array( 'api' ),
			'fields'         => array(
				array(
					'name'     => 'api_key',
					'label'    => 'API Key',
					'type'     => 'password',
					'secret'   => true,
					'required' => true,
				),
			),
		);
	}
}
