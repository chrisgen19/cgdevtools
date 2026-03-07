<?php

defined('ABSPATH') || exit;

class CGDevTools_Password_Protection {

    public function __construct() {
        add_action('template_redirect', [$this, 'protect_frontend']);
        add_action('wp_ajax_nopriv_cgdevtools_login', [$this, 'handle_login']);
        add_action('wp_ajax_cgdevtools_login', [$this, 'handle_login']);
    }

    public function protect_frontend(): void {
        if (is_admin()) {
            return;
        }

        // Allow WP AJAX/REST/cron/login through
        if (
            wp_doing_ajax() ||
            wp_doing_cron() ||
            str_contains($_SERVER['REQUEST_URI'] ?? '', 'wp-login.php') ||
            str_contains($_SERVER['REQUEST_URI'] ?? '', 'wp-json/')
        ) {
            return;
        }

        $settings = get_option('cgdevtools_password_protection', []);

        if (empty($settings['enabled'])) {
            return;
        }

        // Logged-in administrators bypass
        if (is_user_logged_in() && current_user_can('manage_options')) {
            return;
        }

        // Check cookie
        if ($this->has_valid_cookie()) {
            return;
        }

        // Show lock screen
        $this->render_lock_screen($settings);
        exit;
    }

    public function handle_login(): void {
        $nonce = $_POST['nonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'cgdevtools_lock_nonce')) {
            wp_send_json_error('Invalid nonce.');
        }

        $settings = get_option('cgdevtools_password_protection', []);
        $password = $_POST['password'] ?? '';

        if (empty($settings['password'])) {
            wp_send_json_error('No password has been set. Configure one in Dev Tools settings.');
        }

        $check = wp_check_password($password, $settings['password']);

        if ($check) {
            $token = wp_generate_password(32, false);
            $hash = wp_hash($token);
            set_transient('cgdevtools_access_' . $hash, true, DAY_IN_SECONDS);

            setcookie('cgdevtools_access', $token, [
                'expires'  => time() + DAY_IN_SECONDS,
                'path'     => '/',
                'httponly'  => true,
                'secure'   => is_ssl(),
                'samesite' => 'Lax',
            ]);

            wp_send_json_success(['redirect' => home_url('/')]);
        }

        wp_send_json_error('Incorrect password.');
    }

    private function has_valid_cookie(): bool {
        $token = $_COOKIE['cgdevtools_access'] ?? '';
        if (empty($token)) {
            return false;
        }

        $hash = wp_hash($token);
        return (bool) get_transient('cgdevtools_access_' . $hash);
    }

    private function render_lock_screen(array $settings): void {
        $logo_url   = $settings['logo_url'] ?? '';
        $bg_color   = $settings['bg_color'] ?? '#1a1a2e';
        $btn_color  = $settings['btn_color'] ?? '#e94560';
        $text_color = $settings['text_color'] ?? '#ffffff';
        $nonce      = wp_create_nonce('cgdevtools_lock_nonce');
        $ajax_url   = admin_url('admin-ajax.php');

        include CGDEVTOOLS_PLUGIN_DIR . 'templates/lock-screen.php';
    }
}
