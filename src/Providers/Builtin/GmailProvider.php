<?php
namespace TechByIt\SMTP\Providers\Builtin;

use TechByIt\SMTP\Providers\SchemaProvider;

final class GmailProvider extends SchemaProvider {
	protected function definition(): array {
		return array(
			'id'             => 'gmail',
			'name'           => 'Gmail',
			'description'    => 'Connect a Gmail account with OAuth 2.0.',
			'icon'           => 'google',
			'authentication' => 'oauth2',
			'features'       => array( 'api', 'oauth2' ),
			'fields'         => array(
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
