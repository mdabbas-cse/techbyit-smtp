<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use TechByIt\SMTP\Database\Installer;
use TechByIt\SMTP\Rest\BootstrapController;

if (!function_exists('current_user_can')) {
    function current_user_can(string $capability): bool {
        return $GLOBALS['test_can_manage'] && $capability === 'manage_options';
    }
}

define('TECHBYIT_SMTP_VERSION', '1.0.0');
$controller = new BootstrapController();
$GLOBALS['test_can_manage'] = false;
if ($controller->can_manage()) {
    throw new RuntimeException('A non-administrator can access the bootstrap route.');
}
$GLOBALS['test_can_manage'] = true;
if (!$controller->can_manage()) {
    throw new RuntimeException('An administrator cannot access the bootstrap route.');
}
if ($controller->get_bootstrap() !== array('version' => '1.0.0')) {
    throw new RuntimeException('Bootstrap response contains unexpected feature data.');
}
echo "REST capability tests passed.\n";

function register_rest_route($namespace, $route, $args): void {
    $GLOBALS['test_routes'][$namespace][] = $route;
}
foreach (array(
    \TechByIt\SMTP\Rest\BootstrapController::class,
    \TechByIt\SMTP\Rest\ProviderController::class,
    \TechByIt\SMTP\Rest\SettingsController::class,
    \TechByIt\SMTP\Rest\TestMailController::class,
) as $class) {
    (new ReflectionClass($class))->newInstanceWithoutConstructor()->register_routes();
}
if ($GLOBALS['test_routes']['techbyit-smtp/v1'] !== $GLOBALS['test_routes']['flowmail-smtp/v1'] || $GLOBALS['test_routes']['techbyit-smtp/v1'] !== $GLOBALS['test_routes']['mailflow-smtp/v1'] || count($GLOBALS['test_routes']['techbyit-smtp/v1']) !== 7) {
    throw new RuntimeException('New and legacy REST routes differ.');
}
echo "REST namespace compatibility tests passed.\n";

$sql = Installer::schema_sql('wp_', 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
foreach (array('wp_mailflow_smtp_logs', 'message_id varchar(255)', 'attempt_count int(10) unsigned', 'PRIMARY KEY  (id)', 'KEY status_created (status,created_at)') as $fragment) {
    if (strpos($sql, $fragment) === false) {
        throw new RuntimeException('Log schema missing: ' . $fragment);
    }
}
echo "Log schema tests passed.\n";

require __DIR__ . '/phase2.php';
require __DIR__ . '/phase3.php';
