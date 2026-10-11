=== TechByIt SMTP – WordPress SMTP & Email Delivery ===
Tags: smtp, wp mail smtp, email, mailer, email delivery
Requires at least: 6.2
Requires PHP: 7.4
Tested up to: 6.7
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Send WordPress emails through your SMTP server, set a default sender, and test your SMTP connection with a test email.

== Description ==

TechByIt SMTP is a lightweight WordPress SMTP plugin that routes your site's outgoing email through a configured SMTP server. It works with any message sent using WordPress's `wp_mail()` function, including contact form submissions, WooCommerce order emails, password reset emails, and notifications from other plugins.

If your WordPress emails are not sending, landing in spam, or being rejected by recipients, an SMTP plugin is the standard fix. TechByIt SMTP replaces the default PHP `mail()` function with authenticated SMTP delivery, which improves email deliverability and gives you control over how your site sends mail.

From the **TechByIt SMTP** admin menu you can configure your SMTP server, choose the active provider, set a default sender name and address, send a test email to confirm everything works, and review a log of the messages the plugin sends.

= WordPress SMTP Plugin Features =

* **Custom SMTP configuration** — host, port, encryption (TLS/SSL), authentication, and connection timeout settings.
* **Default From name and email** — set a consistent sender for all outgoing WordPress email.
* **Test email form** — verify your SMTP connection with success and error feedback.
* **Email logs** — search messages, filter by status, provider, and date, and open the details of each message.
* **Dashboard statistics** — sent and failed counts, success rate, and recent failures for the last 7 or 30 days.
* **Log retention** — logs are deleted automatically after the period you choose. Storing message bodies is optional and off by default.
* **Encrypted credential storage** — provider passwords and API keys are encrypted at rest.
* **Capability-based access** — settings are restricted to WordPress users with permission to manage site options.
* **Works with `wp_mail()`** — compatible with any plugin or theme that sends email through WordPress.

= Available SMTP Providers =

**Custom SMTP** is the only sending provider currently available. Use it with any email service that provides standard SMTP connection details, including:

* Gmail SMTP (with an app password)
* Google Workspace SMTP
* Microsoft 365 / Outlook SMTP
* Amazon SES SMTP
* SendGrid SMTP
* Mailgun SMTP
* Brevo (Sendinblue) SMTP
* Postmark SMTP
* Any other standard SMTP server

Configuration forms are also included for Gmail, Google Workspace, Microsoft 365, Amazon SES, SendGrid, Mailgun, Brevo, and Postmark. Their dedicated sending transports and OAuth connections are not available yet — use Custom SMTP with the same provider's SMTP credentials in the meantime.

Retry and resend of logged messages are planned features.

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

= How do I fix WordPress emails not sending? =

Install TechByIt SMTP, configure your SMTP server under **TechByIt SMTP → Providers**, activate Custom SMTP, and send a test email. Routing WordPress email through an authenticated SMTP server is the most common fix for missing or undelivered WordPress emails.

= Do I need an SMTP service to use this plugin? =

Yes. To send through Custom SMTP you need access to an SMTP server and its connection details. TechByIt SMTP does not provide an email account or SMTP service — it connects your WordPress site to one you already have.

= Which SMTP providers work with TechByIt SMTP? =

Any provider that offers standard SMTP connection details works with Custom SMTP, including Gmail, Google Workspace, Microsoft 365, Amazon SES, SendGrid, Mailgun, Brevo, and Postmark. Enter the host, port, encryption, username, and password from your provider.

= What happens when no SMTP provider is active? =

WordPress keeps its usual mail behavior. TechByIt SMTP starts routing mail through Custom SMTP after you configure and activate that provider.

= Can I connect Gmail, Microsoft 365, or an API provider directly? =

Their dedicated configuration forms can save settings, but their sending transports are not implemented. Saving OAuth client credentials does not connect an account. Use Custom SMTP with a service that supplies compatible SMTP connection details.

= Does a successful test email guarantee inbox delivery? =

No. Success means the SMTP server accepted the message. Check the recipient's inbox and spam folder to confirm where the message arrived.

= Can I view email logs or resend messages? =

Yes, you can view logs. Open **TechByIt SMTP → Mail Logs** to search and filter messages sent through TechByIt SMTP and open their details. Logging is on by default and can be turned off in **TechByIt SMTP → Settings**. Retry and resend are not available yet.

= What do email logs store? =

The sender, recipients, subject, provider, status, a safe error summary, the server message ID, and attachment file names. Message bodies are stored only when **Store message bodies** is on, because they can contain private information such as password reset links. Attachment contents and credentials are never stored. Logs are deleted after the retention period, 30 days by default.

= Why are saved passwords not shown in the form? =

Saved secrets are not displayed again. Leaving a saved password field blank keeps the existing value. Enter a new value to replace it. If your site's WordPress salts change, enter the credentials again.

= Where are the development instructions? =

Install development dependencies with `composer install` and `pnpm install --frozen-lockfile`. Run `composer test`, `composer lint:php`, `pnpm test`, `pnpm typecheck`, `pnpm lint`, and `pnpm build` to check the project.

== Development ==

The React and TypeScript source is in `admin/src/`; PHP source is in `src/`. Build tooling is in `package.json` and `vite.config.ts`. Run `pnpm install --frozen-lockfile` and `pnpm build` to regenerate `dist/`. Install PHP dependencies with `composer install`.

Source code and build instructions: https://github.com/mdabbas-cse/techbyit-smtp

== Changelog ==

= 1.0.1 =

* Expanded the public product documentation with SMTP provider examples and form-plugin compatibility guidance.
* Added clearer installation, security, reliability, and troubleshooting information.
* Clarified that Custom SMTP is the current sending transport and that email logs and dedicated API/OAuth transports remain planned.
* Updated and synchronized the plugin release metadata.

= 1.0.0 =

* Added Custom SMTP sending through WordPress mail hooks.
* Added provider configuration forms and encrypted credential storage.
* Added default sender settings and an admin test email form.
* Added the plugin admin interface and a database schema for future mail logging.
* Included the Composer manifest and public source link in the release documentation.
* Renamed the plugin to TechByIt SMTP and updated its public slug and text domain.
* Kept existing site settings and integration hooks compatible.

== Upgrade Notice ==

= 1.0.1 =

Documentation and release metadata update. No settings changes are required.

= 1.0.0 =

Plugin renamed to TechByIt SMTP. Existing settings and hooks remain compatible. Code that references the former PHP namespace or constants must be updated.