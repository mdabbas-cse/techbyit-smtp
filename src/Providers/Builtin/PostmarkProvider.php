<?php
namespace TechByIt\SMTP\Providers\Builtin;

use TechByIt\SMTP\Providers\SchemaProvider;

final class PostmarkProvider extends SchemaProvider {
	protected function definition(): array {
		return array(
			'id'             => 'postmark',
			'name'           => 'Postmark',
			'description'    => 'Use Postmark with a server token.',
			'icon'           => 'email',
			'authentication' => 'api_key',
			'features'       => array( 'api' ),
			'fields'         => array(
				array(
					'name'     => 'server_token',
					'label'    => 'Server Token',
					'type'     => 'password',
					'secret'   => true,
					'required' => true,
				),
			),
		);
	}
}
