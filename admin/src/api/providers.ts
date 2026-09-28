import { request } from './client';
import type { GeneralSettings, ProviderMetadata, ProviderSettings, ProviderSummary } from '../types';

const providerPath = (id: string) => `providers/${encodeURIComponent(id)}`;

export const getProviders = () => request<ProviderSummary[]>('providers');
export const getProvider = (id: string) => request<ProviderMetadata>(providerPath(id));
export const getProviderSettings = (id: string) => request<ProviderSettings>(`${providerPath(id)}/settings`);
export const saveProviderSettings = (id: string, values: Record<string, string | number | boolean>) =>
  request<ProviderSettings>(`${providerPath(id)}/settings`, 'POST', values);
export const activateProvider = (id: string) => request<ProviderSettings>(`${providerPath(id)}/activate`, 'POST', {});
export const getGeneralSettings = () => request<GeneralSettings>('settings');
export const saveGeneralSettings = (values: Pick<GeneralSettings, 'from_name' | 'from_email'>) =>
  request<GeneralSettings>('settings', 'POST', values);
