# CGDevTools

WordPress plugin for managing development and staging environments. Scans your site for staging readiness, protects the frontend with a branded password gate, intercepts outgoing emails, and helps you go live with a production readiness checker.

## Features

### Staging Readiness Scanner
Checks your site against 19 staging best practices:

| Check | Auto-Fixable |
|---|---|
| Search engine visibility (blog_public) | Yes |
| Meta robots noindex/nofollow | Yes |
| Robots.txt blocking all crawlers | Yes |
| X-Robots-Tag HTTP header | Yes |
| WordPress sitemap disabled | Yes |
| Password protection enabled | Yes |
| Email interception enabled | Yes |
| Environment badge & banner enabled | Yes |
| DISALLOW_FILE_EDIT enabled | Yes (edits wp-config.php) |
| WP_DEBUG enabled | No |
| SSL/HTTPS | No |
| Caching plugins detected | No |
| Auto-updates disabled | No |
| Admin email is dev/staging | No |
| Analytics/tracking scripts detected | No |
| PHP version (8.1+ recommended) | No |
| Payment gateway in live mode | No |
| Risky WP Cron events active | No |
| Inactive plugins detected | No |

### Production Ready Scanner
Reverse checklist — verifies all staging restrictions are removed and the site is hardened for production:

| Check | Auto-Fixable |
|---|---|
| Search engines allowed to index | Yes |
| Noindex meta injection removed | Yes |
| X-Robots-Tag injection removed | Yes |
| Robots.txt override removed | Yes |
| Sitemaps re-enabled | Yes |
| Password protection disabled | Yes |
| Email interception disabled | Yes |
| Environment badge & banner disabled | Yes |
| DISALLOW_FILE_EDIT enabled | Yes (edits wp-config.php) |
| WP_DEBUG_DISPLAY disabled | Yes (edits wp-config.php) |
| SSL/HTTPS enforced | No |
| WP_DEBUG disabled | No |
| Caching plugin active | No |
| PHP version (8.1+ required) | No |
| Database table prefix (non-default) | No |
| Default "admin" username removed | No |
| Permalink structure (not Plain) | No |
| Site icon / favicon set | No |
| Pending WordPress/plugin/theme updates | No |
| CGDevTools plugin deactivated | No |

### One-Click Fix All
Apply all available fixes with a single button — for both staging and production modes.

### Password Protection
- Branded lock screen with custom logo, background color, button color, and text color
- Password stored as a hash (using `wp_hash_password`)
- Cookie-based sessions (24-hour expiry)
- Logged-in administrators bypass automatically
- WP AJAX, REST API, cron, and wp-login.php are never blocked

### Email Interception
- Hooks into `pre_wp_mail` to trap all outgoing emails
- Emails are logged to a database table and viewable in wp-admin
- Prevents staging from accidentally emailing real users
- Paginated email viewer with modal preview

### Environment Badge
- Floating corner badge showing LOCAL, DEV, STAGING, or UAT
- Color-coded per environment type
- Visible on both frontend and wp-admin

### Admin Banner
- Persistent colored banner in wp-admin dashboard
- Shows environment type and site name
- Links to the scan dashboard

### Admin Bar Integration
- "Dev Tools" button in the WordPress admin bar (frontend + backend)
- Quick Scan from any page (results in modal or toast)
- Links to Staging Dashboard, Production Ready, and Settings

### PDF Export
- Print-optimized HTML report for both staging and production scans
- Includes score summary, pass/fail/warn counts, and full checklist
- Uses browser "Save as PDF" via print dialog

### Production URL Detection
- Set your production URL in settings
- If the current site URL matches, a warning is displayed in wp-admin
- Links to the Plugins page for quick deactivation

### WP-CLI Support

```bash
# Run staging scan
wp cgdevtools scan

# Run production readiness scan
wp cgdevtools production

# Show current environment status
wp cgdevtools status
```

## Requirements

- PHP 8.2 or higher
- WordPress 6.7 or higher

The plugin displays an admin notice and blocks activation if requirements are not met.

## Installation

1. Upload the `cgdevtools` directory to `/wp-content/plugins/`
2. Activate the plugin through the **Plugins** menu in WordPress
3. Go to **Dev Tools > Settings** to configure your environment

## Configuration

### Settings > Environment
- **Environment Type** — Local, Development, Staging, or UAT (controls badge color and label)
- **Environment Badge** — Toggle the floating corner badge
- **Admin Banner** — Toggle the wp-admin environment banner
- **Production URL** — Your live site URL (triggers a warning if current URL matches)

### Settings > Password Protection
- **Enable/Disable** — Toggle frontend password gate
- **Password** — Set the access password (stored hashed)
- **Logo** — Upload a custom logo via the WordPress media library
- **Colors** — Background, button, and text colors with live preview

### Settings > Email Interception
- **Enable/Disable** — Toggle email interception
- View intercepted emails under **Dev Tools > Emails**

## File Structure

```
cgdevtools/
├── cgdevtools.php
├── includes/
│   ├── class-cgdevtools.php
│   ├── class-cgdevtools-scanner.php
│   ├── class-cgdevtools-settings.php
│   ├── class-cgdevtools-password-protection.php
│   ├── class-cgdevtools-email-interceptor.php
│   ├── class-cgdevtools-environment-badge.php
│   ├── class-cgdevtools-admin-bar.php
│   ├── class-cgdevtools-admin-notice.php
│   ├── class-cgdevtools-pdf-export.php
│   └── class-cgdevtools-cli.php
├── assets/
│   ├── css/
│   │   ├── admin.css
│   │   ├── badge.css
│   │   └── lock-screen.css
│   └── js/
│       └── admin.js
└── templates/
    ├── admin-dashboard.php
    ├── admin-settings.php
    ├── admin-emails.php
    ├── lock-screen.php
    ├── pdf-report.php
    └── partials/
        └── scan-table.php
```

## Database

The plugin creates one custom table on activation:

- `{prefix}_cgdevtools_emails` — Stores intercepted outgoing emails

Options used:

| Option Key | Purpose |
|---|---|
| `cgdevtools_password_protection` | Password gate settings |
| `cgdevtools_environment` | Environment type, badge, banner, production URL |
| `cgdevtools_email_interception` | Email interception toggle |
| `cgdevtools_last_scan` | Last staging scan results |
| `cgdevtools_last_production_scan` | Last production scan results |
| `cgdevtools_inject_noindex` | Flag: inject noindex meta tags |
| `cgdevtools_inject_x_robots` | Flag: send X-Robots-Tag header |
| `cgdevtools_override_robots_txt` | Flag: override robots.txt |
| `cgdevtools_disable_sitemaps` | Flag: disable WP core sitemaps |
| `cgdevtools_db_version` | Database schema version |

## Workflow

### Setting up staging
1. Activate CGDevTools
2. Go to **Dev Tools > Settings** and set environment type, production URL, and enable badge/banner
3. Enable password protection (set a password first, then enable)
4. Enable email interception
5. Run a **Staging Scan** from the dashboard — fix any failing checks

### Going to production
1. Go to **Dev Tools > Production Ready**
2. Run a **Production Scan**
3. Click **Fix All Issues** to remove all staging restrictions at once
4. Deactivate and remove CGDevTools

## License

GPL v2 or later
