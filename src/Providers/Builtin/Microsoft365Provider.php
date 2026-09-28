<?php
namespace TechByIt\SMTP\Providers\Builtin;

use TechByIt\SMTP\Providers\SchemaProvider;

final class Microsoft365Provider extends SchemaProvider {
	protected function definition(): array {
		return array(
			'id'             => 'microsoft365',
			'name'           => 'Microsoft 365',
			'description'    => 'Connect Microsoft Graph with OAuth 2.0.',
			'icon'           => 'microsoft',
			'authentication' => 'oauth2',
			'features'       => array( 'api', 'oauth2' ),
			'fields'         => array(
				array(
					'name'       => 'tenant_id',
					'label'      => 'Tenant ID',
					'type'       => 'text',
					'required'   => true,
					'max_length' => 255,
				),
				array(
					'name'       => 'client_id',
					'label'      => 'OAuth Client ID',
					'type'       => 'text',
					'required'   => true,
					'max_length' => 512,
				),
				array(
					'name'     => 'client_secret',
					'label'    => 'OAuth Client Secret',
					'type'     => 'password',
					'secret'   => true,
					'required' => true,
				),
				array(
					'name'     => 'sender_email',
					'label'    => 'Sender Email',
					'type'     => 'email',
					'required' => true,
				),
			),
		);
	}
}
