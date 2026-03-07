/**
 * CGDevTools Admin JavaScript
 */
(function ($) {
    'use strict';

    if (typeof cgdevtools === 'undefined') {
        return;
    }

    // Toast notification
    function showToast(message, type) {
        type = type || 'success';
        var toast = $('<div class="cgdevtools-toast ' + type + '">' + message + '</div>');
        $('body').append(toast);
        setTimeout(function () {
            toast.fadeOut(300, function () { toast.remove(); });
        }, 3000);
    }

    // Render scan results into the dashboard
    function renderResults(results) {
        var pass = 0, fail = 0, warn = 0;
        var rows = '';

        $.each(results, function (i, r) {
            if (r.status === 'pass') pass++;
            else if (r.status === 'fail') fail++;
            else if (r.status === 'warning') warn++;

            var statusLabel = r.status === 'warning' ? 'WARN' : r.status.toUpperCase();
            var action = '';
            if (r.status !== 'pass' && r.fixable) {
                action += '<button class="button button-small cgdevtools-quick-fix" data-check="' + r.id + '">Fix</button> ';
            }
            if (r.status !== 'pass' && r.link) {
                action += '<a href="' + r.link + '" class="button button-small">' + (r.link_label || 'Settings') + '</a>';
            }
            if (r.status === 'pass') {
                action = '<span class="dashicons dashicons-yes-alt" style="color:#16a34a;"></span>';
            } else if (!action) {
                action = '&mdash;';
            }

            rows += '<tr class="cgdevtools-row-' + r.status + '">'
                + '<td><span class="cgdevtools-status cgdevtools-status-' + r.status + '">' + statusLabel + '</span></td>'
                + '<td><strong>' + r.label + '</strong></td>'
                + '<td>' + r.description + '</td>'
                + '<td><span class="cgdevtools-category">' + r.category.charAt(0).toUpperCase() + r.category.slice(1) + '</span></td>'
                + '<td>' + action + '</td>'
                + '</tr>';
        });

        var total = results.length;
        var score = total > 0 ? Math.round((pass / total) * 100) : 0;

        // Update score cards
        var $cards = $('#cgdevtools-score-cards');
        $cards.show();
        $cards.find('#cgdevtools-pass-count').text(pass);
        $cards.find('#cgdevtools-fail-count').text(fail);
        $cards.find('#cgdevtools-warn-count').text(warn);
        $cards.find('.cgdevtools-score-number').text(score + '%');
        $cards.find('.cgdevtools-score-circle').css('--score', score);

        // Update table
        var table = '<table class="cgdevtools-results-table widefat">'
            + '<thead><tr><th>Status</th><th>Check</th><th>Details</th><th>Category</th><th>Action</th></tr></thead>'
            + '<tbody>' + rows + '</tbody></table>';

        $('#cgdevtools-results').html(table);
        $('#cgdevtools-fix-all').prop('disabled', fail === 0);
    }

    // Render results in modal (for quick scan from admin bar)
    function renderModalResults(results) {
        var html = '<div style="margin-bottom:12px;">';
        var pass = 0, fail = 0, warn = 0;

        $.each(results, function (i, r) {
            if (r.status === 'pass') pass++;
            else if (r.status === 'fail') fail++;
            else if (r.status === 'warning') warn++;

            var statusLabel = r.status === 'warning' ? 'WARN' : r.status.toUpperCase();
            html += '<div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid #f1f5f9;">'
                + '<span class="cgdevtools-status cgdevtools-status-' + r.status + '">' + statusLabel + '</span>'
                + '<div><strong>' + r.label + '</strong><br><small style="color:#64748b;">' + r.description + '</small></div>'
                + '</div>';
        });

        var total = results.length;
        var score = total > 0 ? Math.round((pass / total) * 100) : 0;

        html = '<div style="display:flex;gap:16px;margin-bottom:16px;text-align:center;">'
            + '<div style="flex:1;padding:12px;background:#f0fdf4;border-radius:8px;"><strong style="color:#16a34a;font-size:20px;">' + pass + '</strong><br><small>Pass</small></div>'
            + '<div style="flex:1;padding:12px;background:#fef2f2;border-radius:8px;"><strong style="color:#dc2626;font-size:20px;">' + fail + '</strong><br><small>Fail</small></div>'
            + '<div style="flex:1;padding:12px;background:#fffbeb;border-radius:8px;"><strong style="color:#d97706;font-size:20px;">' + warn + '</strong><br><small>Warn</small></div>'
            + '<div style="flex:1;padding:12px;background:#f8fafc;border-radius:8px;"><strong style="font-size:20px;">' + score + '%</strong><br><small>Score</small></div>'
            + '</div>' + html;

        html += '</div>';
        $('#cgdevtools-modal-body').html(html);
        $('#cgdevtools-modal').show();
    }

    // Run Scan
    $(document).on('click', '#cgdevtools-run-scan', function () {
        var $btn = $(this);
        $btn.prop('disabled', true).find('.dashicons').removeClass('dashicons-search').addClass('dashicons-update spin');

        $('#cgdevtools-results').html(
            '<div class="cgdevtools-scanning">'
            + '<div class="cgdevtools-spinner"></div>'
            + '<p>' + cgdevtools.strings.scanning + '</p>'
            + '</div>'
        );

        $.post(cgdevtools.ajax_url, {
            action: 'cgdevtools_run_scan',
            nonce: cgdevtools.nonce
        }, function (res) {
            $btn.prop('disabled', false).find('.dashicons').removeClass('dashicons-update spin').addClass('dashicons-search');
            if (res.success) {
                renderResults(res.data);
                showToast('Scan completed!');
            } else {
                showToast(cgdevtools.strings.error, 'error');
            }
        }).fail(function () {
            $btn.prop('disabled', false).find('.dashicons').removeClass('dashicons-update spin').addClass('dashicons-search');
            showToast(cgdevtools.strings.error, 'error');
        });
    });

    // Fix All
    $(document).on('click', '#cgdevtools-fix-all', function () {
        if (!confirm(cgdevtools.strings.confirm_fix)) return;

        var $btn = $(this);
        $btn.prop('disabled', true).text(cgdevtools.strings.fixing);

        $.post(cgdevtools.ajax_url, {
            action: 'cgdevtools_fix_all',
            nonce: cgdevtools.nonce
        }, function (res) {
            $btn.prop('disabled', false).html('<span class="dashicons dashicons-admin-generic"></span> Fix All Issues');
            if (res.success) {
                renderResults(res.data.results);
                if (res.data.production_results) {
                    renderProductionResults(res.data.production_results);
                }
                showToast('All available fixes applied!');
            } else {
                showToast(cgdevtools.strings.error, 'error');
            }
        });
    });

    // Quick Fix single item
    $(document).on('click', '.cgdevtools-quick-fix', function () {
        var $btn = $(this);
        var checkId = $btn.data('check');
        $btn.prop('disabled', true).text('Fixing...');

        $.post(cgdevtools.ajax_url, {
            action: 'cgdevtools_quick_fix',
            nonce: cgdevtools.nonce,
            check_id: checkId
        }, function (res) {
            if (res.success && res.data.success) {
                showToast(res.data.message);
                // Re-run both scans to update
                $('#cgdevtools-run-scan').trigger('click');
                $('#cgdevtools-run-production-scan').trigger('click');
            } else {
                $btn.prop('disabled', false).text('Fix');
                showToast(res.data?.message || cgdevtools.strings.error, 'error');
            }
        });
    });

    // Settings Tabs
    $(document).on('click', '.cgdevtools-tab', function () {
        var tab = $(this).data('tab');
        $('.cgdevtools-tab').removeClass('active');
        $(this).addClass('active');
        $('.cgdevtools-tab-content').removeClass('active');
        $('#tab-' + tab).addClass('active');
    });

    // Save Settings
    $(document).on('click', '.cgdevtools-save-settings', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var section = $btn.data('section');
        var $form = $btn.closest('form');
        var formData = $form.serializeArray();

        var data = { action: 'cgdevtools_save_settings', nonce: cgdevtools.nonce, section: section };
        $.each(formData, function (i, field) {
            data[field.name] = field.value;
        });

        // Handle unchecked checkboxes
        $form.find('input[type="checkbox"]').each(function () {
            if (!$(this).is(':checked')) {
                data[$(this).attr('name')] = '';
            }
        });

        $btn.prop('disabled', true).text('Saving...');

        $.post(cgdevtools.ajax_url, data, function (res) {
            $btn.prop('disabled', false).text($btn.text().replace('Saving...', ''));
            // Restore button text
            if (section === 'password_protection') $btn.text('Save Password Settings');
            else if (section === 'environment') $btn.text('Save Environment Settings');
            else if (section === 'email_interception') $btn.text('Save Email Settings');

            if (res.success) {
                showToast(res.data.message || cgdevtools.strings.saved);
            } else {
                showToast(res.data || cgdevtools.strings.error, 'error');
            }
        });
    });

    // Upload Logo via WP Media
    $(document).on('click', '#cgdevtools-upload-logo', function (e) {
        e.preventDefault();
        var frame = wp.media({
            title: 'Select Logo',
            button: { text: 'Use This Logo' },
            multiple: false,
            library: { type: 'image' }
        });

        frame.on('select', function () {
            var attachment = frame.state().get('selection').first().toJSON();
            $('#pp-logo').val(attachment.url);
            $('#cgdevtools-logo-preview').html('<img src="' + attachment.url + '" alt="Logo" style="max-width:180px;max-height:80px;">');
        });

        frame.open();
    });

    // Close modals
    $(document).on('click', '.cgdevtools-modal-close, .cgdevtools-modal-overlay', function () {
        $(this).closest('.cgdevtools-modal').hide();
    });

    // View email in modal
    $(document).on('click', '.cgdevtools-view-email', function () {
        var $btn = $(this);
        $('#cgdevtools-email-subject').text($btn.data('subject'));
        $('#cgdevtools-email-to').text($btn.data('to'));
        $('#cgdevtools-email-date').text($btn.data('date'));
        $('#cgdevtools-email-headers').text($btn.data('headers') || '(none)');
        $('#cgdevtools-email-message').html($btn.data('message'));
        $('#cgdevtools-email-modal').show();
    });

    // Delete all emails
    $(document).on('click', '#cgdevtools-delete-emails', function () {
        if (!confirm('Delete all intercepted emails? This cannot be undone.')) return;

        var $btn = $(this);
        $btn.prop('disabled', true);

        $.post(cgdevtools.ajax_url, {
            action: 'cgdevtools_delete_emails',
            nonce: cgdevtools.nonce
        }, function (res) {
            if (res.success) {
                showToast('All emails deleted.');
                location.reload();
            } else {
                $btn.prop('disabled', false);
                showToast(cgdevtools.strings.error, 'error');
            }
        });
    });

    // =========================================================================
    // Production Scan
    // =========================================================================

    function renderProductionResults(results) {
        var pass = 0, fail = 0, warn = 0;
        var rows = '';

        $.each(results, function (i, r) {
            if (r.status === 'pass') pass++;
            else if (r.status === 'fail') fail++;
            else if (r.status === 'warning') warn++;

            var statusLabel = r.status === 'warning' ? 'WARN' : r.status.toUpperCase();
            var action = '';
            if (r.status !== 'pass' && r.fixable) {
                action += '<button class="button button-small cgdevtools-production-quick-fix" data-check="' + r.id + '">Fix</button> ';
            }
            if (r.status !== 'pass' && r.link) {
                action += '<a href="' + r.link + '" class="button button-small">' + (r.link_label || 'Settings') + '</a>';
            }
            if (r.status === 'pass') {
                action = '<span class="dashicons dashicons-yes-alt" style="color:#16a34a;"></span>';
            } else if (!action) {
                action = '&mdash;';
            }

            rows += '<tr class="cgdevtools-row-' + r.status + '">'
                + '<td><span class="cgdevtools-status cgdevtools-status-' + r.status + '">' + statusLabel + '</span></td>'
                + '<td><strong>' + r.label + '</strong></td>'
                + '<td>' + r.description + '</td>'
                + '<td><span class="cgdevtools-category">' + r.category.charAt(0).toUpperCase() + r.category.slice(1) + '</span></td>'
                + '<td>' + action + '</td>'
                + '</tr>';
        });

        var total = results.length;
        var score = total > 0 ? Math.round((pass / total) * 100) : 0;

        var $cards = $('#cgdevtools-production-score-cards');
        $cards.show();
        $cards.find('#cgdevtools-production-pass-count').text(pass);
        $cards.find('#cgdevtools-production-fail-count').text(fail);
        $cards.find('#cgdevtools-production-warn-count').text(warn);
        $cards.find('.cgdevtools-score-number').text(score + '%');
        $cards.find('.cgdevtools-score-circle').css('--score', score);

        var table = '<table class="cgdevtools-results-table widefat">'
            + '<thead><tr><th>Status</th><th>Check</th><th>Details</th><th>Category</th><th>Action</th></tr></thead>'
            + '<tbody>' + rows + '</tbody></table>';

        $('#cgdevtools-production-results').html(table);
        $('#cgdevtools-production-fix-all').prop('disabled', fail === 0 && warn === 0);
    }

    // Run Production Scan
    $(document).on('click', '#cgdevtools-run-production-scan', function () {
        var $btn = $(this);
        $btn.prop('disabled', true).find('.dashicons').removeClass('dashicons-search').addClass('dashicons-update spin');

        $('#cgdevtools-production-results').html(
            '<div class="cgdevtools-scanning">'
            + '<div class="cgdevtools-spinner"></div>'
            + '<p>' + cgdevtools.strings.scanning + '</p>'
            + '</div>'
        );

        $.post(cgdevtools.ajax_url, {
            action: 'cgdevtools_run_production_scan',
            nonce: cgdevtools.nonce
        }, function (res) {
            $btn.prop('disabled', false).find('.dashicons').removeClass('dashicons-update spin').addClass('dashicons-search');
            if (res.success) {
                renderProductionResults(res.data);
                showToast('Production scan completed!');
            } else {
                showToast(cgdevtools.strings.error, 'error');
            }
        }).fail(function () {
            $btn.prop('disabled', false).find('.dashicons').removeClass('dashicons-update spin').addClass('dashicons-search');
            showToast(cgdevtools.strings.error, 'error');
        });
    });

    // Production Fix All
    $(document).on('click', '#cgdevtools-production-fix-all', function () {
        if (!confirm('Remove all staging restrictions and prepare for production?')) return;

        var $btn = $(this);
        $btn.prop('disabled', true).text(cgdevtools.strings.fixing);

        $.post(cgdevtools.ajax_url, {
            action: 'cgdevtools_production_fix_all',
            nonce: cgdevtools.nonce
        }, function (res) {
            $btn.prop('disabled', false).html('<span class="dashicons dashicons-admin-generic"></span> Fix All Issues');
            if (res.success) {
                renderProductionResults(res.data.results);
                if (res.data.staging_results) {
                    renderResults(res.data.staging_results);
                }
                showToast('All staging restrictions removed!');
            } else {
                showToast(cgdevtools.strings.error, 'error');
            }
        });
    });

    // Production Quick Fix single item
    $(document).on('click', '.cgdevtools-production-quick-fix', function () {
        var $btn = $(this);
        var checkId = $btn.data('check');
        $btn.prop('disabled', true).text('Fixing...');

        $.post(cgdevtools.ajax_url, {
            action: 'cgdevtools_production_quick_fix',
            nonce: cgdevtools.nonce,
            check_id: checkId
        }, function (res) {
            if (res.success && res.data.success) {
                showToast(res.data.message);
                // Re-run both scans to update
                $('#cgdevtools-run-production-scan').trigger('click');
                $('#cgdevtools-run-scan').trigger('click');
            } else {
                $btn.prop('disabled', false).text('Fix');
                showToast(res.data?.message || cgdevtools.strings.error, 'error');
            }
        });
    });

    // Set score circle CSS variable on load + handle hash-based tab switching
    $(function () {
        $('.cgdevtools-score-circle').each(function () {
            var score = $(this).data('score') || 0;
            $(this).css('--score', score);
        });

        // Auto-switch tab based on URL hash (e.g. #password, #email, #environment)
        var hash = window.location.hash.replace('#', '');
        if (hash && $('.cgdevtools-tab[data-tab="' + hash + '"]').length) {
            $('.cgdevtools-tab').removeClass('active');
            $('.cgdevtools-tab-content').removeClass('active');
            $('.cgdevtools-tab[data-tab="' + hash + '"]').addClass('active');
            $('#tab-' + hash).addClass('active');
        }
    });

})(jQuery);

/**
 * Quick Scan from admin bar (global function).
 */
function cgdevtoolsQuickScan() {
    if (typeof cgdevtools === 'undefined') return;

    // If modal exists on page (dashboard), use it
    var $modal = jQuery('#cgdevtools-modal');
    if ($modal.length) {
        jQuery('#cgdevtools-modal-body').html(
            '<div class="cgdevtools-scanning">'
            + '<div class="cgdevtools-spinner"></div>'
            + '<p>' + cgdevtools.strings.scanning + '</p>'
            + '</div>'
        );
        $modal.show();
    }

    jQuery.post(cgdevtools.ajax_url, {
        action: 'cgdevtools_run_scan',
        nonce: cgdevtools.nonce
    }, function (res) {
        if (res.success) {
            if ($modal.length) {
                // Render in modal
                var html = buildQuickScanHTML(res.data);
                jQuery('#cgdevtools-modal-body').html(html);
            } else {
                // Show toast with summary
                var pass = 0, fail = 0;
                jQuery.each(res.data, function (i, r) {
                    if (r.status === 'pass') pass++;
                    else if (r.status === 'fail') fail++;
                });
                var msg = 'Scan: ' + pass + ' passed, ' + fail + ' failed';
                var toast = jQuery('<div class="cgdevtools-toast ' + (fail > 0 ? 'error' : 'success') + '">' + msg + '</div>');
                jQuery('body').append(toast);
                setTimeout(function () { toast.fadeOut(300, function () { toast.remove(); }); }, 4000);
            }
        }
    });
}

function buildQuickScanHTML(results) {
    var pass = 0, fail = 0, warn = 0;
    var items = '';

    jQuery.each(results, function (i, r) {
        if (r.status === 'pass') pass++;
        else if (r.status === 'fail') fail++;
        else if (r.status === 'warning') warn++;

        var statusLabel = r.status === 'warning' ? 'WARN' : r.status.toUpperCase();
        items += '<div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid #f1f5f9;">'
            + '<span class="cgdevtools-status cgdevtools-status-' + r.status + '">' + statusLabel + '</span>'
            + '<div><strong>' + r.label + '</strong><br><small style="color:#64748b;">' + r.description + '</small></div>'
            + '</div>';
    });

    var total = results.length;
    var score = total > 0 ? Math.round((pass / total) * 100) : 0;

    return '<div style="display:flex;gap:16px;margin-bottom:16px;text-align:center;">'
        + '<div style="flex:1;padding:12px;background:#f0fdf4;border-radius:8px;"><strong style="color:#16a34a;font-size:20px;">' + pass + '</strong><br><small>Pass</small></div>'
        + '<div style="flex:1;padding:12px;background:#fef2f2;border-radius:8px;"><strong style="color:#dc2626;font-size:20px;">' + fail + '</strong><br><small>Fail</small></div>'
        + '<div style="flex:1;padding:12px;background:#fffbeb;border-radius:8px;"><strong style="color:#d97706;font-size:20px;">' + warn + '</strong><br><small>Warn</small></div>'
        + '<div style="flex:1;padding:12px;background:#f8fafc;border-radius:8px;"><strong style="font-size:20px;">' + score + '%</strong><br><small>Score</small></div>'
        + '</div>' + items;
}
