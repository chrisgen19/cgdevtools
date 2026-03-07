<?php defined('ABSPATH') || exit;

$pp_settings = get_option('cgdevtools_password_protection', []);
$env_settings = get_option('cgdevtools_environment', []);
$email_settings = get_option('cgdevtools_email_interception', []);
?>

<div class="wrap cgdevtools-wrap">
    <h1 class="cgdevtools-title">
        <span class="dashicons dashicons-admin-generic"></span>
        Dev Tools &mdash; Settings
    </h1>

    <div class="cgdevtools-settings-tabs">
        <button class="cgdevtools-tab active" data-tab="environment">Environment</button>
        <button class="cgdevtools-tab" data-tab="password">Password Protection</button>
        <button class="cgdevtools-tab" data-tab="email">Email Interception</button>
    </div>

    <!-- Environment Settings -->
    <div class="cgdevtools-tab-content active" id="tab-environment">
        <div class="cgdevtools-settings-card">
            <h2>Environment Configuration</h2>
            <form id="cgdevtools-env-form" class="cgdevtools-form">
                <table class="form-table">
                    <tr>
                        <th><label for="env-type">Environment Type</label></th>
                        <td>
                            <select name="type" id="env-type">
                                <option value="local" <?php selected($env_settings['type'] ?? '', 'local'); ?>>Local</option>
                                <option value="dev" <?php selected($env_settings['type'] ?? '', 'dev'); ?>>Development</option>
                                <option value="staging" <?php selected($env_settings['type'] ?? '', 'staging'); ?>>Staging</option>
                                <option value="uat" <?php selected($env_settings['type'] ?? '', 'uat'); ?>>UAT</option>
                            </select>
                            <p class="description">This controls the badge color and label shown on the site.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="env-badge">Environment Badge</label></th>
                        <td>
                            <label>
                                <input type="checkbox" name="badge_enabled" id="env-badge" value="1"
                                       <?php checked(!empty($env_settings['badge_enabled'])); ?>>
                                Show floating environment badge on frontend & admin
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="env-banner">Admin Banner</label></th>
                        <td>
                            <label>
                                <input type="checkbox" name="banner_enabled" id="env-banner" value="1"
                                       <?php checked(!empty($env_settings['banner_enabled'])); ?>>
                                Show environment banner in wp-admin
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="env-production-url">Production URL</label></th>
                        <td>
                            <input type="url" name="production_url" id="env-production-url" class="regular-text"
                                   value="<?php echo esc_attr($env_settings['production_url'] ?? ''); ?>"
                                   placeholder="https://www.example.com">
                            <p class="description">
                                If the current site URL matches this, the plugin will display a warning to deactivate.
                            </p>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <button type="submit" class="button button-primary cgdevtools-save-settings" data-section="environment">
                        Save Environment Settings
                    </button>
                </p>
            </form>
        </div>
    </div>

    <!-- Password Protection -->
    <div class="cgdevtools-tab-content" id="tab-password">
        <div class="cgdevtools-settings-card">
            <h2>Password Protection</h2>
            <p class="description">Require a password to view the frontend. Logged-in administrators bypass this automatically.</p>

            <form id="cgdevtools-pp-form" class="cgdevtools-form">
                <table class="form-table">
                    <tr>
                        <th><label for="pp-enabled">Enable</label></th>
                        <td>
                            <label>
                                <input type="checkbox" name="enabled" id="pp-enabled" value="1"
                                       <?php checked(!empty($pp_settings['enabled'])); ?>>
                                Enable frontend password protection
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="pp-password">Password</label></th>
                        <td>
                            <input type="password" name="password" id="pp-password" class="regular-text"
                                   placeholder="Enter new password (leave blank to keep current)">
                            <?php if (!empty($pp_settings['password_plain_hint'])) : ?>
                                <p class="description">Current password hint: <code><?php echo esc_html($pp_settings['password_plain_hint']); ?></code></p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Lock Screen Branding</th>
                        <td>
                            <div class="cgdevtools-branding-preview" style="background:<?php echo esc_attr($pp_settings['bg_color'] ?? '#1a1a2e'); ?>;padding:24px;border-radius:8px;max-width:320px;">
                                <div id="cgdevtools-logo-preview" style="text-align:center;margin-bottom:16px;">
                                    <?php if (!empty($pp_settings['logo_url'])) : ?>
                                        <img src="<?php echo esc_url($pp_settings['logo_url']); ?>" alt="Logo" style="max-width:180px;max-height:80px;">
                                    <?php else : ?>
                                        <span style="color:<?php echo esc_attr($pp_settings['text_color'] ?? '#fff'); ?>;font-size:18px;font-weight:700;">Your Logo</span>
                                    <?php endif; ?>
                                </div>
                                <div style="background:rgba(255,255,255,0.1);border-radius:6px;padding:8px 12px;margin-bottom:12px;">
                                    <span style="color:rgba(255,255,255,0.5);font-size:13px;">Password field preview</span>
                                </div>
                                <div style="background:<?php echo esc_attr($pp_settings['btn_color'] ?? '#e94560'); ?>;color:#fff;text-align:center;padding:8px;border-radius:6px;font-weight:600;font-size:13px;">
                                    Unlock
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="pp-logo">Logo</label></th>
                        <td>
                            <input type="url" name="logo_url" id="pp-logo" class="regular-text"
                                   value="<?php echo esc_attr($pp_settings['logo_url'] ?? ''); ?>"
                                   placeholder="Logo URL">
                            <button type="button" class="button" id="cgdevtools-upload-logo">Upload Logo</button>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="pp-bg-color">Background Color</label></th>
                        <td>
                            <input type="color" name="bg_color" id="pp-bg-color"
                                   value="<?php echo esc_attr($pp_settings['bg_color'] ?? '#1a1a2e'); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="pp-btn-color">Button Color</label></th>
                        <td>
                            <input type="color" name="btn_color" id="pp-btn-color"
                                   value="<?php echo esc_attr($pp_settings['btn_color'] ?? '#e94560'); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="pp-text-color">Text Color</label></th>
                        <td>
                            <input type="color" name="text_color" id="pp-text-color"
                                   value="<?php echo esc_attr($pp_settings['text_color'] ?? '#ffffff'); ?>">
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <button type="submit" class="button button-primary cgdevtools-save-settings" data-section="password_protection">
                        Save Password Settings
                    </button>
                </p>
            </form>
        </div>
    </div>

    <!-- Email Interception -->
    <div class="cgdevtools-tab-content" id="tab-email">
        <div class="cgdevtools-settings-card">
            <h2>Email Interception</h2>
            <p class="description">When enabled, all outgoing emails are intercepted and logged instead of being sent. This prevents staging from emailing real users.</p>

            <form id="cgdevtools-email-form" class="cgdevtools-form">
                <table class="form-table">
                    <tr>
                        <th><label for="email-enabled">Enable</label></th>
                        <td>
                            <label>
                                <input type="checkbox" name="enabled" id="email-enabled" value="1"
                                       <?php checked(!empty($email_settings['enabled'])); ?>>
                                Intercept all outgoing emails
                            </label>
                            <p class="description">
                                Intercepted emails can be viewed under
                                <a href="<?php echo esc_url(admin_url('admin.php?page=cgdevtools-emails')); ?>">Dev Tools > Emails</a>.
                            </p>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <button type="submit" class="button button-primary cgdevtools-save-settings" data-section="email_interception">
                        Save Email Settings
                    </button>
                </p>
            </form>
        </div>
    </div>
</div>
