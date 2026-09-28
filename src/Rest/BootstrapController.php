<?php
namespace TechByIt\SMTP\Rest;

final class BootstrapController {
    public function register(): void {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    public function register_routes(): void {
        foreach (array('techbyit-smtp/v1', 'flowmail-smtp/v1', 'mailflow-smtp/v1') as $namespace) {
            register_rest_route($namespace, '/bootstrap', array(
                'methods' => 'GET',
                'callback' => array($this, 'get_bootstrap'),
                'permission_callback' => array($this, 'can_manage'),
            ));
        }
    }

    public function can_manage(): bool {
        return current_user_can('manage_options');
    }

    public function get_bootstrap(): array {
        return array(
            'version' => TECHBYIT_SMTP_VERSION,
        );
    }
}
