<?php
namespace TechByIt\SMTP\Database;

final class Installer {
    public const VERSION = '1';
    public const OPTION = 'mailflow_smtp_db_version';

    public static function maybe_install(): void {
        if (get_option(self::OPTION) !== self::VERSION) {
            self::install();
        }
    }

    public static function install(): void {
        global $wpdb;
        $table = $wpdb->prefix . 'mailflow_smtp_logs';
        $legacy_table = $wpdb->prefix . 'my_smtp_logs';
        // Installation needs current table state; cached results could hide a rename.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $existing = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $legacy = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $legacy_table));
        if ($existing !== $table && $legacy === $legacy_table) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $renamed = $wpdb->query($wpdb->prepare('RENAME TABLE %i TO %i', $legacy_table, $table));
            if ($renamed === false) {
                return;
            }
        }
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta(self::schema_sql($wpdb->prefix, $wpdb->get_charset_collate()));
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $found = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
        if ($found === $table) {
            update_option(self::OPTION, self::VERSION, false);
            delete_option('my_smtp_db_version');
        }
    }

    public static function schema_sql(string $prefix, string $charset_collate): string {
        $table = $prefix . 'mailflow_smtp_logs';
        return "CREATE TABLE {$table} (
id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
message_id varchar(255) NOT NULL DEFAULT '',
provider varchar(64) NOT NULL DEFAULT '',
from_email varchar(320) NOT NULL DEFAULT '',
from_name varchar(255) NOT NULL DEFAULT '',
recipients longtext NULL,
subject text NULL,
headers longtext NULL,
body longtext NULL,
content_type varchar(100) NOT NULL DEFAULT 'text/plain',
status varchar(20) NOT NULL DEFAULT 'failed',
error_message text NULL,
attempt_count int(10) unsigned NOT NULL DEFAULT 0,
created_at datetime NOT NULL,
updated_at datetime NOT NULL,
sent_at datetime NULL,
PRIMARY KEY  (id),
KEY message_id (message_id),
KEY provider_created (provider,created_at),
KEY status_created (status,created_at)
) {$charset_collate};";
    }
}
