<?php
namespace TechByIt\SMTP\Providers\Builtin;

use TechByIt\SMTP\Providers\SchemaProvider;

final class MailgunProvider extends SchemaProvider {
	protected function definition(): array {
		return array(
			'id'             => 'mailgun',
			'name'           => 'Mailgun',
			'description'    => 'Use Mailgun with an API key and sending domain.',
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
				array(
					'name'       => 'domain',
					'label'      => 'Sending Domain',
					'type'       => 'text',
					'format'     => 'domain',
					'required'   => true,
					'max_length' => 255,
				),
				array(
					'name'     => 'region',
					'label'    => 'Region',
					'type'     => 'select',
					'required' => true,
					'options'  => array( 'us', 'eu' ),
				),
			),
		);
	}
}
