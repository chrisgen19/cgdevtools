<?php defined('ABSPATH') || exit; ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <title><?php echo esc_html(get_bloginfo('name')); ?> &mdash; Access Required</title>
    <link rel="stylesheet" href="<?php echo esc_url(CGDEVTOOLS_PLUGIN_URL . 'assets/css/lock-screen.css?v=' . CGDEVTOOLS_VERSION); ?>">
    <style>
        :root {
            --cgdt-bg: <?php echo esc_attr($bg_color); ?>;
            --cgdt-btn: <?php echo esc_attr($btn_color); ?>;
            --cgdt-text: <?php echo esc_attr($text_color); ?>;
        }
    </style>
</head>
<body class="cgdevtools-lock-body">
    <div class="cgdevtools-lock-container">
        <div class="cgdevtools-lock-card">
            <div class="cgdevtools-lock-logo">
                <?php if (!empty($logo_url)) : ?>
                    <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>">
                <?php else : ?>
                    <div class="cgdevtools-lock-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="48" height="48">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                    </div>
                <?php endif; ?>
            </div>

            <h1 class="cgdevtools-lock-title">Access Required</h1>
            <p class="cgdevtools-lock-subtitle">This site is protected. Enter the password to continue.</p>

            <form id="cgdevtools-lock-form" class="cgdevtools-lock-form">
                <div class="cgdevtools-lock-field">
                    <input type="password" name="password" id="cgdevtools-lock-password"
                           placeholder="Enter password" autocomplete="off" required>
                </div>
                <div class="cgdevtools-lock-error" id="cgdevtools-lock-error" style="display:none;"></div>
                <button type="submit" class="cgdevtools-lock-btn" id="cgdevtools-lock-submit">
                    <span class="cgdevtools-lock-btn-text">Unlock</span>
                    <span class="cgdevtools-lock-btn-loading" style="display:none;">
                        <svg width="20" height="20" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" stroke-dasharray="31.4 31.4" stroke-linecap="round">
                                <animateTransform attributeName="transform" type="rotate" dur="1s" from="0 12 12" to="360 12 12" repeatCount="indefinite"/>
                            </circle>
                        </svg>
                    </span>
                </button>
            </form>

            <p class="cgdevtools-lock-footer">
                <a href="<?php echo esc_url(wp_login_url()); ?>">Admin Login</a>
            </p>
        </div>
    </div>

    <script>
    (function() {
        var form = document.getElementById('cgdevtools-lock-form');
        var errorEl = document.getElementById('cgdevtools-lock-error');
        var btnText = document.querySelector('.cgdevtools-lock-btn-text');
        var btnLoad = document.querySelector('.cgdevtools-lock-btn-loading');
        var btn = document.getElementById('cgdevtools-lock-submit');

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            errorEl.style.display = 'none';
            btnText.style.display = 'none';
            btnLoad.style.display = 'inline-flex';
            btn.disabled = true;

            var data = new FormData();
            data.append('action', 'cgdevtools_login');
            data.append('nonce', '<?php echo esc_js($nonce); ?>');
            data.append('password', document.getElementById('cgdevtools-lock-password').value);

            fetch('<?php echo esc_url($ajax_url); ?>', { method: 'POST', body: data })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (res.success) {
                        window.location.href = res.data.redirect;
                    } else {
                        errorEl.textContent = res.data || 'Incorrect password.';
                        errorEl.style.display = 'block';
                        btnText.style.display = 'inline';
                        btnLoad.style.display = 'none';
                        btn.disabled = false;
                    }
                })
                .catch(function() {
                    errorEl.textContent = 'An error occurred. Please try again.';
                    errorEl.style.display = 'block';
                    btnText.style.display = 'inline';
                    btnLoad.style.display = 'none';
                    btn.disabled = false;
                });
        });
    })();
    </script>
</body>
</html>
