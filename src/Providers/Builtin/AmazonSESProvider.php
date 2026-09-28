<?php
namespace TechByIt\SMTP\Providers\Builtin;

use TechByIt\SMTP\Providers\SchemaProvider;

final class AmazonSESProvider extends SchemaProvider {
	protected function definition(): array {
		return array(
			'id'             => 'amazon_ses',
			'name'           => 'Amazon SES',
			'description'    => 'Use the Amazon SES API with access keys.',
			'icon'           => 'amazon',
			'authentication' => 'access_key',
			'features'       => array( 'api' ),
			'fields'         => array(
				array(
					'name'     => 'access_key',
					'label'    => 'Access Key ID',
					'type'     => 'password',
					'secret'   => true,
					'required' => true,
				),
				array(
					'name'     => 'secret_key',
					'label'    => 'Secret Access Key',
					'type'     => 'password',
					'secret'   => true,
					'required' => true,
				),
				array(
					'name'       => 'region',
					'label'      => 'AWS Region',
					'type'       => 'text',
					'format'     => 'region',
					'required'   => true,
					'max_length' => 64,
				),
			),
		);
	}
}
