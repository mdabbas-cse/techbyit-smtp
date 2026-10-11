# TechByIt SMTP architecture and phased delivery

## Product boundary

TechByIt SMTP is a WordPress admin plugin for site owners. The plugin routes ordinary `wp_mail()` calls through an active Custom SMTP provider. Phase 4 adds mail logging, log inspection, dashboard statistics, and retention. Retry and resend are planned for Phase 5. The plugin never changes WordPress core. PHP 7.4 and WordPress 6.2 are the minimum targets.

## Design choices

The recommended design uses WordPress hooks for compatibility and a transport interface for delivery. SMTP providers configure the PHPMailer object through `phpmailer_init`; API providers use `pre_wp_mail` only for their own active transport. A per-request context links `wp_mail` arguments to `wp_mail_succeeded`/`wp_mail_failed`, with an explicit result path for API transports. It must avoid replacing `wp_mail`, changing attachments or headers, or intercepting mail when no provider is active. Provider modules own validation and authentication, including OAuth where needed. WordPress remains the mail API for callers.

Two alternatives were considered: routing every service through SMTP, which fails for API and OAuth providers, and replacing `wp_mail`, which risks plugin incompatibility. The transport boundary keeps each provider isolated while preserving the WordPress contract.

## Directory structure

```text
techbyit-smtp.php               Plugin entry point and activation hook
composer.json                   PSR-4 autoload and PHP test tooling
src/Plugin.php                  Hook registration
src/Database/Installer.php      Versioned dbDelta migration
src/Providers/                 Provider contracts, registry, and schemas (Phase 2)
src/Admin/                     Menu registration and asset enqueue
src/Rest/                      Controllers and permission checks
src/Settings/                  Validated settings and encrypted secrets (Phase 2)
src/Mail/                      Mail manager and transports (later phase)
src/Logs/                      Log repository, mail logger, retention (Phase 4); retry policy (Phase 5)
admin/                         React/TypeScript app, API client, state, pages
tests/php/                      Core service tests
admin/src/**/*.test.ts(x)        Critical frontend tests
dist/                           Built admin assets shipped with plugin
```

## Backend interfaces and data flow

`ProviderInterface` supplies a stable ID and safe metadata, including name, authentication kind, supported features, and configuration fields. `ProviderRegistry` holds isolated schemas and `ProviderManager` resolves them by ID. Extensions can register providers through the `mailflow_smtp_register_providers` action. Later each provider module supplies a transport/configurator. Built-ins are Custom SMTP, Gmail, Google Workspace, Microsoft 365, Amazon SES, SendGrid, Mailgun, Brevo, and Postmark. Custom SMTP uses PHPMailer; provider APIs use WordPress HTTP requests. Gmail and Workspace use the Gmail API with OAuth2; Microsoft 365 uses Microsoft Graph with OAuth2; Amazon SES uses AWS Signature Version 4; the remaining APIs use API keys. OAuth providers use a server-side flow and encrypted refresh tokens. No API key or token is returned by provider metadata.

`SettingsRepository` reads/writes the active provider and From identity via the Settings API or options with strict validation, and stores credentials in a separate non-autoloaded option. `CredentialCipher` uses authenticated encryption with a key derived from WordPress secret salts plus plugin context; absent crypto support is a hard configuration error. Secrets are write-only over REST, with boolean `configured` indicators returned to the UI. Existing encrypted data must not be overwritten by blank fields.

Phase 3 uses `ProviderManager` to resolve the selected provider and validated private settings. A sending provider declares its transport capability; Custom SMTP implements `SmtpTransportProviderInterface`. `MailManager` returns a `TransportSelection`. `WordPressMailIntegration` observes `wp_mail`, `pre_wp_mail`, `phpmailer_init`, `wp_mail_succeeded`, and `wp_mail_failed`. WordPress continues to parse recipients, MIME headers, embeds, and attachments. `PHPMailerConfigurator` changes SMTP properties only; sender filters apply configured From values only when the message has no valid explicit From header. A `MailMessage` contains normalized addresses and allowlisted headers for hooks, and `MailResult` contains safe status data. The hooks `mailflow_smtp_before_send`, `mailflow_smtp_mail_sent`, and `mailflow_smtp_mail_failed` provide the logging extension points. Successful send means accepted by SMTP, not delivered to an inbox.

Phase 4 adds `MailLogger`, which listens to those three hooks. `mailflow_smtp_before_send` inserts a `sending` row, and the matching sent or failed hook updates that row, keyed by the `MailMessage` instance. A failure raised before sending, such as an unconfigured provider, inserts a `failed` row directly. Logging errors are caught so they cannot block delivery. Mail that WordPress sends while no provider is active is not logged. `LogRepository` owns every query. A daily `techbyit_smtp_purge_logs` cron event deletes rows older than the retention period in bounded batches; plugin deactivation clears the event. Retry policy remains future work.

## Database schema

Table name is `$wpdb->prefix . 'mailflow_smtp_logs'` (`wp_mailflow_smtp_logs` for the default prefix). `dbDelta` runs on activation and on schema version change. Schema version 2 (Phase 4) adds `error_code`, `attachments`, and a `created_at` index to the phase 1 table:

| Column | Type | Purpose |
| --- | --- | --- |
| `id` | bigint unsigned, primary key | Internal identifier |
| `message_id` | varchar(255), indexed | PHPMailer/provider ID when available |
| `provider` | varchar(64), indexed | Provider ID used on last attempt |
| `from_email` | varchar(320) | Effective sender |
| `from_name` | varchar(255) | Effective name |
| `recipients` | longtext | JSON of to/cc/bcc addresses |
| `subject` | text | Message subject |
| `headers` | longtext | JSON of allowlisted replay-safe headers |
| `body` | longtext | Optional body, controlled by logging setting |
| `content_type` | varchar(100) | Plain or HTML replay type |
| `status` | varchar(20), indexed | `sent`, `failed`, `sending` |
| `error_code` | varchar(64) | Classified failure code, such as `smtp_connection_failed` |
| `error_message` | text | Redacted, bounded failure summary |
| `attachments` | longtext | JSON of attachment file names only; contents and paths are never stored |
| `attempt_count` | int unsigned | Attempts, including first send |
| `created_at`, `updated_at`, `sent_at` | datetime | UTC timestamps |

Logging excludes credentials, authorization headers, cookies, and OAuth data; headers are limited to the `MailMessage` allowlist. Logging settings live in `mailflow_smtp_settings`: `logging_enabled` (default on), `log_body` (default off), and `log_retention_days` (1–365, default 30). Bodies are stored only when `log_body` is on and the body is valid UTF-8 no larger than 1 MiB. When no body is stored, retry/resend will be unavailable for that log. All log routes require `manage_options`. Attachments are not persisted, so retries requiring an attachment must be disabled with a clear reason. List and dashboard responses never include bodies; the UI loads a body only after an explicit details action and shows HTML as source text.

## REST API (`techbyit-smtp/v1`)

The earlier `flowmail-smtp/v1` and `mailflow-smtp/v1` REST namespaces remain registered for existing integrations. New admin requests use `techbyit-smtp/v1`. Stored `mailflow_smtp_*` options, the log table name, encryption context, and `mailflow_smtp_*` hooks remain unchanged to preserve existing data and integrations. PHP classes now use the `TechByIt\SMTP` namespace and constants use the `TECHBYIT_SMTP_` prefix; integrations referencing the former PHP names must update them.

The `/bootstrap` endpoint requires `manage_options` and uses WordPress cookie authentication with `X-WP-Nonce` in the admin app. Implemented endpoints follow the same permission model. Each implemented route has an explicit permission callback and validated arguments. Responses omit secrets. Routes for OAuth, retry, and resend in the table below are planned only.

| Method | Path | Purpose |
| --- | --- | --- |
| GET | `/bootstrap` | Plugin version (Phase 1) |
| GET | `/providers` | Provider catalog and configured/active state (Phase 2) |
| GET | `/providers/{id}` | Provider schema (Phase 2) |
| GET/POST | `/providers/{id}/settings` | Redacted configuration and write-only secret updates (Phase 2) |
| POST | `/providers/{id}/activate` | Select configured provider |
| GET/POST | `/settings` | From identity and active provider (Phase 2) |
| POST | `/providers/{id}/oauth/start` | Begin authorized OAuth flow |
| GET | `/providers/{id}/oauth/callback` | State-verified callback |
| POST | `/test-email` | Send one controlled test message through active SMTP (Phase 3) |
| GET | `/dashboard` | 7- and 30-day counts by status and five recent failures (Phase 4) |
| GET | `/logs` | Search, status/provider/date filters, pagination (Phase 4) |
| DELETE | `/logs` | Delete all log rows (Phase 4) |
| GET | `/logs/{id}` | Message details, subject to retention rules (Phase 4) |
| DELETE | `/logs/{id}` | Delete one log row (Phase 4) |
| POST | `/logs/{id}/retry` | One failed-message attempt |
| POST | `/logs/{id}/resend` | One deliberate repeat of a sent message |

OAuth callbacks additionally validate one-time state and capability. Retry/resend endpoints reject absent replay content, attachment-only messages, concurrent claims, and exhausted attempt budgets. No retry is triggered by viewing a log.

## React admin structure

WordPress menu entries mount the same scoped React app with Dashboard, Providers, Mail Logs, and Settings views. A small API client adds the REST nonce and handles `WP_Error` responses. Page hooks or a context layer own loading/error state; presentational components render data. Shared components include `PageShell`, `StatusBadge`, `FilterBar`, `Pagination`, `SecretField`, `ProviderCard`, and `ConfirmAction`. Mailer forms are provider-owned: SMTP host/port/security/credentials, OAuth connect status for Google/Microsoft, and API key/region/domain/endpoint fields where applicable. Secrets are kept only in local form state until submission, cleared after it, and never hydrated from REST.

## Phases

1. **Foundation:** installable plugin bootstrap, Composer autoload, versioned log table migration, capability-protected bootstrap REST route, four-page React admin shell, and Vite build.
2. **Provider configuration:** provider registry and schema-driven forms, encrypted settings, From identity, and active provider selection.
3. **Mail sending:** Custom SMTP through WordPress PHPMailer, structured results, safe failure classification, and admin test email.
4. **Logs and dashboard (current delivery):** safe capture, repository, search/filter/detail API and UI, retention.
5. **Manual retry/resend and release:** concurrency policy, attempt accounting, confirmation UI, hardening, packaging.

The current UI labels API and OAuth sending as unavailable. It does not imply those providers can deliver mail.
