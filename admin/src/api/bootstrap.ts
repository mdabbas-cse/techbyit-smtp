import type { BootstrapData } from '../types';

export async function getBootstrap(config = window.TechByItSMTPConfig): Promise<BootstrapData> {
  if (!config) throw new Error('TechByIt SMTP configuration is missing.');
  const response = await fetch(new URL('bootstrap', config.restUrl), {
    credentials: 'same-origin',
    headers: { 'X-WP-Nonce': config.nonce },
  });
  if (!response.ok) {
    throw new Error(response.status === 403 ? 'You do not have permission to view TechByIt SMTP.' : 'Could not load TechByIt SMTP data.');
  }
  const data: unknown = await response.json();
  if (!data || typeof data !== 'object' || !('version' in data) || typeof data.version !== 'string') {
    throw new Error('TechByIt SMTP returned an invalid response.');
  }
  return data as BootstrapData;
}
