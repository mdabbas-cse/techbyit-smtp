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
}

declare global {
  interface Window {
    TechByItSMTPConfig?: { restUrl: string; nonce: string };
  }
}
