<?php
namespace TechByIt\SMTP;

use TechByIt\SMTP\Admin\AdminPage;
use TechByIt\SMTP\Mail\MailManager;
use TechByIt\SMTP\Mail\PHPMailerConfigurator;
use TechByIt\SMTP\Mail\TestMailService;
use TechByIt\SMTP\Mail\WordPressMailIntegration;
use TechByIt\SMTP\Providers\ProviderManager;
use TechByIt\SMTP\Providers\ProviderRegistry;
use TechByIt\SMTP\Rest\BootstrapController;
use TechByIt\SMTP\Rest\ProviderController;
use TechByIt\SMTP\Rest\SettingsController;
use TechByIt\SMTP\Rest\TestMailController;
use TechByIt\SMTP\Settings\CredentialEncryption;
use TechByIt\SMTP\Settings\ProviderSettingsRepository;
use TechByIt\SMTP\Settings\SettingsRepository;

final class Plugin {
	public function register(): void {
		( new AdminPage() )->register();
		( new BootstrapController() )->register();
		$settings = new SettingsRepository();
		$registry = ProviderRegistry::defaults();
		do_action( 'mailflow_smtp_register_providers', $registry );
		$provider_settings = new ProviderSettingsRepository( new CredentialEncryption() );
		$providers         = new ProviderManager( $registry, $provider_settings, $settings );
		( new ProviderController( $providers ) )->register();
		( new SettingsController( $settings ) )->register();
		$mail        = new MailManager( $providers );
		$integration = new WordPressMailIntegration( $mail, $settings, new PHPMailerConfigurator() );
		$integration->register();
		( new TestMailController( new TestMailService( $mail, $integration ) ) )->register();
	}
}
