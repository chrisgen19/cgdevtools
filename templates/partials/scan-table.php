<?php defined('ABSPATH') || exit;
$fix_class = $scan_fix_class ?? 'cgdevtools-quick-fix';
?>
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
        <?php foreach ($staging_results as $result) : ?>
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
                        <button class="button button-small <?php echo esc_attr($fix_class); ?>" data-check="<?php echo esc_attr($result['id']); ?>">
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
