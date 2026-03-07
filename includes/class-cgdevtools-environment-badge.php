<?php

defined('ABSPATH') || exit;

class CGDevTools_Environment_Badge {

    public function __construct() {
        $settings = get_option('cgdevtools_environment', []);

        if (!empty($settings['badge_enabled'])) {
            add_action('wp_footer', [$this, 'render_badge']);
            add_action('admin_footer', [$this, 'render_badge']);
            add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
            add_action('admin_enqueue_scripts', [$this, 'enqueue_styles']);
        }

        // Inject noindex/nofollow if enabled
        if (get_option('cgdevtools_inject_noindex')) {
            add_action('wp_head', [$this, 'inject_noindex'], 1);
        }

        // Inject X-Robots-Tag header if enabled
        if (get_option('cgdevtools_inject_x_robots')) {
            add_action('send_headers', [$this, 'inject_x_robots_header']);
        }

        // Override robots.txt to block all crawlers
        if (get_option('cgdevtools_override_robots_txt')) {
            add_filter('robots_txt', [$this, 'override_robots_txt'], 999, 2);
        }

        // Disable WordPress core sitemaps
        if (get_option('cgdevtools_disable_sitemaps')) {
            add_filter('wp_sitemaps_enabled', '__return_false');
        }
    }

    public function enqueue_styles(): void {
        wp_enqueue_style(
            'cgdevtools-badge',
            CGDEVTOOLS_PLUGIN_URL . 'assets/css/badge.css',
            [],
            CGDEVTOOLS_VERSION
        );
    }

    public function render_badge(): void {
        $settings = get_option('cgdevtools_environment', []);
        $type = strtoupper($settings['type'] ?? 'STAGING');

        $colors = match ($settings['type'] ?? 'staging') {
            'local'   => ['bg' => '#16a34a', 'text' => '#fff'],
            'dev'     => ['bg' => '#2563eb', 'text' => '#fff'],
            'staging' => ['bg' => '#d97706', 'text' => '#fff'],
            'uat'     => ['bg' => '#7c3aed', 'text' => '#fff'],
            default   => ['bg' => '#d97706', 'text' => '#fff'],
        };

        printf(
            '<div id="cgdevtools-badge" style="--badge-bg:%s;--badge-text:%s;">%s</div>',
            esc_attr($colors['bg']),
            esc_attr($colors['text']),
            esc_html($type)
        );
    }

    public function inject_noindex(): void {
        echo '<meta name="robots" content="noindex, nofollow, noarchive, nosnippet">' . "\n";
    }

    public function inject_x_robots_header(): void {
        if (!headers_sent()) {
            header('X-Robots-Tag: noindex, nofollow, noarchive');
        }
    }

    /**
     * Override robots.txt to block all crawlers.
     */
    public function override_robots_txt(string $output, bool $public): string {
        return "User-agent: *\nDisallow: /\n";
    }
}
