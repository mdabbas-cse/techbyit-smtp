export type Page = 'dashboard' | 'mailers' | 'logs' | 'settings';

export interface BootstrapData {
  version: string;
}

export type Authentication = 'smtp_credentials' | 'api_key' | 'oauth2' | 'access_key' | 'provider_specific';
export type FieldType = 'text' | 'email' | 'number' | 'select' | 'toggle' | 'password';

export interface FieldSchema {
  name: string;
  label: string;
  type: FieldType;
  required?: boolean;
  required_when?: string;
  secret?: boolean;
  options?: string[];
  min?: number;
  max?: number;
  max_length?: number;
  format?: 'host' | 'domain' | 'region';
}

export interface ProviderSummary {
  id: string;
  name: string;
  description: string;
  icon: string;
  authentication: Authentication;
  features: string[];
  configured: boolean;
  active: boolean;
  sending_supported: boolean;
  authorization: { status: 'disconnected' | 'connected' | 'expired' | 'not_required' };
}

export interface ProviderMetadata extends Omit<ProviderSummary, 'configured' | 'active' | 'authorization'> {
  fields: FieldSchema[];
}

export type ProviderValue = string | number | boolean | { configured: boolean };
export interface ProviderSettings {
  id: string;
  values: Record<string, ProviderValue>;
  configured: boolean;
  active: boolean;
  authorization: ProviderSummary['authorization'];
}

export interface GeneralSettings {
  active_provider: string;
  from_name: string;
  from_email: string;
  logging_enabled: boolean;
  log_body: boolean;
  log_retention_days: number;
}

export type LogStatus = 'sending' | 'sent' | 'failed';

export interface LogSummary {
  id: number;
  provider: string;
  from_email: string;
  from_name: string;
  to: string[];
  subject: string;
  status: LogStatus;
  error_code: string;
  error_message: string;
  created_at: string | null;
  sent_at: string | null;
}

export interface LogDetails extends LogSummary {
  cc: string[];
  bcc: string[];
  reply_to: string[];
  headers: string[];
  attachments: string[];
  content_type: string;
  body: string | null;
  message_id: string;
  attempt_count: number;
  updated_at: string | null;
}

export interface LogPage {
  items: LogSummary[];
  total: number;
  page: number;
  per_page: number;
  total_pages: number;
}

export interface LogFilters {
  search: string;
  status: '' | LogStatus;
  provider: string;
  date_from: string;
  date_to: string;
  page: number;
}

export interface LogCounts { total: number; sent: number; failed: number; sending: number }

export interface DashboardData {
  logging_enabled: boolean;
  retention_days: number;
  last_7_days: LogCounts;
  last_30_days: LogCounts;
  recent_failures: LogSummary[];
}

declare global {
  interface Window {
    TechByItSMTPConfig?: { restUrl: string; nonce: string };
  }
}
