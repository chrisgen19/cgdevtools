<?php

defined('ABSPATH') || exit;

class CGDevTools_Scanner {

    public function __construct() {
        add_action('wp_ajax_cgdevtools_run_scan', [$this, 'ajax_run_scan']);
        add_action('wp_ajax_cgdevtools_quick_fix', [$this, 'ajax_quick_fix']);
        add_action('wp_ajax_cgdevtools_fix_all', [$this, 'ajax_fix_all']);
        add_action('wp_ajax_cgdevtools_run_production_scan', [$this, 'ajax_run_production_scan']);
        add_action('wp_ajax_cgdevtools_production_quick_fix', [$this, 'ajax_production_quick_fix']);
        add_action('wp_ajax_cgdevtools_production_fix_all', [$this, 'ajax_production_fix_all']);
    }

    public function ajax_run_scan(): void {
        check_ajax_referer('cgdevtools_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized', 403);
        }

        $results = $this->run_scan();
        update_option('cgdevtools_last_scan', [
            'results'   => $results,
            'timestamp' => current_time('mysql'),
        ]);

        wp_send_json_success($results);
    }

    public function ajax_quick_fix(): void {
        check_ajax_referer('cgdevtools_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized', 403);
        }

        $check_id = sanitize_text_field($_POST['check_id'] ?? '');
        $result = $this->apply_fix($check_id);

        wp_send_json_success($result);
    }

    public function ajax_fix_all(): void {
        check_ajax_referer('cgdevtools_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized', 403);
        }

        $results = $this->run_scan();
        $fixed = [];

        foreach ($results as $result) {
            if ($result['status'] !== 'pass' && $result['fixable']) {
                $fix = $this->apply_fix($result['id']);
                $fixed[] = $fix;
            }
        }

        $new_results = $this->run_scan();
        update_option('cgdevtools_last_scan', [
            'results'   => $new_results,
            'timestamp' => current_time('mysql'),
        ]);

        wp_send_json_success([
            'fixed'   => $fixed,
            'results' => $new_results,
        ]);
    }

    // =========================================================================
    // Production Scan AJAX
    // =========================================================================

    public function ajax_run_production_scan(): void {
        check_ajax_referer('cgdevtools_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized', 403);
        }

        $results = $this->run_production_scan();
        update_option('cgdevtools_last_production_scan', [
            'results'   => $results,
            'timestamp' => current_time('mysql'),
        ]);

        wp_send_json_success($results);
    }

    public function ajax_production_quick_fix(): void {
        check_ajax_referer('cgdevtools_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized', 403);
        }

        $check_id = sanitize_text_field($_POST['check_id'] ?? '');
        $result = $this->apply_production_fix($check_id);

        wp_send_json_success($result);
    }

    public function ajax_production_fix_all(): void {
        check_ajax_referer('cgdevtools_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized', 403);
        }

        $results = $this->run_production_scan();
        $fixed = [];

        foreach ($results as $result) {
            if ($result['status'] !== 'pass' && $result['fixable']) {
                $fix = $this->apply_production_fix($result['id']);
                $fixed[] = $fix;
            }
        }

        $new_results = $this->run_production_scan();
        update_option('cgdevtools_last_production_scan', [
            'results'   => $new_results,
            'timestamp' => current_time('mysql'),
        ]);

        wp_send_json_success([
            'fixed'   => $fixed,
            'results' => $new_results,
        ]);
    }

    // =========================================================================
    // Production Scan Checks
    // =========================================================================

    /**
     * Run all production readiness checks.
     */
    public function run_production_scan(): array {
        return [
            $this->prod_check_search_engine_visibility(),
            $this->prod_check_noindex_injection(),
            $this->prod_check_x_robots_injection(),
            $this->prod_check_robots_txt_override(),
            $this->prod_check_sitemap_disabled(),
            $this->prod_check_password_protection(),
            $this->prod_check_email_interception(),
            $this->prod_check_environment_badge(),
            $this->prod_check_ssl(),
            $this->prod_check_wp_debug(),
            $this->prod_check_caching(),
            $this->prod_check_cgdevtools_active(),
        ];
    }

    private function prod_check_search_engine_visibility(): array {
        $blog_public = get_option('blog_public');

        return [
            'id'          => 'prod_search_engine_visibility',
            'label'       => 'Search Engine Visibility',
            'description' => $blog_public == '1'
                ? 'Search engines are allowed to index this site.'
                : 'Search engines are currently discouraged. Must be enabled for production.',
            'status'      => $blog_public == '1' ? 'pass' : 'fail',
            'fixable'     => $blog_public != '1',
            'category'    => 'indexing',
        ];
    }

    private function prod_check_noindex_injection(): array {
        $active = get_option('cgdevtools_inject_noindex');

        return [
            'id'          => 'prod_noindex_injection',
            'label'       => 'Noindex Meta Tag Injection',
            'description' => $active
                ? 'CGDevTools is injecting noindex/nofollow meta tags. Must be removed for production.'
                : 'No CGDevTools noindex injection active.',
            'status'      => $active ? 'fail' : 'pass',
            'fixable'     => (bool) $active,
            'category'    => 'indexing',
        ];
    }

    private function prod_check_x_robots_injection(): array {
        $active = get_option('cgdevtools_inject_x_robots');

        return [
            'id'          => 'prod_x_robots_injection',
            'label'       => 'X-Robots-Tag Header Injection',
            'description' => $active
                ? 'CGDevTools is sending X-Robots-Tag noindex header. Must be removed for production.'
                : 'No CGDevTools X-Robots-Tag injection active.',
            'status'      => $active ? 'fail' : 'pass',
            'fixable'     => (bool) $active,
            'category'    => 'indexing',
        ];
    }

    private function prod_check_robots_txt_override(): array {
        $active = get_option('cgdevtools_override_robots_txt');

        return [
            'id'          => 'prod_robots_txt_override',
            'label'       => 'Robots.txt Override',
            'description' => $active
                ? 'CGDevTools is overriding robots.txt to block all crawlers. Must be removed for production.'
                : 'No CGDevTools robots.txt override active.',
            'status'      => $active ? 'fail' : 'pass',
            'fixable'     => (bool) $active,
            'category'    => 'indexing',
        ];
    }

    private function prod_check_sitemap_disabled(): array {
        $active = get_option('cgdevtools_disable_sitemaps');

        return [
            'id'          => 'prod_sitemap_disabled',
            'label'       => 'Sitemap Generation',
            'description' => $active
                ? 'CGDevTools has disabled WordPress sitemaps. Must be re-enabled for production.'
                : 'WordPress sitemaps are not blocked by CGDevTools.',
            'status'      => $active ? 'fail' : 'pass',
            'fixable'     => (bool) $active,
            'category'    => 'indexing',
        ];
    }

    private function prod_check_password_protection(): array {
        $settings = get_option('cgdevtools_password_protection', []);
        $enabled = !empty($settings['enabled']);

        return [
            'id'          => 'prod_password_protection',
            'label'       => 'Password Protection',
            'description' => $enabled
                ? 'Frontend password protection is still active. Must be disabled for production.'
                : 'Frontend password protection is disabled.',
            'status'      => $enabled ? 'fail' : 'pass',
            'fixable'     => $enabled,
            'category'    => 'security',
        ];
    }

    private function prod_check_email_interception(): array {
        $settings = get_option('cgdevtools_email_interception', []);
        $enabled = !empty($settings['enabled']);

        return [
            'id'          => 'prod_email_interception',
            'label'       => 'Email Interception',
            'description' => $enabled
                ? 'Emails are being intercepted. Real emails will NOT be sent. Must be disabled for production.'
                : 'Email interception is disabled. Emails will be sent normally.',
            'status'      => $enabled ? 'fail' : 'pass',
            'fixable'     => $enabled,
            'category'    => 'security',
        ];
    }

    private function prod_check_environment_badge(): array {
        $settings = get_option('cgdevtools_environment', []);
        $badge = !empty($settings['badge_enabled']);
        $banner = !empty($settings['banner_enabled']);
        $active = $badge || $banner;

        $parts = [];
        if ($badge) $parts[] = 'badge';
        if ($banner) $parts[] = 'admin banner';

        return [
            'id'          => 'prod_environment_badge',
            'label'       => 'Environment Badge & Banner',
            'description' => $active
                ? 'Environment ' . implode(' and ', $parts) . ' still active. Must be disabled for production.'
                : 'Environment badge and banner are disabled.',
            'status'      => $active ? 'fail' : 'pass',
            'fixable'     => $active,
            'category'    => 'configuration',
        ];
    }

    private function prod_check_ssl(): array {
        return [
            'id'          => 'prod_ssl',
            'label'       => 'SSL / HTTPS',
            'description' => is_ssl()
                ? 'Site is served over HTTPS.'
                : 'Site is NOT served over HTTPS. SSL is required for production.',
            'status'      => is_ssl() ? 'pass' : 'fail',
            'fixable'     => false,
            'category'    => 'security',
        ];
    }

    private function prod_check_wp_debug(): array {
        $debug_on = defined('WP_DEBUG') && WP_DEBUG;

        return [
            'id'          => 'prod_wp_debug',
            'label'       => 'WP_DEBUG Mode',
            'description' => $debug_on
                ? 'WP_DEBUG is enabled. Must be disabled on production to hide errors from visitors.'
                : 'WP_DEBUG is disabled.',
            'status'      => $debug_on ? 'fail' : 'pass',
            'fixable'     => false,
            'category'    => 'configuration',
        ];
    }

    private function prod_check_caching(): array {
        $caching_plugins = [
            'w3-total-cache/w3-total-cache.php',
            'wp-super-cache/wp-cache.php',
            'wp-fastest-cache/wpFastestCache.php',
            'litespeed-cache/litespeed-cache.php',
            'wp-rocket/wp-rocket.php',
            'autoptimize/autoptimize.php',
            'cache-enabler/cache-enabler.php',
        ];

        $active = [];
        foreach ($caching_plugins as $plugin) {
            if (is_plugin_active($plugin)) {
                $active[] = dirname($plugin);
            }
        }

        return [
            'id'          => 'prod_caching',
            'label'       => 'Caching Plugin',
            'description' => !empty($active)
                ? 'Caching is active: ' . implode(', ', $active) . '.'
                : 'No caching plugin detected. Consider installing one for production performance.',
            'status'      => !empty($active) ? 'pass' : 'warning',
            'fixable'     => false,
            'category'    => 'performance',
        ];
    }

    private function prod_check_cgdevtools_active(): array {
        return [
            'id'          => 'prod_cgdevtools_active',
            'label'       => 'CGDevTools Plugin',
            'description' => 'CGDevTools is active. Deactivate and remove this plugin before going live.',
            'status'      => 'warning',
            'fixable'     => false,
            'link'        => admin_url('plugins.php'),
            'link_label'  => 'Plugins',
            'category'    => 'configuration',
        ];
    }

    // =========================================================================
    // Production Fixes
    // =========================================================================

    private function apply_production_fix(string $check_id): array {
        return match ($check_id) {
            'prod_search_engine_visibility' => $this->prod_fix_search_engine_visibility(),
            'prod_noindex_injection'        => $this->prod_fix_noindex_injection(),
            'prod_x_robots_injection'       => $this->prod_fix_x_robots_injection(),
            'prod_robots_txt_override'      => $this->prod_fix_robots_txt_override(),
            'prod_sitemap_disabled'         => $this->prod_fix_sitemap_disabled(),
            'prod_password_protection'      => $this->prod_fix_password_protection(),
            'prod_email_interception'       => $this->prod_fix_email_interception(),
            'prod_environment_badge'        => $this->prod_fix_environment_badge(),
            default                         => ['success' => false, 'message' => 'No auto-fix available for this check.'],
        };
    }

    private function prod_fix_search_engine_visibility(): array {
        update_option('blog_public', '1');
        return ['success' => true, 'message' => 'Search engines are now allowed to index this site.'];
    }

    private function prod_fix_noindex_injection(): array {
        delete_option('cgdevtools_inject_noindex');
        return ['success' => true, 'message' => 'Noindex meta tag injection has been removed.'];
    }

    private function prod_fix_x_robots_injection(): array {
        delete_option('cgdevtools_inject_x_robots');
        return ['success' => true, 'message' => 'X-Robots-Tag header injection has been removed.'];
    }

    private function prod_fix_robots_txt_override(): array {
        delete_option('cgdevtools_override_robots_txt');
        return ['success' => true, 'message' => 'Robots.txt override has been removed. WordPress default robots.txt is restored.'];
    }

    private function prod_fix_sitemap_disabled(): array {
        delete_option('cgdevtools_disable_sitemaps');
        return ['success' => true, 'message' => 'WordPress sitemaps have been re-enabled.'];
    }

    private function prod_fix_password_protection(): array {
        $settings = get_option('cgdevtools_password_protection', []);
        $settings['enabled'] = false;
        update_option('cgdevtools_password_protection', $settings);
        return ['success' => true, 'message' => 'Frontend password protection has been disabled.'];
    }

    private function prod_fix_email_interception(): array {
        update_option('cgdevtools_email_interception', ['enabled' => false]);
        return ['success' => true, 'message' => 'Email interception has been disabled. Emails will now be sent normally.'];
    }

    private function prod_fix_environment_badge(): array {
        $settings = get_option('cgdevtools_environment', []);
        $settings['badge_enabled'] = false;
        $settings['banner_enabled'] = false;
        update_option('cgdevtools_environment', $settings);
        return ['success' => true, 'message' => 'Environment badge and admin banner have been disabled.'];
    }

    // =========================================================================
    // Staging Scan Checks
    // =========================================================================

    /**
     * Run all staging scan checks.
     *
     * @return array<int, array{id: string, label: string, description: string, status: string, fixable: bool, category: string}>
     */
    public function run_scan(): array {
        return [
            $this->check_search_engine_visibility(),
            $this->check_meta_robots(),
            $this->check_robots_txt(),
            $this->check_x_robots_header(),
            $this->check_wp_debug(),
            $this->check_ssl(),
            $this->check_caching_plugins(),
            $this->check_auto_updates(),
            $this->check_admin_email(),
            $this->check_analytics_scripts(),
            $this->check_sitemap(),
            $this->check_password_protection(),
            $this->check_email_interception(),
        ];
    }

    private function check_search_engine_visibility(): array {
        $blog_public = get_option('blog_public');

        return [
            'id'          => 'search_engine_visibility',
            'label'       => 'Search Engine Visibility',
            'description' => 'WordPress "Discourage search engines" setting should be enabled.',
            'status'      => $blog_public == '0' ? 'pass' : 'fail',
            'fixable'     => true,
            'category'    => 'indexing',
        ];
    }

    private function check_meta_robots(): array {
        $response = wp_remote_get(home_url('/'), [
            'timeout'   => 10,
            'sslverify' => false,
        ]);

        $status = 'fail';
        if (!is_wp_error($response)) {
            $body = wp_remote_retrieve_body($response);
            if (
                str_contains($body, 'noindex') &&
                str_contains($body, 'nofollow')
            ) {
                $status = 'pass';
            }
        }

        return [
            'id'          => 'meta_robots',
            'label'       => 'Meta Robots (noindex, nofollow)',
            'description' => 'Homepage should contain noindex and nofollow meta tags.',
            'status'      => $status,
            'fixable'     => true,
            'category'    => 'indexing',
        ];
    }

    private function check_robots_txt(): array {
        // If CGDevTools override is active, it's already handled
        if (get_option('cgdevtools_override_robots_txt')) {
            return [
                'id'          => 'robots_txt',
                'label'       => 'Robots.txt Blocking',
                'description' => 'Robots.txt is overridden by CGDevTools to block all crawlers.',
                'status'      => 'pass',
                'fixable'     => false,
                'category'    => 'indexing',
            ];
        }

        $response = wp_remote_get(home_url('/robots.txt'), [
            'timeout'   => 10,
            'sslverify' => false,
        ]);

        $status = 'fail';
        if (!is_wp_error($response)) {
            $body = wp_remote_retrieve_body($response);
            if (
                str_contains($body, 'Disallow: /') &&
                !str_contains($body, 'Disallow: /wp-')
            ) {
                $status = 'pass';
            }
        }

        return [
            'id'          => 'robots_txt',
            'label'       => 'Robots.txt Blocking',
            'description' => $status === 'pass'
                ? 'Robots.txt is blocking all crawlers.'
                : 'Robots.txt should block all crawlers with "Disallow: /". Click Fix to override.',
            'status'      => $status,
            'fixable'     => $status === 'fail',
            'category'    => 'indexing',
        ];
    }

    private function check_x_robots_header(): array {
        $response = wp_remote_head(home_url('/'), [
            'timeout'   => 10,
            'sslverify' => false,
        ]);

        $status = 'fail';
        if (!is_wp_error($response)) {
            $headers = wp_remote_retrieve_headers($response);
            $x_robots = $headers['x-robots-tag'] ?? '';
            if (str_contains(strtolower($x_robots), 'noindex')) {
                $status = 'pass';
            }
        }

        return [
            'id'          => 'x_robots_header',
            'label'       => 'X-Robots-Tag Header',
            'description' => 'HTTP header X-Robots-Tag should include "noindex".',
            'status'      => $status,
            'fixable'     => true,
            'category'    => 'indexing',
        ];
    }

    private function check_wp_debug(): array {
        return [
            'id'          => 'wp_debug',
            'label'       => 'WP_DEBUG Mode',
            'description' => 'WP_DEBUG should be enabled on staging for error visibility.',
            'status'      => defined('WP_DEBUG') && WP_DEBUG ? 'pass' : 'warning',
            'fixable'     => false,
            'category'    => 'configuration',
        ];
    }

    private function check_ssl(): array {
        return [
            'id'          => 'ssl',
            'label'       => 'SSL / HTTPS',
            'description' => 'Site should be served over HTTPS.',
            'status'      => is_ssl() ? 'pass' : 'warning',
            'fixable'     => false,
            'category'    => 'security',
        ];
    }

    private function check_caching_plugins(): array {
        $caching_plugins = [
            'w3-total-cache/w3-total-cache.php',
            'wp-super-cache/wp-cache.php',
            'wp-fastest-cache/wpFastestCache.php',
            'litespeed-cache/litespeed-cache.php',
            'wp-rocket/wp-rocket.php',
            'autoptimize/autoptimize.php',
            'cache-enabler/cache-enabler.php',
        ];

        $active = [];
        foreach ($caching_plugins as $plugin) {
            if (is_plugin_active($plugin)) {
                $active[] = dirname($plugin);
            }
        }

        return [
            'id'          => 'caching_plugins',
            'label'       => 'Caching Plugins',
            'description' => empty($active)
                ? 'No caching plugins detected (good for staging).'
                : 'Active caching plugins detected: ' . implode(', ', $active) . '. Consider disabling on staging.',
            'status'      => empty($active) ? 'pass' : 'warning',
            'fixable'     => false,
            'category'    => 'configuration',
        ];
    }

    private function check_auto_updates(): array {
        $auto_updates = [
            'core'    => defined('WP_AUTO_UPDATE_CORE') ? WP_AUTO_UPDATE_CORE : 'minor',
            'plugins' => get_option('auto_update_plugins', []),
            'themes'  => get_option('auto_update_themes', []),
        ];

        $core_disabled = $auto_updates['core'] === false || $auto_updates['core'] === 'false';

        return [
            'id'          => 'auto_updates',
            'label'       => 'Auto-Updates Disabled',
            'description' => 'Auto-updates should be disabled on staging to prevent unexpected changes.',
            'status'      => $core_disabled ? 'pass' : 'warning',
            'fixable'     => false,
            'category'    => 'configuration',
        ];
    }

    private function check_admin_email(): array {
        $admin_email = get_option('admin_email');
        $is_dev_email = (
            str_contains($admin_email, '+staging') ||
            str_contains($admin_email, '+dev') ||
            str_contains($admin_email, 'dev@') ||
            str_contains($admin_email, 'staging@') ||
            str_contains($admin_email, 'test@')
        );

        return [
            'id'          => 'admin_email',
            'label'       => 'Admin Email',
            'description' => $is_dev_email
                ? "Admin email ({$admin_email}) appears to be a dev/staging email."
                : "Admin email ({$admin_email}) may be a production email. Consider using a dev-specific email.",
            'status'      => $is_dev_email ? 'pass' : 'warning',
            'fixable'     => false,
            'link'        => !$is_dev_email ? admin_url('options-general.php') : '',
            'link_label'  => 'General Settings',
            'category'    => 'configuration',
        ];
    }

    private function check_analytics_scripts(): array {
        $response = wp_remote_get(home_url('/'), [
            'timeout'   => 10,
            'sslverify' => false,
        ]);

        $tracking_found = [];
        if (!is_wp_error($response)) {
            $body = wp_remote_retrieve_body($response);
            $trackers = [
                'Google Analytics'  => ['google-analytics.com', 'googletagmanager.com', 'gtag('],
                'Facebook Pixel'    => ['connect.facebook.net', 'fbevents.js', 'fbq('],
                'Hotjar'            => ['hotjar.com', 'hj('],
                'Mixpanel'          => ['mixpanel.com'],
                'Segment'           => ['segment.com/analytics'],
                'Plausible'         => ['plausible.io'],
                'Matomo'            => ['matomo.js', 'piwik.js'],
            ];

            foreach ($trackers as $name => $signatures) {
                foreach ($signatures as $sig) {
                    if (str_contains($body, $sig)) {
                        $tracking_found[] = $name;
                        break;
                    }
                }
            }
        }

        return [
            'id'          => 'analytics_scripts',
            'label'       => 'Analytics & Tracking Scripts',
            'description' => empty($tracking_found)
                ? 'No tracking scripts detected.'
                : 'Tracking scripts found: ' . implode(', ', array_unique($tracking_found)) . '. Should be disabled on staging.',
            'status'      => empty($tracking_found) ? 'pass' : 'fail',
            'fixable'     => false,
            'category'    => 'indexing',
        ];
    }

    private function check_sitemap(): array {
        // If CGDevTools disabled sitemaps, it's already handled
        if (get_option('cgdevtools_disable_sitemaps')) {
            return [
                'id'          => 'sitemap',
                'label'       => 'Sitemap Generation',
                'description' => 'WordPress core sitemaps are disabled by CGDevTools.',
                'status'      => 'pass',
                'fixable'     => false,
                'category'    => 'indexing',
            ];
        }

        $response = wp_remote_head(home_url('/wp-sitemap.xml'), [
            'timeout'   => 10,
            'sslverify' => false,
        ]);

        $sitemap_active = false;
        if (!is_wp_error($response)) {
            $code = wp_remote_retrieve_response_code($response);
            if ($code === 200) {
                $sitemap_active = true;
            }
        }

        // Also check popular sitemap plugins
        $yoast_sitemap = wp_remote_head(home_url('/sitemap_index.xml'), [
            'timeout'   => 5,
            'sslverify' => false,
        ]);
        if (!is_wp_error($yoast_sitemap) && wp_remote_retrieve_response_code($yoast_sitemap) === 200) {
            $sitemap_active = true;
        }

        return [
            'id'          => 'sitemap',
            'label'       => 'Sitemap Generation',
            'description' => $sitemap_active
                ? 'Sitemap is accessible. Click Fix to disable WordPress core sitemaps.'
                : 'Sitemap is not accessible (good for staging).',
            'status'      => $sitemap_active ? 'fail' : 'pass',
            'fixable'     => $sitemap_active,
            'category'    => 'indexing',
        ];
    }

    private function check_password_protection(): array {
        $settings = get_option('cgdevtools_password_protection', []);
        $enabled = !empty($settings['enabled']);
        $has_password = !empty($settings['password']);

        return [
            'id'          => 'password_protection',
            'label'       => 'Password Protection',
            'description' => $enabled
                ? 'Frontend password protection is active.'
                : ($has_password
                    ? 'Frontend is not password protected. Click Fix to enable it.'
                    : 'Frontend is not password protected. Set a password in Settings first.'),
            'status'      => $enabled ? 'pass' : 'fail',
            'fixable'     => !$enabled && $has_password,
            'link'        => !$enabled ? admin_url('admin.php?page=cgdevtools-settings#password') : '',
            'link_label'  => 'Settings',
            'category'    => 'security',
        ];
    }

    private function check_email_interception(): array {
        $settings = get_option('cgdevtools_email_interception', []);
        $enabled = !empty($settings['enabled']);

        return [
            'id'          => 'email_interception',
            'label'       => 'Email Interception',
            'description' => $enabled
                ? 'Outgoing emails are being intercepted and logged.'
                : 'Emails are NOT being intercepted. Real emails may be sent from staging!',
            'status'      => $enabled ? 'pass' : 'fail',
            'fixable'     => !$enabled,
            'link'        => !$enabled ? admin_url('admin.php?page=cgdevtools-settings#email') : '',
            'link_label'  => 'Settings',
            'category'    => 'security',
        ];
    }

    /**
     * Apply an automatic fix for a given check.
     */
    private function apply_fix(string $check_id): array {
        return match ($check_id) {
            'search_engine_visibility' => $this->fix_search_engine_visibility(),
            'meta_robots'              => $this->fix_meta_robots(),
            'x_robots_header'          => $this->fix_x_robots_header(),
            'robots_txt'               => $this->fix_robots_txt(),
            'sitemap'                  => $this->fix_sitemap(),
            'password_protection'      => $this->fix_password_protection(),
            'email_interception'       => $this->fix_email_interception(),
            default                    => ['success' => false, 'message' => 'No auto-fix available for this check.'],
        };
    }

    private function fix_search_engine_visibility(): array {
        update_option('blog_public', '0');
        return ['success' => true, 'message' => 'Search engine visibility set to "Discourage search engines".'];
    }

    private function fix_meta_robots(): array {
        update_option('cgdevtools_inject_noindex', true);
        return ['success' => true, 'message' => 'CGDevTools will now inject noindex/nofollow meta tags.'];
    }

    private function fix_x_robots_header(): array {
        update_option('cgdevtools_inject_x_robots', true);
        return ['success' => true, 'message' => 'CGDevTools will now send X-Robots-Tag header.'];
    }

    private function fix_robots_txt(): array {
        update_option('cgdevtools_override_robots_txt', true);
        return ['success' => true, 'message' => 'CGDevTools will now override robots.txt to block all crawlers.'];
    }

    private function fix_sitemap(): array {
        update_option('cgdevtools_disable_sitemaps', true);
        return ['success' => true, 'message' => 'WordPress core sitemaps have been disabled.'];
    }

    private function fix_password_protection(): array {
        $settings = get_option('cgdevtools_password_protection', []);
        if (empty($settings['password'])) {
            return ['success' => false, 'message' => 'Cannot enable — no password has been set. Go to Dev Tools > Settings first.'];
        }
        $settings['enabled'] = true;
        update_option('cgdevtools_password_protection', $settings);
        return ['success' => true, 'message' => 'Frontend password protection has been enabled.'];
    }

    private function fix_email_interception(): array {
        update_option('cgdevtools_email_interception', ['enabled' => true]);
        return ['success' => true, 'message' => 'Email interception is now active. All outgoing emails will be logged.'];
    }
}
