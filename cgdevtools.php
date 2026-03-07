<?php
/**
 * Plugin Name: CGDevTools
 * Plugin URI: https://cgdevtools.com
 * Description: Development & staging environment manager — site scanner, password protection, email interception, and environment indicators.
 * Version: 1.0.0
 * Requires at least: 6.7
 * Requires PHP: 8.2
 * Author: CGDev
 * Author URI: https://cgdevtools.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: cgdevtools
 */

defined('ABSPATH') || exit;

define('CGDEVTOOLS_VERSION', '1.0.0');
define('CGDEVTOOLS_PLUGIN_FILE', __FILE__);
define('CGDEVTOOLS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CGDEVTOOLS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CGDEVTOOLS_MIN_PHP', '8.2');
define('CGDEVTOOLS_MIN_WP', '6.7');

/**
 * Check environment requirements before loading.
 */
function cgdevtools_check_requirements(): bool {
    $errors = [];

    if (version_compare(PHP_VERSION, CGDEVTOOLS_MIN_PHP, '<')) {
        $errors[] = sprintf(
            __('CGDevTools requires PHP %s or higher. You are running PHP %s.', 'cgdevtools'),
            CGDEVTOOLS_MIN_PHP,
            PHP_VERSION
        );
    }

    global $wp_version;
    if (version_compare($wp_version, CGDEVTOOLS_MIN_WP, '<')) {
        $errors[] = sprintf(
            __('CGDevTools requires WordPress %s or higher. You are running WordPress %s.', 'cgdevtools'),
            CGDEVTOOLS_MIN_WP,
            $wp_version
        );
    }

    if (!empty($errors)) {
        add_action('admin_notices', function () use ($errors) {
            foreach ($errors as $error) {
                printf('<div class="notice notice-error"><p><strong>CGDevTools:</strong> %s</p></div>', esc_html($error));
            }
        });
        return false;
    }

    return true;
}

/**
 * Initialize plugin.
 */
function cgdevtools_init(): void {
    if (!cgdevtools_check_requirements()) {
        return;
    }

    require_once CGDEVTOOLS_PLUGIN_DIR . 'includes/class-cgdevtools.php';
    CGDevTools::instance();
}

add_action('plugins_loaded', 'cgdevtools_init');

/**
 * Activation hook.
 */
register_activation_hook(__FILE__, function (): void {
    if (version_compare(PHP_VERSION, CGDEVTOOLS_MIN_PHP, '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            sprintf('CGDevTools requires PHP %s or higher.', CGDEVTOOLS_MIN_PHP),
            'Plugin Activation Error',
            ['back_link' => true]
        );
    }

    $defaults = [
        'cgdevtools_password_protection' => [
            'enabled'    => false,
            'password'   => '',
            'logo_url'   => '',
            'bg_color'   => '#1a1a2e',
            'btn_color'  => '#e94560',
            'text_color' => '#ffffff',
        ],
        'cgdevtools_environment' => [
            'type'           => 'staging',
            'badge_enabled'  => true,
            'banner_enabled' => true,
            'production_url' => '',
        ],
        'cgdevtools_email_interception' => [
            'enabled' => true,
        ],
        'cgdevtools_last_scan' => [],
    ];

    foreach ($defaults as $key => $value) {
        if (get_option($key) === false) {
            add_option($key, $value);
        }
    }

    // Create email log table
    global $wpdb;
    $table = $wpdb->prefix . 'cgdevtools_emails';
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS {$table} (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        to_email VARCHAR(255) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        message LONGTEXT NOT NULL,
        headers TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_created (created_at)
    ) {$charset};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);

    add_option('cgdevtools_db_version', '1.0.0');
});

/**
 * Deactivation hook.
 */
register_deactivation_hook(__FILE__, function (): void {
    delete_transient('cgdevtools_scan_cache');
});
