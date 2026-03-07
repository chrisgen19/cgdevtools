<?php

defined('ABSPATH') || exit;

class CGDevTools_Admin_Bar {

    public function __construct() {
        add_action('admin_bar_menu', [$this, 'add_admin_bar_node'], 100);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_bar_assets']);
    }

    public function add_admin_bar_node(\WP_Admin_Bar $admin_bar): void {
        if (!current_user_can('manage_options')) {
            return;
        }

        $last_scan = get_option('cgdevtools_last_scan', []);
        $fail_count = 0;

        if (!empty($last_scan['results'])) {
            foreach ($last_scan['results'] as $result) {
                if ($result['status'] === 'fail') {
                    $fail_count++;
                }
            }
        }

        $badge = $fail_count > 0
            ? sprintf(' <span class="cgdevtools-bar-badge">%d</span>', $fail_count)
            : '';

        $admin_bar->add_node([
            'id'    => 'cgdevtools',
            'title' => 'Dev Tools' . $badge,
            'href'  => '#',
            'meta'  => [
                'class' => 'cgdevtools-admin-bar-node',
                'title' => __('Run CGDevTools Scan', 'cgdevtools'),
            ],
        ]);

        $admin_bar->add_node([
            'id'     => 'cgdevtools-scan',
            'parent' => 'cgdevtools',
            'title'  => __('Quick Scan', 'cgdevtools'),
            'href'   => '#',
            'meta'   => [
                'class'   => 'cgdevtools-quick-scan',
                'onclick' => 'cgdevtoolsQuickScan(); return false;',
            ],
        ]);

        $admin_bar->add_node([
            'id'     => 'cgdevtools-staging',
            'parent' => 'cgdevtools',
            'title'  => __('Staging Scan', 'cgdevtools'),
            'href'   => admin_url('admin.php?page=cgdevtools&tab=staging'),
        ]);

        $admin_bar->add_node([
            'id'     => 'cgdevtools-production',
            'parent' => 'cgdevtools',
            'title'  => __('Production Scan', 'cgdevtools'),
            'href'   => admin_url('admin.php?page=cgdevtools&tab=production'),
        ]);

        $admin_bar->add_node([
            'id'     => 'cgdevtools-settings',
            'parent' => 'cgdevtools',
            'title'  => __('Settings', 'cgdevtools'),
            'href'   => admin_url('admin.php?page=cgdevtools-settings'),
        ]);
    }

    public function enqueue_assets(): void {
        if (!is_admin_bar_showing() || !current_user_can('manage_options')) {
            return;
        }

        $this->enqueue_admin_bar_assets('');
    }

    public function enqueue_admin_bar_assets(string $hook = ''): void {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Skip if CGDevTools settings page already enqueued the same assets
        if (wp_script_is('cgdevtools-admin', 'enqueued')) {
            return;
        }

        wp_enqueue_style(
            'cgdevtools-admin-bar',
            CGDEVTOOLS_PLUGIN_URL . 'assets/css/admin.css',
            [],
            CGDEVTOOLS_VERSION
        );

        wp_enqueue_script(
            'cgdevtools-admin-bar',
            CGDEVTOOLS_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            CGDEVTOOLS_VERSION,
            true
        );

        if (!wp_script_is('cgdevtools-admin', 'enqueued')) {
            wp_localize_script('cgdevtools-admin-bar', 'cgdevtools', [
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
    }
}
