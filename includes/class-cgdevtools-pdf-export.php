<?php

defined('ABSPATH') || exit;

class CGDevTools_PDF_Export {

    public function __construct() {
        add_action('admin_post_cgdevtools_export_pdf', [$this, 'export_pdf']);
        add_action('admin_post_cgdevtools_export_production_pdf', [$this, 'export_production_pdf']);
    }

    public function export_pdf(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        check_admin_referer('cgdevtools_export_pdf');

        $last_scan = get_option('cgdevtools_last_scan', []);
        $results = $last_scan['results'] ?? [];
        $timestamp = $last_scan['timestamp'] ?? current_time('mysql');
        $site_url = home_url();
        $site_name = get_bloginfo('name');
        $report_title = 'Staging Readiness';
        $report_subtitle = 'Staging Environment Readiness Assessment';

        include CGDEVTOOLS_PLUGIN_DIR . 'templates/pdf-report.php';
        exit;
    }

    public function export_production_pdf(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        check_admin_referer('cgdevtools_export_production_pdf');

        $last_scan = get_option('cgdevtools_last_production_scan', []);
        $results = $last_scan['results'] ?? [];
        $timestamp = $last_scan['timestamp'] ?? current_time('mysql');
        $site_url = home_url();
        $site_name = get_bloginfo('name');
        $report_title = 'Production Ready';
        $report_subtitle = 'Production Deployment Readiness Assessment';

        include CGDEVTOOLS_PLUGIN_DIR . 'templates/pdf-report.php';
        exit;
    }
}
