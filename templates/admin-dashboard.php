<?php defined('ABSPATH') || exit;

// Staging data
$staging_scan = get_option('cgdevtools_last_scan', []);
$staging_results = $staging_scan['results'] ?? [];
$staging_timestamp = $staging_scan['timestamp'] ?? null;
$s_pass = $s_fail = $s_warn = 0;
foreach ($staging_results as $r) {
    match ($r['status']) {
        'pass' => $s_pass++, 'fail' => $s_fail++, 'warning' => $s_warn++, default => null,
    };
}
$s_total = count($staging_results);
$s_score = $s_total > 0 ? round(($s_pass / $s_total) * 100) : 0;

// Production data
$prod_scan = get_option('cgdevtools_last_production_scan', []);
$prod_results = $prod_scan['results'] ?? [];
$prod_timestamp = $prod_scan['timestamp'] ?? null;
$p_pass = $p_fail = $p_warn = 0;
foreach ($prod_results as $r) {
    match ($r['status']) {
        'pass' => $p_pass++, 'fail' => $p_fail++, 'warning' => $p_warn++, default => null,
    };
}
$p_total = count($prod_results);
$p_score = $p_total > 0 ? round(($p_pass / $p_total) * 100) : 0;

// Active tab
$active_tab = sanitize_text_field($_GET['tab'] ?? 'staging');
if (!in_array($active_tab, ['staging', 'production'], true)) {
    $active_tab = 'staging';
}
?>

<div class="wrap cgdevtools-wrap">
    <h1 class="cgdevtools-title">
        <span class="dashicons dashicons-admin-tools"></span>
        Dev Tools &mdash; Scanner Dashboard
    </h1>

    <!-- Scan Mode Tabs -->
    <div class="cgdevtools-dashboard-tabs">
        <a href="<?php echo esc_url(admin_url('admin.php?page=cgdevtools&tab=staging')); ?>"
           class="cgdevtools-dashboard-tab <?php echo $active_tab === 'staging' ? 'active' : ''; ?>">
            <span class="dashicons dashicons-shield"></span> Staging Readiness
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=cgdevtools&tab=production')); ?>"
           class="cgdevtools-dashboard-tab <?php echo $active_tab === 'production' ? 'active' : ''; ?>">
            <span class="dashicons dashicons-migrate"></span> Production Ready
        </a>
    </div>

    <!-- ================================================================= -->
    <!-- STAGING TAB -->
    <!-- ================================================================= -->
    <div class="cgdevtools-dashboard-panel" id="panel-staging" style="<?php echo $active_tab !== 'staging' ? 'display:none;' : ''; ?>">

        <p class="cgdevtools-panel-desc">
            Checks that your site is properly locked down for staging &mdash; search engines blocked, password protection active, emails intercepted.
        </p>

        <div class="cgdevtools-header-actions">
            <button id="cgdevtools-run-scan" class="button button-primary button-hero">
                <span class="dashicons dashicons-search"></span> Run Scan
            </button>
            <button id="cgdevtools-fix-all" class="button button-secondary button-hero" <?php echo empty($staging_results) ? 'disabled' : ''; ?>>
                <span class="dashicons dashicons-admin-generic"></span> Fix All Issues
            </button>
            <?php if (!empty($staging_results)) : ?>
                <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=cgdevtools_export_pdf'), 'cgdevtools_export_pdf')); ?>"
                   class="button button-secondary button-hero">
                    <span class="dashicons dashicons-media-document"></span> Export PDF
                </a>
            <?php endif; ?>
        </div>

        <?php if ($staging_timestamp) : ?>
            <p class="cgdevtools-last-scan">
                Last scan: <strong><?php echo esc_html($staging_timestamp); ?></strong>
            </p>
        <?php endif; ?>

        <div class="cgdevtools-score-cards" id="cgdevtools-score-cards" style="<?php echo empty($staging_results) ? 'display:none;' : ''; ?>">
            <div class="cgdevtools-card cgdevtools-card-score">
                <div class="cgdevtools-score-circle" data-score="<?php echo esc_attr($s_score); ?>">
                    <span class="cgdevtools-score-number"><?php echo esc_html($s_score); ?>%</span>
                </div>
                <p>Staging Readiness</p>
            </div>
            <div class="cgdevtools-card cgdevtools-card-pass">
                <span class="cgdevtools-card-number" id="cgdevtools-pass-count"><?php echo esc_html($s_pass); ?></span>
                <p>Passed</p>
            </div>
            <div class="cgdevtools-card cgdevtools-card-fail">
                <span class="cgdevtools-card-number" id="cgdevtools-fail-count"><?php echo esc_html($s_fail); ?></span>
                <p>Failed</p>
            </div>
            <div class="cgdevtools-card cgdevtools-card-warn">
                <span class="cgdevtools-card-number" id="cgdevtools-warn-count"><?php echo esc_html($s_warn); ?></span>
                <p>Warnings</p>
            </div>
        </div>

        <div id="cgdevtools-results">
            <?php if (!empty($staging_results)) : ?>
                <?php include CGDEVTOOLS_PLUGIN_DIR . 'templates/partials/scan-table.php'; ?>
            <?php else : ?>
                <div class="cgdevtools-empty-state">
                    <span class="dashicons dashicons-shield" style="font-size:48px;width:48px;height:48px;color:#9ca3af;"></span>
                    <h2>No scan results yet</h2>
                    <p>Click "Run Scan" to check your staging environment readiness.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ================================================================= -->
    <!-- PRODUCTION TAB -->
    <!-- ================================================================= -->
    <div class="cgdevtools-dashboard-panel" id="panel-production" style="<?php echo $active_tab !== 'production' ? 'display:none;' : ''; ?>">

        <p class="cgdevtools-panel-desc">
            Verifies that all staging restrictions have been removed and your site is ready for live production deployment.
        </p>

        <div class="cgdevtools-header-actions">
            <button id="cgdevtools-run-production-scan" class="button button-primary button-hero">
                <span class="dashicons dashicons-search"></span> Run Scan
            </button>
            <button id="cgdevtools-production-fix-all" class="button button-secondary button-hero" <?php echo empty($prod_results) ? 'disabled' : ''; ?>>
                <span class="dashicons dashicons-admin-generic"></span> Fix All Issues
            </button>
            <?php if (!empty($prod_results)) : ?>
                <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=cgdevtools_export_production_pdf'), 'cgdevtools_export_production_pdf')); ?>"
                   class="button button-secondary button-hero">
                    <span class="dashicons dashicons-media-document"></span> Export PDF
                </a>
            <?php endif; ?>
        </div>

        <?php if ($prod_timestamp) : ?>
            <p class="cgdevtools-last-scan">
                Last scan: <strong><?php echo esc_html($prod_timestamp); ?></strong>
            </p>
        <?php endif; ?>

        <div class="cgdevtools-score-cards" id="cgdevtools-production-score-cards" style="<?php echo empty($prod_results) ? 'display:none;' : ''; ?>">
            <div class="cgdevtools-card cgdevtools-card-score">
                <div class="cgdevtools-score-circle" data-score="<?php echo esc_attr($p_score); ?>">
                    <span class="cgdevtools-score-number"><?php echo esc_html($p_score); ?>%</span>
                </div>
                <p>Production Readiness</p>
            </div>
            <div class="cgdevtools-card cgdevtools-card-pass">
                <span class="cgdevtools-card-number" id="cgdevtools-production-pass-count"><?php echo esc_html($p_pass); ?></span>
                <p>Passed</p>
            </div>
            <div class="cgdevtools-card cgdevtools-card-fail">
                <span class="cgdevtools-card-number" id="cgdevtools-production-fail-count"><?php echo esc_html($p_fail); ?></span>
                <p>Failed</p>
            </div>
            <div class="cgdevtools-card cgdevtools-card-warn">
                <span class="cgdevtools-card-number" id="cgdevtools-production-warn-count"><?php echo esc_html($p_warn); ?></span>
                <p>Warnings</p>
            </div>
        </div>

        <div id="cgdevtools-production-results">
            <?php if (!empty($prod_results)) : ?>
                <?php
                $staging_results = $prod_results;
                $scan_fix_class = 'cgdevtools-production-quick-fix';
                include CGDEVTOOLS_PLUGIN_DIR . 'templates/partials/scan-table.php';
                $scan_fix_class = '';
                ?>
            <?php else : ?>
                <div class="cgdevtools-empty-state">
                    <span class="dashicons dashicons-migrate" style="font-size:48px;width:48px;height:48px;color:#9ca3af;"></span>
                    <h2>No production scan results yet</h2>
                    <p>Click "Run Scan" to check if your site is ready for live deployment.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Scan Modal (used from admin bar) -->
    <div id="cgdevtools-modal" class="cgdevtools-modal" style="display:none;">
        <div class="cgdevtools-modal-overlay"></div>
        <div class="cgdevtools-modal-content">
            <div class="cgdevtools-modal-header">
                <h2>Quick Scan Results</h2>
                <button class="cgdevtools-modal-close">&times;</button>
            </div>
            <div class="cgdevtools-modal-body" id="cgdevtools-modal-body"></div>
            <div class="cgdevtools-modal-footer">
                <a href="<?php echo esc_url(admin_url('admin.php?page=cgdevtools')); ?>" class="button button-primary">
                    View Full Dashboard
                </a>
            </div>
        </div>
    </div>
</div>
