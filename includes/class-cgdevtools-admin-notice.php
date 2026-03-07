<?php

defined('ABSPATH') || exit;

class CGDevTools_Admin_Notice {

    public function __construct() {
        $settings = get_option('cgdevtools_environment', []);

        if (!empty($settings['banner_enabled'])) {
            add_action('admin_notices', [$this, 'render_banner']);
            add_action('admin_head', [$this, 'banner_styles']);
        }
    }

    public function render_banner(): void {
        $settings = get_option('cgdevtools_environment', []);
        $type = strtoupper($settings['type'] ?? 'STAGING');
        $site_name = get_bloginfo('name');

        $colors = match ($settings['type'] ?? 'staging') {
            'local'   => '#16a34a',
            'dev'     => '#2563eb',
            'staging' => '#d97706',
            'uat'     => '#7c3aed',
            default   => '#d97706',
        };

        printf(
            '<div class="cgdevtools-admin-banner" style="background:%s;">
                <strong>%s ENVIRONMENT</strong> &mdash; %s &mdash; This is not the production site.
                <a href="%s" style="color:#fff;text-decoration:underline;">View Scan</a>
            </div>',
            esc_attr($colors),
            esc_html($type),
            esc_html($site_name),
            esc_url(admin_url('admin.php?page=cgdevtools'))
        );
    }

    public function banner_styles(): void {
        echo '<style>
            .cgdevtools-admin-banner {
                color: #fff;
                padding: 8px 16px;
                text-align: center;
                font-size: 13px;
                margin: 0 0 10px 0;
                border-radius: 4px;
            }
        </style>';
    }
}
