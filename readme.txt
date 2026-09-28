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

== Installation ==

1. Upload the built `techbyit-smtp` directory to `/wp-content/plugins/`. The plugin package must include `composer.json`, `vendor/`, and `dist/`.
2. Activate **TechByIt SMTP** from the WordPress **Plugins** screen.
3. Open **TechByIt SMTP → Providers** and select **Custom SMTP**.
4. Enter the SMTP host, port, and encryption settings supplied by your email service. Enable authentication and enter your username and password if required.
5. Select **Save Settings**, then **Set as Active Provider**.
6. Open **TechByIt SMTP → Settings** to save your default From name and email address, if needed.
7. Open **TechByIt SMTP → Dashboard**, enter a recipient address you control, and select **Send Test Email**.

The plugin requires WordPress 6.2 or newer and PHP 7.4 or newer with JSON and OpenSSL support. If installing from source, run `composer install --no-dev` and `pnpm install --frozen-lockfile && pnpm build` before activation.

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

== Development ==

The React and TypeScript source is in `admin/src/`; PHP source is in `src/`. Build tooling is in `package.json` and `vite.config.ts`. Run `pnpm install --frozen-lockfile` and `pnpm build` to regenerate `dist/`. Install PHP dependencies with `composer install`.

Source code and build instructions: https://github.com/mdabbas-cse/techbyit-smtp

== Changelog ==

= 1.0.0 =

* Included the Composer manifest and public source link in the release documentation.

* Renamed the plugin to TechByIt SMTP and updated its public slug and text domain.
* Kept existing site settings and integration hooks compatible.

* Added Custom SMTP sending through WordPress mail hooks.
* Added provider configuration forms and encrypted credential storage.
* Added default sender settings and an admin test email form.
* Added the plugin admin interface and a database schema for future mail logging.
