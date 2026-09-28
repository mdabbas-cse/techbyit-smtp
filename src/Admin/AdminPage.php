<?php
namespace TechByIt\SMTP\Admin;

final class AdminPage {
    /** @var string[] */
    private $hooks = array();

    public function register(): void {
        add_action('admin_menu', array($this, 'menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue'));
    }

    public function menu(): void {
        $this->hooks[] = add_menu_page(__('TechByIt SMTP', 'techbyit-smtp'), __('TechByIt SMTP', 'techbyit-smtp'), 'manage_options', 'techbyit-smtp', array($this, 'render'), 'dashicons-email-alt', 80);
        foreach (array('techbyit-smtp' => __('Dashboard', 'techbyit-smtp'), 'techbyit-smtp-mailers' => __('Providers', 'techbyit-smtp'), 'techbyit-smtp-logs' => __('Mail Logs', 'techbyit-smtp'), 'techbyit-smtp-settings' => __('Settings', 'techbyit-smtp')) as $slug => $label) {
            $this->hooks[] = add_submenu_page('techbyit-smtp', $label, $label, 'manage_options', $slug, array($this, 'render'));
        }
    }

    public function render(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You cannot access TechByIt SMTP.', 'techbyit-smtp'));
        }
        // This read-only query value selects an allowlisted admin view; no form is processed.
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : 'techbyit-smtp';
        if (!in_array($page, array('techbyit-smtp', 'techbyit-smtp-mailers', 'techbyit-smtp-logs', 'techbyit-smtp-settings'), true)) {
            $page = 'techbyit-smtp';
        }
        echo '<div class="wrap"><div id="techbyit-smtp-app" data-page="' . esc_attr($page) . '"></div></div>';
    }

    public function enqueue(string $hook): void {
        if (!in_array($hook, $this->hooks, true)) {
            return;
        }
        $script = plugin_dir_path(TECHBYIT_SMTP_FILE) . 'dist/admin.js';
        if (!file_exists($script)) {
            add_action('admin_notices', static function () use ($hook): void {
                $screen = get_current_screen();
                if (!$screen || $screen->id !== $hook) {
                    return;
                }
                echo '<div class="notice notice-warning"><p>' . esc_html__('TechByIt SMTP admin assets are missing. Run pnpm build.', 'techbyit-smtp') . '</p></div>';
            });
            return;
        }
        wp_enqueue_script('techbyit-smtp-admin', plugins_url('dist/admin.js', TECHBYIT_SMTP_FILE), array(), TECHBYIT_SMTP_VERSION, true);
        wp_add_inline_script('techbyit-smtp-admin', 'window.TechByItSMTPConfig = ' . wp_json_encode(array(
            'restUrl' => esc_url_raw(rest_url('techbyit-smtp/v1/')),
            'nonce' => wp_create_nonce('wp_rest'),
        ), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';', 'before');
        $style = plugin_dir_path(TECHBYIT_SMTP_FILE) . 'dist/admin.css';
        if (file_exists($style)) {
            wp_enqueue_style('techbyit-smtp-admin', plugins_url('dist/admin.css', TECHBYIT_SMTP_FILE), array(), TECHBYIT_SMTP_VERSION);
        }
    }
}
