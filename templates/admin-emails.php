<?php defined('ABSPATH') || exit;

$page = max(1, intval($_GET['paged'] ?? 1));
$data = CGDevTools_Email_Interceptor::get_emails($page, 20);
$emails = $data['emails'];
$total = $data['total'];
$total_pages = ceil($total / 20);
?>

<div class="wrap cgdevtools-wrap">
    <h1 class="cgdevtools-title">
        <span class="dashicons dashicons-email"></span>
        Dev Tools &mdash; Intercepted Emails
    </h1>

    <p class="description">
        These emails were intercepted and never sent. Total: <strong><?php echo esc_html($total); ?></strong> emails captured.
    </p>

    <?php if ($total > 0) : ?>
        <p>
            <button id="cgdevtools-delete-emails" class="button button-secondary" style="color:#dc2626;">
                <span class="dashicons dashicons-trash" style="vertical-align:middle;"></span> Delete All Emails
            </button>
        </p>
    <?php endif; ?>

    <?php if (empty($emails)) : ?>
        <div class="cgdevtools-empty-state">
            <span class="dashicons dashicons-email" style="font-size:48px;width:48px;height:48px;color:#9ca3af;"></span>
            <h2>No intercepted emails</h2>
            <p>When email interception is enabled, all outgoing emails will appear here instead of being sent.</p>
        </div>
    <?php else : ?>
        <table class="cgdevtools-results-table widefat">
            <thead>
                <tr>
                    <th style="width:50px;">#</th>
                    <th>To</th>
                    <th>Subject</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($emails as $email) : ?>
                    <tr>
                        <td><?php echo esc_html($email['id']); ?></td>
                        <td><code><?php echo esc_html($email['to_email']); ?></code></td>
                        <td><?php echo esc_html($email['subject']); ?></td>
                        <td><?php echo esc_html($email['created_at']); ?></td>
                        <td>
                            <button class="button button-small cgdevtools-view-email"
                                    data-subject="<?php echo esc_attr($email['subject']); ?>"
                                    data-to="<?php echo esc_attr($email['to_email']); ?>"
                                    data-message="<?php echo esc_attr($email['message']); ?>"
                                    data-headers="<?php echo esc_attr($email['headers']); ?>"
                                    data-date="<?php echo esc_attr($email['created_at']); ?>">
                                View
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($total_pages > 1) : ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <?php
                    echo paginate_links([
                        'base'    => add_query_arg('paged', '%#%'),
                        'format'  => '',
                        'current' => $page,
                        'total'   => $total_pages,
                    ]);
                    ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Email View Modal -->
    <div id="cgdevtools-email-modal" class="cgdevtools-modal" style="display:none;">
        <div class="cgdevtools-modal-overlay"></div>
        <div class="cgdevtools-modal-content">
            <div class="cgdevtools-modal-header">
                <h2 id="cgdevtools-email-subject">Email Subject</h2>
                <button class="cgdevtools-modal-close">&times;</button>
            </div>
            <div class="cgdevtools-modal-body">
                <p><strong>To:</strong> <span id="cgdevtools-email-to"></span></p>
                <p><strong>Date:</strong> <span id="cgdevtools-email-date"></span></p>
                <p><strong>Headers:</strong></p>
                <pre id="cgdevtools-email-headers" style="background:#f1f5f9;padding:8px;border-radius:4px;font-size:12px;overflow:auto;max-height:80px;"></pre>
                <p><strong>Message:</strong></p>
                <div id="cgdevtools-email-message" style="background:#f1f5f9;padding:12px;border-radius:4px;max-height:300px;overflow:auto;"></div>
            </div>
        </div>
    </div>
</div>
