<?php
namespace TechByIt\SMTP\Providers\Builtin;

use TechByIt\SMTP\Providers\SchemaProvider;
use TechByIt\SMTP\Providers\SmtpTransportProviderInterface;

final class CustomSMTPProvider extends SchemaProvider implements SmtpTransportProviderInterface {
	public function smtp_configuration( array $values ): array {
		return $values;
	}
	protected function definition(): array {
		return array(
			'id'             => 'custom_smtp',
			'name'           => 'Custom SMTP',
			'description'    => 'Configure any SMTP server.',
			'icon'           => 'email-alt',
			'authentication' => 'smtp_credentials',
			'features'       => array( 'smtp' ),
			'fields'         => array(
				array(
					'name'       => 'host',
					'label'      => 'SMTP Host',
					'type'       => 'text',
					'format'     => 'host',
					'required'   => true,
					'max_length' => 255,
				),
				array(
					'name'     => 'port',
					'label'    => 'SMTP Port',
					'type'     => 'number',
					'required' => true,
					'min'      => 1,
					'max'      => 65535,
				),
				array(
					'name'     => 'encryption',
					'label'    => 'Encryption',
					'type'     => 'select',
					'required' => true,
					'options'  => array( 'none', 'ssl', 'tls' ),
				),
				array(
					'name'     => 'authentication',
					'label'    => 'Use authentication',
					'type'     => 'toggle',
					'required' => false,
				),
				array(
					'name'          => 'username',
					'label'         => 'Username',
					'type'          => 'text',
					'required_when' => 'authentication',
					'max_length'    => 255,
				),
				array(
					'name'          => 'password',
					'label'         => 'Password',
					'type'          => 'password',
					'secret'        => true,
					'required_when' => 'authentication',
				),
				array(
					'name'     => 'timeout',
					'label'    => 'Timeout (seconds)',
					'type'     => 'number',
					'required' => false,
					'min'      => 1,
					'max'      => 60,
				),
			),
		);
	}
}
