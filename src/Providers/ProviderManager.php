<?php
namespace TechByIt\SMTP\Providers;

use TechByIt\SMTP\Settings\ProviderSettingsRepository;
use TechByIt\SMTP\Settings\SettingsRepository;
use TechByIt\SMTP\Support\ConfigurationException;

final class ProviderManager {
	private $registry;
	private $provider_settings;
	private $settings;

	public function __construct( ProviderRegistry $registry, ProviderSettingsRepository $provider_settings, SettingsRepository $settings ) {
		$this->registry          = $registry;
		$this->provider_settings = $provider_settings;
		$this->settings          = $settings;
	}

	public function provider( string $id ): ProviderInterface {
		$provider = $this->registry->get( $id );
		if ( ! $provider ) {
			throw new ConfigurationException( 'Unknown provider.' );
		}
		return $provider;
	}

	public function providers(): array {
		$result = array();
		foreach ( $this->registry->all() as $provider ) {
			$result[] = $this->summary( $provider );
		}
		return $result;
	}

	public function summary( ProviderInterface $provider ): array {
		$metadata = $provider->metadata();
		unset( $metadata['fields'] );
		return array_merge(
			$metadata,
			array(
				'sending_supported' => $provider instanceof SendingProviderInterface,
				'configured'        => $this->is_configured( $provider ),
				'active'            => $this->settings->get()['active_provider'] === $provider->id(),
				'authorization'     => array( 'status' => 'oauth2' === $provider->metadata()['authentication'] ? $this->provider_settings->authorization_status( $provider ) : 'not_required' ),
			)
		);
	}

	public function get_provider_settings( string $id ): array {
		$provider = $this->provider( $id );
		return array(
			'id'            => $id,
			'values'        => $this->provider_settings->public_values( $provider ),
			'configured'    => $this->is_configured( $provider ),
			'active'        => $this->settings->get()['active_provider'] === $id,
			'authorization' => array( 'status' => 'oauth2' === $provider->metadata()['authentication'] ? $this->provider_settings->authorization_status( $provider ) : 'not_required' ),
		);
	}

	public function save_configuration( string $id, array $input ): array {
		$provider = $this->provider( $id );
		$values   = $provider->validate( $input, $this->provider_settings->private_values( $provider, $input ) );
		$this->provider_settings->save( $provider, $values );
		return $this->get_provider_settings( $id );
	}

	public function activate( string $id ): array {
		$provider = $this->provider( $id );
		if ( ! $provider instanceof SendingProviderInterface ) {
			throw new ConfigurationException( 'Sending is not available for this provider yet.' );
		}
		if ( ! $this->is_configured( $provider ) ) {
			throw new ConfigurationException( 'Configure this provider before activating it.' );
		}
		if ( 'oauth2' === $provider->metadata()['authentication'] && 'connected' !== $this->provider_settings->authorization_status( $provider ) ) {
			throw new ConfigurationException( 'Connect this OAuth provider before activating it.' );
		}
		$this->settings->set_active_provider( $id );
		return $this->get_provider_settings( $id );
	}

	public function active_configuration(): array {
		$id = $this->settings->get()['active_provider'];
		if ( '' === $id ) {
			return array(
				'id'         => $id,
				'provider'   => null,
				'values'     => array(),
				'configured' => false,
			); }
		$provider = $this->registry->get( $id );
		if ( ! $provider ) {
			return array(
				'id'         => $id,
				'provider'   => null,
				'values'     => array(),
				'configured' => false,
			); }
		try {
			$values = $provider->validate( array(), $this->provider_settings->private_values( $provider ) );
			return array(
				'id'         => $id,
				'provider'   => $provider,
				'values'     => $values,
				'configured' => true,
			);
		} catch ( ConfigurationException $exception ) {
			return array(
				'id'         => $id,
				'provider'   => $provider,
				'values'     => array(),
				'configured' => false,
			);
		}
	}

	private function is_configured( ProviderInterface $provider ): bool {
		try {
			$provider->validate( array(), $this->provider_settings->private_values( $provider ) );
			return true;
		} catch ( ConfigurationException | \RuntimeException $exception ) {
			return false;
		}
	}
}
