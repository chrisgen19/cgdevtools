<?php

defined('ABSPATH') || exit;

class CGDevTools_Email_Interceptor {

    public function __construct() {
        $settings = get_option('cgdevtools_email_interception', []);

        if (!empty($settings['enabled'])) {
            add_filter('pre_wp_mail', [$this, 'intercept_email'], 10, 2);
        }
    }

    /**
     * Intercept all outgoing emails and log them instead of sending.
     *
     * @param null|bool $return Short-circuit return value.
     * @param array     $atts   Email attributes.
     * @return bool True to short-circuit wp_mail().
     */
    public function intercept_email(null|bool $return, array $atts): bool {
        global $wpdb;
        $table = $wpdb->prefix . 'cgdevtools_emails';

        $to = is_array($atts['to']) ? implode(', ', $atts['to']) : ($atts['to'] ?? '');
        $headers = is_array($atts['headers'] ?? null) ? implode("\n", $atts['headers']) : ($atts['headers'] ?? '');

        $wpdb->insert($table, [
            'to_email' => sanitize_text_field($to),
            'subject'  => sanitize_text_field($atts['subject'] ?? '(no subject)'),
            'message'  => wp_kses_post($atts['message'] ?? ''),
            'headers'  => sanitize_textarea_field($headers),
        ], ['%s', '%s', '%s', '%s']);

        return true;
    }

    /**
     * Get intercepted emails with pagination.
     *
     * @return array{emails: array, total: int}
     */
    public static function get_emails(int $page = 1, int $per_page = 20): array {
        global $wpdb;
        $table = $wpdb->prefix . 'cgdevtools_emails';

        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        $offset = ($page - 1) * $per_page;

        $emails = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $per_page,
                $offset
            ),
            ARRAY_A
        );

        return [
            'emails' => $emails ?: [],
            'total'  => $total,
        ];
    }
}
