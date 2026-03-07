<?php

defined('ABSPATH') || exit;

class CGDevTools_Settings {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_cgdevtools_save_settings', [$this, 'ajax_save_settings']);
        add_action('wp_ajax_cgdevtools_upload_logo', [$this, 'ajax_upload_logo']);
        add_action('wp_ajax_cgdevtools_delete_emails', [$this, 'ajax_delete_emails']);
    }

    public function add_menu(): void {
        add_menu_page(
            __('Dev Tools', 'cgdevtools'),
            __('Dev Tools', 'cgdevtools'),
            'manage_options',
            'cgdevtools',
            [$this, 'render_dashboard'],
            'dashicons-admin-tools',
            80
        );

        add_submenu_page(
            'cgdevtools',
            __('Dashboard', 'cgdevtools'),
            __('Dashboard', 'cgdevtools'),
            'manage_options',
            'cgdevtools',
            [$this, 'render_dashboard']
        );

        add_submenu_page(
            'cgdevtools',
            __('Settings', 'cgdevtools'),
            __('Settings', 'cgdevtools'),
            'manage_options',
            'cgdevtools-settings',
            [$this, 'render_settings']
        );

        add_submenu_page(
            'cgdevtools',
            __('Production Ready', 'cgdevtools'),
            __('Production Ready', 'cgdevtools'),
            'manage_options',
            'cgdevtools-production',
            [$this, 'render_production']
        );

        add_submenu_page(
            'cgdevtools',
            __('Intercepted Emails', 'cgdevtools'),
            __('Emails', 'cgdevtools'),
            'manage_options',
            'cgdevtools-emails',
            [$this, 'render_emails']
        );
    }

    public function enqueue_assets(string $hook): void {
        if (!str_contains($hook, 'cgdevtools')) {
            return;
        }

        wp_enqueue_style(
            'cgdevtools-admin',
            CGDEVTOOLS_PLUGIN_URL . 'assets/css/admin.css',
            [],
            CGDEVTOOLS_VERSION
        );

        wp_enqueue_script(
            'cgdevtools-admin',
            CGDEVTOOLS_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            CGDEVTOOLS_VERSION,
            true
        );

        wp_enqueue_media();

        wp_localize_script('cgdevtools-admin', 'cgdevtools', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('cgdevtools_nonce'),
            'strings'  => [
                'scanning'    => __('Scanning...', 'cgdevtools'),
                'fixing'      => __('Applying fixes...', 'cgdevtools'),
                'saved'       => __('Settings saved!', 'cgdevtools'),
                'error'       => __('An error occurred.', 'cgdevtools'),
                'confirm_fix' => __('Apply all available fixes?', 'cgdevtools'),
            ],
        ]);
    }

    public function render_dashboard(): void {
        include CGDEVTOOLS_PLUGIN_DIR . 'templates/admin-dashboard.php';
    }

    public function render_settings(): void {
        include CGDEVTOOLS_PLUGIN_DIR . 'templates/admin-settings.php';
    }

    public function render_production(): void {
        include CGDEVTOOLS_PLUGIN_DIR . 'templates/admin-production.php';
    }

    public function render_emails(): void {
        include CGDEVTOOLS_PLUGIN_DIR . 'templates/admin-emails.php';
    }

    public function ajax_save_settings(): void {
        check_ajax_referer('cgdevtools_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized', 403);
        }

        $section = sanitize_text_field($_POST['section'] ?? '');

        match ($section) {
            'password_protection' => $this->save_password_settings(),
            'environment'         => $this->save_environment_settings(),
            'email_interception'  => $this->save_email_settings(),
            default               => wp_send_json_error('Invalid section'),
        };
    }

    private function save_password_settings(): void {
        $current = get_option('cgdevtools_password_protection', []);

        $settings = [
            'enabled'    => !empty($_POST['enabled']),
            'password'   => $current['password'] ?? '',
            'logo_url'   => esc_url_raw($_POST['logo_url'] ?? ''),
            'bg_color'   => sanitize_hex_color($_POST['bg_color'] ?? '#1a1a2e'),
            'btn_color'  => sanitize_hex_color($_POST['btn_color'] ?? '#e94560'),
            'text_color' => sanitize_hex_color($_POST['text_color'] ?? '#ffffff'),
        ];

        $new_password = $_POST['password'] ?? '';
        if (!empty($new_password)) {
            $settings['password'] = wp_hash_password($new_password);
            $settings['password_plain_hint'] = substr($new_password, 0, 2) . str_repeat('*', max(0, strlen($new_password) - 2));
        }

        update_option('cgdevtools_password_protection', $settings);
        wp_send_json_success(['message' => 'Password protection settings saved.']);
    }

    private function save_environment_settings(): void {
        $settings = [
            'type'           => sanitize_text_field($_POST['type'] ?? 'staging'),
            'badge_enabled'  => !empty($_POST['badge_enabled']),
            'banner_enabled' => !empty($_POST['banner_enabled']),
            'production_url' => esc_url_raw($_POST['production_url'] ?? ''),
        ];

        update_option('cgdevtools_environment', $settings);
        wp_send_json_success(['message' => 'Environment settings saved.']);
    }

    private function save_email_settings(): void {
        $settings = [
            'enabled' => !empty($_POST['enabled']),
        ];

        update_option('cgdevtools_email_interception', $settings);
        wp_send_json_success(['message' => 'Email interception settings saved.']);
    }

    public function ajax_upload_logo(): void {
        check_ajax_referer('cgdevtools_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized', 403);
        }

        if (empty($_FILES['logo'])) {
            wp_send_json_error('No file uploaded.');
        }

        $upload = wp_handle_upload($_FILES['logo'], ['test_form' => false]);

        if (isset($upload['error'])) {
            wp_send_json_error($upload['error']);
        }

        wp_send_json_success(['url' => $upload['url']]);
    }

    public function ajax_delete_emails(): void {
        check_ajax_referer('cgdevtools_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized', 403);
        }

        global $wpdb;
        $table = $wpdb->prefix . 'cgdevtools_emails';
        $wpdb->query("TRUNCATE TABLE {$table}");

        wp_send_json_success(['message' => 'All intercepted emails deleted.']);
    }
}
