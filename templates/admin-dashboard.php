<?php defined('ABSPATH') || exit;

$last_scan = get_option('cgdevtools_last_scan', []);
$results = $last_scan['results'] ?? [];
$timestamp = $last_scan['timestamp'] ?? null;

$pass_count = 0;
$fail_count = 0;
$warn_count = 0;

foreach ($results as $r) {
    match ($r['status']) {
        'pass'    => $pass_count++,
        'fail'    => $fail_count++,
        'warning' => $warn_count++,
        default   => null,
    };
}

$total = count($results);
$score = $total > 0 ? round(($pass_count / $total) * 100) : 0;
?>

<div class="wrap cgdevtools-wrap">
    <h1 class="cgdevtools-title">
        <span class="dashicons dashicons-admin-tools"></span>
        Dev Tools &mdash; Staging Readiness Scanner
    </h1>

    <div class="cgdevtools-header-actions">
        <button id="cgdevtools-run-scan" class="button button-primary button-hero">
            <span class="dashicons dashicons-search"></span> Run Scan
        </button>
        <button id="cgdevtools-fix-all" class="button button-secondary button-hero" <?php echo empty($results) ? 'disabled' : ''; ?>>
            <span class="dashicons dashicons-admin-generic"></span> Fix All Issues
        </button>
        <?php if (!empty($results)) : ?>
            <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=cgdevtools_export_pdf'), 'cgdevtools_export_pdf')); ?>"
               class="button button-secondary button-hero">
                <span class="dashicons dashicons-media-document"></span> Export PDF
            </a>
        <?php endif; ?>
    </div>

    <?php if ($timestamp) : ?>
        <p class="cgdevtools-last-scan">
            Last scan: <strong><?php echo esc_html($timestamp); ?></strong>
        </p>
    <?php endif; ?>

    <!-- Score Card -->
    <div class="cgdevtools-score-cards" id="cgdevtools-score-cards" style="<?php echo empty($results) ? 'display:none;' : ''; ?>">
        <div class="cgdevtools-card cgdevtools-card-score">
            <div class="cgdevtools-score-circle" data-score="<?php echo esc_attr($score); ?>">
                <span class="cgdevtools-score-number"><?php echo esc_html($score); ?>%</span>
            </div>
            <p>Readiness Score</p>
        </div>
        <div class="cgdevtools-card cgdevtools-card-pass">
            <span class="cgdevtools-card-number" id="cgdevtools-pass-count"><?php echo esc_html($pass_count); ?></span>
            <p>Passed</p>
        </div>
        <div class="cgdevtools-card cgdevtools-card-fail">
            <span class="cgdevtools-card-number" id="cgdevtools-fail-count"><?php echo esc_html($fail_count); ?></span>
            <p>Failed</p>
        </div>
        <div class="cgdevtools-card cgdevtools-card-warn">
            <span class="cgdevtools-card-number" id="cgdevtools-warn-count"><?php echo esc_html($warn_count); ?></span>
            <p>Warnings</p>
        </div>
    </div>

    <!-- Results Table -->
    <div id="cgdevtools-results">
        <?php if (!empty($results)) : ?>
            <table class="cgdevtools-results-table widefat">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Check</th>
                        <th>Details</th>
                        <th>Category</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $result) : ?>
                        <tr class="cgdevtools-row-<?php echo esc_attr($result['status']); ?>">
                            <td>
                                <span class="cgdevtools-status cgdevtools-status-<?php echo esc_attr($result['status']); ?>">
                                    <?php echo match ($result['status']) {
                                        'pass'    => 'PASS',
                                        'fail'    => 'FAIL',
                                        'warning' => 'WARN',
                                        default   => '???',
                                    }; ?>
                                </span>
                            </td>
                            <td><strong><?php echo esc_html($result['label']); ?></strong></td>
                            <td><?php echo esc_html($result['description']); ?></td>
                            <td><span class="cgdevtools-category"><?php echo esc_html(ucfirst($result['category'])); ?></span></td>
                            <td>
                                <?php if ($result['status'] !== 'pass' && $result['fixable']) : ?>
                                    <button class="button button-small cgdevtools-quick-fix" data-check="<?php echo esc_attr($result['id']); ?>">
                                        Fix
                                    </button>
                                <?php endif; ?>
                                <?php if ($result['status'] !== 'pass' && !empty($result['link'])) : ?>
                                    <a href="<?php echo esc_url($result['link']); ?>" class="button button-small">
                                        <?php echo esc_html($result['link_label'] ?? 'Settings'); ?>
                                    </a>
                                <?php endif; ?>
                                <?php if ($result['status'] === 'pass') : ?>
                                    <span class="dashicons dashicons-yes-alt" style="color:#16a34a;"></span>
                                <?php elseif (empty($result['fixable']) && empty($result['link'])) : ?>
                                    &mdash;
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <div class="cgdevtools-empty-state">
                <span class="dashicons dashicons-search" style="font-size:48px;width:48px;height:48px;color:#9ca3af;"></span>
                <h2>No scan results yet</h2>
                <p>Click "Run Scan" to check your staging environment readiness.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Quick Scan Modal (used from admin bar) -->
    <div id="cgdevtools-modal" class="cgdevtools-modal" style="display:none;">
        <div class="cgdevtools-modal-overlay"></div>
        <div class="cgdevtools-modal-content">
            <div class="cgdevtools-modal-header">
                <h2>Quick Scan Results</h2>
                <button class="cgdevtools-modal-close">&times;</button>
            </div>
            <div class="cgdevtools-modal-body" id="cgdevtools-modal-body">
                <!-- Populated via JS -->
            </div>
            <div class="cgdevtools-modal-footer">
                <a href="<?php echo esc_url(admin_url('admin.php?page=cgdevtools')); ?>" class="button button-primary">
                    View Full Dashboard
                </a>
            </div>
        </div>
    </div>
</div>
