# TechByIt SMTP architecture and phased delivery

## Product boundary

TechByIt SMTP is a WordPress admin plugin for site owners. The current release routes ordinary `wp_mail()` calls through an active Custom SMTP provider. Logging, outcome inspection, retry, and resend are planned for later phases and are not available in version 1.0.0. The plugin never changes WordPress core. PHP 7.4 and WordPress 6.2 are the minimum targets.

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
src/Logs/                      Log repository and retry policy (later phase)
admin/                         React/TypeScript app, API client, state, pages
tests/php/                      Core service tests
admin/src/**/*.test.ts(x)        Critical frontend tests
dist/                           Built admin assets shipped with plugin
```

## Backend interfaces and data flow

`ProviderInterface` supplies a stable ID and safe metadata, including name, authentication kind, supported features, and configuration fields. `ProviderRegistry` holds isolated schemas and `ProviderManager` resolves them by ID. Extensions can register providers through the `mailflow_smtp_register_providers` action. Later each provider module supplies a transport/configurator. Built-ins are Custom SMTP, Gmail, Google Workspace, Microsoft 365, Amazon SES, SendGrid, Mailgun, Brevo, and Postmark. Custom SMTP uses PHPMailer; provider APIs use WordPress HTTP requests. Gmail and Workspace use the Gmail API with OAuth2; Microsoft 365 uses Microsoft Graph with OAuth2; Amazon SES uses AWS Signature Version 4; the remaining APIs use API keys. OAuth providers use a server-side flow and encrypted refresh tokens. No API key or token is returned by provider metadata.

`SettingsRepository` reads/writes the active provider and From identity via the Settings API or options with strict validation, and stores credentials in a separate non-autoloaded option. `CredentialCipher` uses authenticated encryption with a key derived from WordPress secret salts plus plugin context; absent crypto support is a hard configuration error. Secrets are write-only over REST, with boolean `configured` indicators returned to the UI. Existing encrypted data must not be overwritten by blank fields.

Phase 3 uses `ProviderManager` to resolve the selected provider and validated private settings. A sending provider declares its transport capability; Custom SMTP implements `SmtpTransportProviderInterface`. `MailManager` returns a `TransportSelection`. `WordPressMailIntegration` observes `wp_mail`, `pre_wp_mail`, `phpmailer_init`, `wp_mail_succeeded`, and `wp_mail_failed`. WordPress continues to parse recipients, MIME headers, embeds, and attachments. `PHPMailerConfigurator` changes SMTP properties only; sender filters apply configured From values only when the message has no valid explicit From header. A `MailMessage` contains normalized addresses and allowlisted headers for hooks, and `MailResult` contains safe status data. The hooks `mailflow_smtp_before_send`, `mailflow_smtp_mail_sent`, and `mailflow_smtp_mail_failed` provide the Phase 4 logging extension points. Successful send means accepted by SMTP, not delivered to an inbox. `LogRepository` and retry policy remain future work.

## Database schema

Table name is `$wpdb->prefix . 'mailflow_smtp_logs'` (`wp_mailflow_smtp_logs` for the default prefix). `dbDelta` runs on activation and on schema version change. The phase 1 schema is:

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
| `error_message` | text | Redacted, bounded failure summary |
| `attempt_count` | int unsigned | Attempts, including first send |
| `created_at`, `updated_at`, `sent_at` | datetime | UTC timestamps |

Planned logging must exclude credentials, authorization headers, cookies, and OAuth data. The planned body capture setting defaults off; when off, retry/resend is unavailable for that log. When on, body and recipient access must be restricted to `manage_options`; retention and purge settings must bound storage. Attachments will not be persisted, so retries requiring an attachment must be disabled with a clear reason. Mail logs can contain private content; the future UI must show it only after an explicit details action.

## REST API (`techbyit-smtp/v1`)

The earlier `flowmail-smtp/v1` and `mailflow-smtp/v1` REST namespaces remain registered for existing integrations. New admin requests use `techbyit-smtp/v1`. Stored `mailflow_smtp_*` options, the log table name, encryption context, and `mailflow_smtp_*` hooks remain unchanged to preserve existing data and integrations. PHP classes now use the `TechByIt\SMTP` namespace and constants use the `TECHBYIT_SMTP_` prefix; integrations referencing the former PHP names must update them.

The `/bootstrap` endpoint requires `manage_options` and uses WordPress cookie authentication with `X-WP-Nonce` in the admin app. Implemented endpoints follow the same permission model. Each implemented route has an explicit permission callback and validated arguments. Responses omit secrets. Routes for OAuth, dashboard statistics, logs, retry, and resend in the table below are planned only.

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
| GET | `/dashboard` | Aggregate counts and recent failures |
| GET | `/logs` | Search, status/provider/date filters, pagination |
| GET | `/logs/{id}` | Message details, subject to retention rules |
| POST | `/logs/{id}/retry` | One failed-message attempt |
| POST | `/logs/{id}/resend` | One deliberate repeat of a sent message |

OAuth callbacks additionally validate one-time state and capability. Retry/resend endpoints reject absent replay content, attachment-only messages, concurrent claims, and exhausted attempt budgets. No retry is triggered by viewing a log.

## React admin structure

WordPress menu entries mount the same scoped React app with Dashboard, Providers, Mail Logs, and Settings views. A small API client adds the REST nonce and handles `WP_Error` responses. Page hooks or a context layer own loading/error state; presentational components render data. Shared components include `PageShell`, `StatusBadge`, `FilterBar`, `Pagination`, `SecretField`, `ProviderCard`, and `ConfirmAction`. Mailer forms are provider-owned: SMTP host/port/security/credentials, OAuth connect status for Google/Microsoft, and API key/region/domain/endpoint fields where applicable. Secrets are kept only in local form state until submission, cleared after it, and never hydrated from REST.

## Phases

1. **Foundation:** installable plugin bootstrap, Composer autoload, versioned log table migration, capability-protected bootstrap REST route, four-page React admin shell, and Vite build.
2. **Provider configuration:** provider registry and schema-driven forms, encrypted settings, From identity, and active provider selection.
3. **Mail sending (current delivery):** Custom SMTP through WordPress PHPMailer, structured results, safe failure classification, and admin test email.
4. **Logs and dashboard:** safe capture, repository, search/filter/detail API and UI, retention.
5. **Manual retry/resend and release:** concurrency policy, attempt accounting, confirmation UI, hardening, packaging.

The current UI labels API and OAuth sending as unavailable. It does not imply those providers can deliver mail.
