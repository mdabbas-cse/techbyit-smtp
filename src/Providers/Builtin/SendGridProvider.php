<?php
namespace TechByIt\SMTP\Providers\Builtin;

use TechByIt\SMTP\Providers\SchemaProvider;

final class SendGridProvider extends SchemaProvider {
	protected function definition(): array {
		return array(
			'id'             => 'sendgrid',
			'name'           => 'SendGrid',
			'description'    => 'Use SendGrid with an API key.',
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
