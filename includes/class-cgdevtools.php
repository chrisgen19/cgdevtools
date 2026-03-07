<?php

defined('ABSPATH') || exit;

final class CGDevTools {

    private static ?CGDevTools $instance = null;

    public static function instance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_dependencies();
        $this->init_components();
        $this->check_production_url();
    }

    private function load_dependencies(): void {
        $includes = [
            'class-cgdevtools-scanner',
            'class-cgdevtools-settings',
            'class-cgdevtools-password-protection',
            'class-cgdevtools-email-interceptor',
            'class-cgdevtools-environment-badge',
            'class-cgdevtools-admin-bar',
            'class-cgdevtools-admin-notice',
            'class-cgdevtools-pdf-export',
        ];

        foreach ($includes as $file) {
            require_once CGDEVTOOLS_PLUGIN_DIR . "includes/{$file}.php";
        }

        if (defined('WP_CLI') && WP_CLI) {
            require_once CGDEVTOOLS_PLUGIN_DIR . 'includes/class-cgdevtools-cli.php';
        }
    }

    private function init_components(): void {
        new CGDevTools_Scanner();
        new CGDevTools_Settings();
        new CGDevTools_Password_Protection();
        new CGDevTools_Email_Interceptor();
        new CGDevTools_Environment_Badge();
        new CGDevTools_Admin_Bar();
        new CGDevTools_Admin_Notice();
        new CGDevTools_PDF_Export();

        if (defined('WP_CLI') && WP_CLI) {
            WP_CLI::add_command('cgdevtools', 'CGDevTools_CLI');
        }
    }

    private function check_production_url(): void {
        $env = get_option('cgdevtools_environment', []);
        $production_url = $env['production_url'] ?? '';

        if (empty($production_url)) {
            return;
        }

        $current = untrailingslashit(strtolower(home_url()));
        $production = untrailingslashit(strtolower($production_url));

        if ($current === $production) {
            add_action('admin_notices', function () {
                printf(
                    '<div class="notice notice-error"><p><strong>CGDevTools WARNING:</strong> %s</p></div>',
                    esc_html__('This site URL matches the production URL! CGDevTools should NOT be active on production. Please deactivate this plugin immediately.', 'cgdevtools')
                );
            });

            add_action('admin_notices', function () {
                printf(
                    '<div class="notice notice-warning is-dismissible"><p>%s <a href="%s">%s</a></p></div>',
                    esc_html__('CGDevTools detected a production environment.', 'cgdevtools'),
                    esc_url(admin_url('plugins.php')),
                    esc_html__('Click here to deactivate', 'cgdevtools')
                );
            });
        }
    }
}
