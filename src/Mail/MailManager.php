<?php
namespace TechByIt\SMTP\Mail;

use TechByIt\SMTP\Providers\ProviderManager;
use TechByIt\SMTP\Providers\SmtpTransportProviderInterface;

final class MailManager {
	private $providers;
	public function __construct( ProviderManager $providers ) {
		$this->providers = $providers;
	}
	public function active_transport(): TransportSelection {
		try {
			$active = $this->providers->active_configuration();
			$id     = $active['id'];
			if ( '' === $id ) {
				return new TransportSelection( '', array(), null, true ); }
			$provider = $active['provider'];
			if ( ! $provider ) {
				return new TransportSelection( $id, array(), 'unknown_provider' ); }
			if ( ! $active['configured'] ) {
				return new TransportSelection( $id, array(), 'provider_not_configured' ); }
			if ( ! $provider instanceof SmtpTransportProviderInterface ) {
				return new TransportSelection( $id, array(), 'transport_unavailable' ); }
			return new TransportSelection( $id, $provider->smtp_configuration( $active['values'] ) );
		} catch ( \Throwable $exception ) {
			return new TransportSelection( '', array(), 'configuration_unavailable' );
		}
	}
}
