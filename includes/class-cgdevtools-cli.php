<?php

defined('ABSPATH') || exit;

/**
 * WP-CLI commands for CGDevTools.
 *
 * Usage:
 *   wp cgdevtools scan
 *   wp cgdevtools status
 */
class CGDevTools_CLI {

    /**
     * Run the staging readiness scan.
     *
     * ## EXAMPLES
     *     wp cgdevtools scan
     *
     * @when after_wp_load
     */
    public function scan(array $args, array $assoc_args): void {
        WP_CLI::log('Running CGDevTools scan...');
        WP_CLI::log('');

        $scanner = new CGDevTools_Scanner();
        $results = $scanner->run_scan();

        update_option('cgdevtools_last_scan', [
            'results'   => $results,
            'timestamp' => current_time('mysql'),
        ]);

        $pass = 0;
        $fail = 0;
        $warn = 0;

        foreach ($results as $result) {
            $icon = match ($result['status']) {
                'pass'    => WP_CLI::colorize('%G[PASS]%n'),
                'fail'    => WP_CLI::colorize('%R[FAIL]%n'),
                'warning' => WP_CLI::colorize('%Y[WARN]%n'),
                default   => '[????]',
            };

            WP_CLI::log(sprintf('  %s  %s', $icon, $result['label']));
            WP_CLI::log(sprintf('         %s', $result['description']));
            WP_CLI::log('');

            match ($result['status']) {
                'pass'    => $pass++,
                'fail'    => $fail++,
                'warning' => $warn++,
                default   => null,
            };
        }

        WP_CLI::log('---');
        WP_CLI::log(sprintf(
            'Results: %s passed, %s failed, %s warnings',
            WP_CLI::colorize("%G{$pass}%n"),
            WP_CLI::colorize("%R{$fail}%n"),
            WP_CLI::colorize("%Y{$warn}%n")
        ));

        if ($fail > 0) {
            WP_CLI::warning('Site has failing checks. Not ready for staging deployment.');
        } else {
            WP_CLI::success('All critical checks passed!');
        }
    }

    /**
     * Run the production readiness scan.
     *
     * ## EXAMPLES
     *     wp cgdevtools production
     *
     * @when after_wp_load
     */
    public function production(array $args, array $assoc_args): void {
        WP_CLI::log('Running CGDevTools production readiness scan...');
        WP_CLI::log('');

        $scanner = new CGDevTools_Scanner();
        $results = $scanner->run_production_scan();

        update_option('cgdevtools_last_production_scan', [
            'results'   => $results,
            'timestamp' => current_time('mysql'),
        ]);

        $pass = 0;
        $fail = 0;
        $warn = 0;

        foreach ($results as $result) {
            $icon = match ($result['status']) {
                'pass'    => WP_CLI::colorize('%G[PASS]%n'),
                'fail'    => WP_CLI::colorize('%R[FAIL]%n'),
                'warning' => WP_CLI::colorize('%Y[WARN]%n'),
                default   => '[????]',
            };

            WP_CLI::log(sprintf('  %s  %s', $icon, $result['label']));
            WP_CLI::log(sprintf('         %s', $result['description']));
            WP_CLI::log('');

            match ($result['status']) {
                'pass'    => $pass++,
                'fail'    => $fail++,
                'warning' => $warn++,
                default   => null,
            };
        }

        WP_CLI::log('---');
        WP_CLI::log(sprintf(
            'Results: %s passed, %s failed, %s warnings',
            WP_CLI::colorize("%G{$pass}%n"),
            WP_CLI::colorize("%R{$fail}%n"),
            WP_CLI::colorize("%Y{$warn}%n")
        ));

        if ($fail > 0) {
            WP_CLI::warning('Site has failing checks. Not ready for production deployment.');
        } else {
            WP_CLI::success('All critical checks passed. Site is ready for production!');
        }
    }

    /**
     * Show current environment status.
     *
     * ## EXAMPLES
     *     wp cgdevtools status
     *
     * @when after_wp_load
     */
    public function status(array $args, array $assoc_args): void {
        $env = get_option('cgdevtools_environment', []);
        $pp = get_option('cgdevtools_password_protection', []);
        $email = get_option('cgdevtools_email_interception', []);
        $last_scan = get_option('cgdevtools_last_scan', []);

        $items = [
            ['Setting', 'Value'],
            ['Environment Type', strtoupper($env['type'] ?? 'staging')],
            ['Badge Enabled', !empty($env['badge_enabled']) ? 'Yes' : 'No'],
            ['Banner Enabled', !empty($env['banner_enabled']) ? 'Yes' : 'No'],
            ['Production URL', $env['production_url'] ?? '(not set)'],
            ['Password Protection', !empty($pp['enabled']) ? 'Active' : 'Inactive'],
            ['Email Interception', !empty($email['enabled']) ? 'Active' : 'Inactive'],
            ['Last Scan', $last_scan['timestamp'] ?? 'Never'],
        ];

        $table = new \cli\Table();
        $table->setHeaders(array_shift($items));
        $table->setRows($items);
        $table->display();
    }
}
