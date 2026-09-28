<?php
/**
 * Plugin Name: TechByIt SMTP
 * Description: SMTP and email management for WordPress.
 * Version: 1.0.0
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * Text Domain: techbyit-smtp
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit;
}

define('TECHBYIT_SMTP_FILE', __FILE__);
define('TECHBYIT_SMTP_VERSION', '1.0.0');

$techbyit_smtp_autoload = __DIR__ . '/vendor/autoload.php';
if (!file_exists($techbyit_smtp_autoload)) {
    add_action('admin_notices', static function (): void {
        $screen = get_current_screen();
        if ($screen && $screen->id === 'plugins' && current_user_can('manage_options')) {
            echo '<div class="notice notice-error"><p>' . esc_html__('TechByIt SMTP needs Composer dependencies. Run composer install in the plugin directory.', 'techbyit-smtp') . '</p></div>';
        }
    });
    return;
}
require_once $techbyit_smtp_autoload;

register_activation_hook(__FILE__, array(\TechByIt\SMTP\Database\Installer::class, 'install'));
add_action('plugins_loaded', array(\TechByIt\SMTP\Database\Installer::class, 'maybe_install'));
add_action('plugins_loaded', static function (): void {
    (new \TechByIt\SMTP\Plugin())->register();
});
