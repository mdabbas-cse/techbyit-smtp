=== TechByIt SMTP ===
Tags: smtp, email, mailer, notifications
Requires at least: 6.2
Requires PHP: 7.4
Tested up to: 7.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Send WordPress email through your SMTP server, configure a default sender, and check your connection with a test email.

== Description ==

TechByIt SMTP sends WordPress site email through a configured SMTP server. It works with messages sent using WordPress's `wp_mail()` function, including notifications from plugins that use that function.

From the TechByIt SMTP admin menu, you can configure your server, choose the active provider, set a default sender, and send a test email.

= Features =

* Custom SMTP configuration with host, port, encryption, authentication, and connection timeout settings.
* Default From name and email address settings.
* A test email form with success and error feedback.
* Encrypted storage for provider passwords and keys.
* Settings restricted to WordPress users with permission to manage site options.

= Available providers =

**Custom SMTP** is the only provider currently available for sending.

Configuration forms are also included for Gmail, Google Workspace, Microsoft 365, Amazon SES, SendGrid, Mailgun, Brevo, and Postmark. Their sending transports and OAuth connections are not available yet.

Message logging, dashboard statistics, retry, and resend are planned features. The Mail Logs page is currently a placeholder.

## Installation guide

### Requirements

- WordPress 6.2 or newer.
- PHP 7.4 or newer with the JSON and OpenSSL extensions enabled.
- A WordPress account with permission to install and activate plugins and manage site settings.
- Access to an SMTP server, including its host, port, encryption mode, and authentication details.

### Install the ZIP through WordPress

1. Download the built TechByIt SMTP ZIP, named `techbyit-smtp-<version>.zip`.
2. Sign in to your WordPress admin dashboard.
3. Open **Plugins → Add New → Upload Plugin**.
4. Select the ZIP file and click **Install Now**. Upload the ZIP without extracting it.
5. Once installation finishes, click **Activate Plugin**.
6. Open **TechByIt SMTP** in the WordPress admin menu and follow the configuration steps below.

The built ZIP includes the required PHP autoloader and compiled admin assets. Composer, Node.js, and pnpm are only needed when building from source.

### Install manually with SFTP or a hosting file manager

1. Extract the built ZIP on your computer.
2. Upload its `techbyit-smtp` folder to your site's `wp-content/plugins/` directory.
3. Check that the plugin entry file is at `wp-content/plugins/techbyit-smtp/techbyit-smtp.php`. Avoid placing the plugin inside a second nested `techbyit-smtp` folder.
4. Keep the package contents together, including `src/`, `vendor/`, `dist/`, and `licenses/`.
5. In WordPress, open **Plugins → Installed Plugins** and activate **TechByIt SMTP**.

### Build and install from source

Install Composer 2, Node.js 22, and pnpm 9.15.0, matching the project's development setup. From the source folder containing `composer.json` and `package.json`, run:

```bash
composer install --no-dev --optimize-autoloader
pnpm install --frozen-lockfile
pnpm build
```

Composer creates `vendor/autoload.php`; the frontend build creates `dist/admin.js` and `dist/admin.css`. Upload the built plugin as a folder named `techbyit-smtp` using the manual installation steps above. Include `techbyit-smtp.php`, `composer.json`, `src/`, `vendor/`, `dist/`, `licenses/`, `LICENSE`, and `readme.txt`. The deployed plugin does not need `node_modules/` or the test files.

### Configure SMTP and send a test email

1. Open **TechByIt SMTP → Providers** and select **Custom SMTP**. It is currently the only provider with an implemented sending transport.
2. Enter the **SMTP Host**, **SMTP Port**, and **Encryption** mode supplied by your email service. Enable **Use authentication** and enter the **Username** and **Password** if the service requires them.
3. Click **Save Settings**, then **Set as Active Provider**. Saving the settings alone does not activate the provider.
4. Open **TechByIt SMTP → Settings**, set the default **From Name** and **From Email**, and save. Use a sender address your SMTP service permits.
5. Open **TechByIt SMTP → Dashboard**, enter a recipient email address you control, and click **Send Test Email**.
6. Confirm the success message, then check the recipient's inbox and spam folder. A successful send means the SMTP server accepted the message; it does not guarantee inbox delivery.

### Installation troubleshooting

- **Missing Composer dependencies:** confirm that `vendor/autoload.php` was uploaded. For a source installation, run the Composer command above before uploading.
- **Missing admin assets:** confirm that `dist/admin.js` and `dist/admin.css` were uploaded. Run `pnpm build` if they are missing.
- **No TechByIt SMTP menu:** confirm that the plugin is active and your WordPress account has permission to manage site settings.
- **No active provider warning:** save the Custom SMTP configuration and click **Set as Active Provider**.
- **Test email fails:** check the returned error and verify the host, port, encryption, credentials, and allowed sender address with your SMTP service.

== Frequently Asked Questions ==

= Do I need an SMTP service? =

Yes. To send through Custom SMTP, you need access to an SMTP server and its connection details. TechByIt SMTP does not provide an email account or SMTP service.

= What happens when no provider is active? =

WordPress keeps its usual mail behavior. TechByIt SMTP starts routing mail through Custom SMTP after you configure and activate that provider.

= Can I connect Gmail, Microsoft 365, or an API provider? =

Their dedicated configuration forms can save settings, but their sending transports are not implemented. Saving OAuth client credentials does not connect an account. Use Custom SMTP with a service that supplies compatible SMTP connection details.

= Does a successful test guarantee inbox delivery? =

No. Success means the SMTP server accepted the message. Check the recipient's inbox and spam folder to confirm where the message arrived.

= Can I view email logs or resend messages? =

These features are not available in this version. The Mail Logs page is a placeholder.

= Why are saved passwords not shown in the form? =

Saved secrets are not displayed again. Leaving a saved password field blank keeps the existing value. Enter a new value to replace it. If your site's WordPress salts change, enter the credentials again.

= Where are the development instructions? =

Install development dependencies with `composer install` and `pnpm install --frozen-lockfile`. Run `composer test`, `composer lint:php`, `pnpm test`, `pnpm typecheck`, `pnpm lint`, and `pnpm build` to check the project.

== Changelog ==

= 1.0.0 =

* Included the Composer manifest and public source link in the release documentation.

* Renamed the plugin to TechByIt SMTP and updated its public slug and text domain.
* Kept existing site settings and integration hooks compatible.

* Added Custom SMTP sending through WordPress mail hooks.
* Added provider configuration forms and encrypted credential storage.
* Added default sender settings and an admin test email form.
* Added the plugin admin interface and a database schema for future mail logging.

## Development

The React and TypeScript source is in `admin/src/`; PHP source is in `src/`. Build configuration is in `vite.config.ts`, and dependency versions are locked in `pnpm-lock.yaml` and `composer.lock`.

Install dependencies with `composer install` and `pnpm install --frozen-lockfile`. Run `pnpm build` to produce `dist/admin.js` and `dist/admin.css`. The distributable plugin must include `vendor/` and `dist/`.

Source repository: [GitHub](https://github.com/mdabbas-cse/techbyit-smtp).

Database option names, the log table, encryption context, and integration hook names retain their original identifiers so existing site data and hook integrations continue to work. PHP classes now use the `TechByIt\SMTP` namespace, and plugin constants use the `TECHBYIT_SMTP_` prefix. Code that directly references the former PHP namespace or constants must update those references. The primary `techbyit-smtp/v1` namespace and both earlier REST namespaces are available; new UI requests use `techbyit-smtp/v1`.

The compiled React bundle contains React, React DOM, and Scheduler under the MIT license; their shared license text is in `licenses/react-mit.txt`.
