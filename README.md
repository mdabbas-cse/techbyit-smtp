=== TechByIt SMTP – Custom SMTP Mailer & Email Sender for WordPress ===
Contributors: techbyit
Tags: smtp, wp mail smtp, email, mailer, email delivery
Requires at least: 6.2
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Send WordPress emails through your SMTP server, set a default sender, and test your SMTP connection with a test email.

== Description ==

TechByIt SMTP is a lightweight WordPress SMTP plugin that sends your site's email through a configured SMTP server. It works with any message sent using WordPress's `wp_mail()` function, including contact form submissions, WooCommerce order emails, password reset emails, and notifications from other plugins.

From the TechByIt SMTP admin menu you can configure your SMTP server, choose the active provider, set a default sender, send a test email, and review a log of the messages the plugin sends.

= Is WordPress not sending emails, or are they going to spam? =

The default PHP `mail()` function is often blocked or filtered as spam by receiving mail systems. TechByIt SMTP connects your WordPress site to a real SMTP server so your messages are sent from an authenticated source. Using an SMTP server helps prove your site's emails come from a trusted sender — much like presenting your ID before entering a secure building.

= WordPress SMTP plugin features =

* Custom SMTP configuration with host, port, encryption (TLS/SSL), authentication, and connection timeout settings.
* Default From name and email address settings for consistent sender identity.
* A test email form with success and error feedback.
* Email logs with search, filters by status, provider, and date, and a details view for each message.
* Dashboard statistics for the last 7 or 30 days: sent and failed messages, success rate, and recent failures.
* Automatic log cleanup after a retention period you choose. Storing message bodies is optional and off by default.
* Encrypted storage for provider passwords and API keys.
* Settings restricted to WordPress users with permission to manage site options.
* Quick setup: connect your SMTP server and send a test email within minutes.

= Available SMTP providers =

**Custom SMTP** is the only provider currently available for sending.

Configuration forms are also included for Gmail, Google Workspace, Microsoft 365, Amazon SES, SendGrid, Mailgun, Brevo, and Postmark. Their dedicated sending transports and OAuth connections are not available yet.

Retry and resend of logged messages are planned features.

= Works with popular SMTP services =

Custom SMTP works with any service that gives you standard SMTP connection details (host, port, encryption, and credentials). Typical settings for popular services:

* **Mailgun:** `smtp.mailgun.org`, TLS, port 587
* **Gmail / Google Workspace:** `smtp.gmail.com`, TLS, port 587
* **Outlook / Microsoft 365:** `smtp-mail.outlook.com`, TLS, port 587
* **Yahoo Mail:** `smtp.mail.yahoo.com`, SSL, port 465
* **Zoho Mail:** `smtp.zoho.com`, TLS, port 587
* **Amazon SES:** region-specific host such as `email-smtp.us-west-2.amazonaws.com`, TLS, port 587
* **SendGrid:** `smtp.sendgrid.net`, TLS, port 587
* **Brevo (Sendinblue):** `smtp-relay.sendinblue.com`, TLS, port 587

Always confirm the current host, port, and sender rules with your provider, as these can change. Many providers offer free plans.

= Compatible with top WordPress form plugins =

If you use forms, you want to be sure notifications are delivered every time. TechByIt SMTP routes mail sent through `wp_mail()`, so it is designed to work with form plugins such as:

* [Bit Form](https://wordpress.org/plugins/bit-form/)
* Gravity Forms
* Contact Form 7
* WPForms
* Ninja Forms
* Forminator
* Fluent Forms
* Formidable Forms
* Everest Forms
* WS Form
* Happy Forms
* weForms
* Kali Forms
* PlanSo Forms
* Form Maker by 10Web
* Elementor Form
* FormCraft
* Quform
* Caldera Forms

It also works with any other plugin that relies on WordPress's `wp_mail()` to send email.

= Strong security for your WordPress emails =

TechByIt SMTP follows security best practices. Many providers, such as Gmail, SendGrid, or Mailgun, issue dedicated SMTP credentials or app passwords. Use those instead of typing your main account login into WordPress. Saved passwords and keys are stored encrypted and are never displayed again in the settings form.

= Reliable WordPress email delivery for every user =

Don't let emails go missing or end up in spam. TechByIt SMTP gives you control over your WordPress email delivery: use any SMTP service you trust, set a default sender, and verify the connection with a test email.

= Email logs =

TechByIt SMTP records each message it routes through the active provider. Open **TechByIt SMTP → Mail Logs** to search by subject, recipient, or sender, filter by status, provider, or date, and open a message to see its recipients, sender, delivery status, error, and attachment names. The **Dashboard** shows sent and failed counts, the success rate, and the most recent failures.

Logs exclude credentials, authorization headers, and attachment contents. Message bodies are stored only when you turn on **Store message bodies** in **TechByIt SMTP → Settings**; bodies can contain private information such as password reset links. Logs are deleted automatically after the retention period (30 days by default, up to 365 days), and you can delete a single entry or all logs at any time. Only users who can manage site options can view logs.

== Installation ==

= Requirements =

* WordPress 6.2 or newer.
* PHP 7.4 or newer with the JSON and OpenSSL extensions enabled.
* A WordPress account with permission to install and activate plugins and manage site settings.
* Access to an SMTP server, including its host, port, encryption mode, and authentication details.

= Install the ZIP through WordPress =

1. Download the built TechByIt SMTP ZIP, named `techbyit-smtp-<version>.zip`.
2. Sign in to your WordPress admin dashboard.
3. Open **Plugins → Add New → Upload Plugin**.
4. Select the ZIP file and click **Install Now**. Upload the ZIP without extracting it.
5. Once installation finishes, click **Activate Plugin**.
6. Open **TechByIt SMTP** in the WordPress admin menu and follow the configuration steps below.

The ZIP includes the required PHP autoloader and compiled admin assets.

= Install manually with SFTP or a hosting file manager =

1. Extract the built ZIP on your computer.
2. Upload its `techbyit-smtp` folder to your site's `wp-content/plugins/` directory.
3. Check that the plugin entry file is at `wp-content/plugins/techbyit-smtp/techbyit-smtp.php`. Avoid placing the plugin inside a second nested `techbyit-smtp` folder.
4. Keep the package contents together, including `src/`, `vendor/`, `dist/`, and `licenses/`.
5. In WordPress, open **Plugins → Installed Plugins** and activate **TechByIt SMTP**.

= Configure SMTP and send a test email =

1. Open **TechByIt SMTP → Providers** and select **Custom SMTP**. It is currently the only provider with an implemented sending transport.
2. Enter the **SMTP Host**, **SMTP Port**, and **Encryption** mode supplied by your email service. Enable **Use authentication** and enter the **Username** and **Password** if the service requires them.
3. Click **Save Settings**, then **Set as Active Provider**. Saving the settings alone does not activate the provider.
4. Open **TechByIt SMTP → Settings**, set the default **From Name** and **From Email**, and save. Use a sender address your SMTP service permits.
5. Open **TechByIt SMTP → Dashboard**, enter a recipient email address you control, and click **Send Test Email**.
6. Confirm the success message, then check the recipient's inbox and spam folder. A successful send means the SMTP server accepted the message; it does not guarantee inbox delivery.
7. Open **TechByIt SMTP → Mail Logs** to see the test message and its delivery status.

= Installation troubleshooting =

* **Missing plugin files:** confirm that `vendor/autoload.php` was uploaded.
* **Missing admin assets:** confirm that `dist/admin.js` and `dist/admin.css` were uploaded.
* **No TechByIt SMTP menu:** confirm that the plugin is active and your WordPress account has permission to manage site settings.
* **No active provider warning:** save the Custom SMTP configuration and click **Set as Active Provider**.
* **Test email fails:** check the returned error and verify the host, port, encryption, credentials, and allowed sender address with your SMTP service.

== Frequently Asked Questions ==

= How do I fix WordPress not sending emails? =

Install TechByIt SMTP, configure your SMTP server under **TechByIt SMTP → Providers**, activate Custom SMTP, and send a test email. Routing WordPress email through an authenticated SMTP server is the most common fix for missing or undelivered WordPress emails.

= Do I need an SMTP service to use this plugin? =

Yes. To send through Custom SMTP, you need access to an SMTP server and its connection details. TechByIt SMTP does not provide an email account or SMTP service — it connects your WordPress site to one you already have.

= Which SMTP providers work with TechByIt SMTP? =

Any provider that offers standard SMTP connection details works with Custom SMTP, including Gmail, Google Workspace, Microsoft 365, Amazon SES, SendGrid, Mailgun, Brevo, and Postmark. Enter the host, port, encryption, username, and password from your provider.

= What happens when no SMTP provider is active? =

WordPress keeps its usual mail behavior. TechByIt SMTP starts routing mail through Custom SMTP after you configure and activate that provider.

= Can I connect Gmail, Microsoft 365, or an API provider directly? =

Their dedicated configuration forms can save settings, but their sending transports are not implemented. Saving OAuth client credentials does not connect an account. Use Custom SMTP with a service that supplies compatible SMTP connection details.

= Does it work with my form plugin? =

TechByIt SMTP handles email sent through WordPress's `wp_mail()` function, which is used by most form plugins, including Contact Form 7, WPForms, Gravity Forms, Fluent Forms, and Bit Form. If a plugin sends mail some other way, it will not be routed through TechByIt SMTP.

= Does a successful test email guarantee inbox delivery? =

No. Success means the SMTP server accepted the message. Check the recipient's inbox and spam folder to confirm where the message arrived.

= Can I view email logs or resend messages? =

Yes, you can view logs. Open **TechByIt SMTP → Mail Logs** to search and filter messages sent through TechByIt SMTP and open their details. Logging is on by default and can be turned off in **TechByIt SMTP → Settings**. Retry and resend are not available yet.

= Which emails are logged? =

Messages that TechByIt SMTP routes through the active provider, including test emails and messages that fail because the provider is not ready. When no provider is active, WordPress sends mail as usual and TechByIt SMTP does not log it.

= What do email logs store? =

The sender, recipients (To, Cc, Bcc, and Reply-To), subject, provider, status, a safe error summary, the server message ID, and attachment file names. Message bodies are stored only when **Store message bodies** is on. Attachment contents, credentials, and other headers are never stored.

= Why are saved passwords not shown in the form? =

Saved secrets are not displayed again. Leaving a saved password field blank keeps the existing value. Enter a new value to replace it. If your site's WordPress salts change, enter the credentials again.

= Does the plugin keep compatibility with earlier versions? =

Yes. Database option names, the log table, encryption context, and integration hook names retain their original identifiers so existing site data and hook integrations continue to work. PHP classes now use the `TechByIt\SMTP` namespace, and plugin constants use the `TECHBYIT_SMTP_` prefix. Code that directly references the former PHP namespace or constants must update those references. The primary `techbyit-smtp/v1` namespace and both earlier REST namespaces are available; new UI requests use `techbyit-smtp/v1`.

= Third-party libraries =

The compiled React bundle contains React, React DOM, and Scheduler under the MIT license; their shared license text is in `licenses/react-mit.txt`. The source code for the compiled admin assets is available at https://github.com/mdabbas-cse/techbyit-smtp

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