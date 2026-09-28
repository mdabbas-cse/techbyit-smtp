import { renderToStaticMarkup } from 'react-dom/server';
import { expect, test } from 'vitest';
import { ProviderSettings } from './ProviderSettings';
import type { ProviderMetadata, ProviderSettings as Settings } from '../types';

test('does not offer activation for a provider without a sending transport', () => {
  const provider: ProviderMetadata = {
    id: 'sendgrid', name: 'SendGrid', description: 'API provider', icon: 'email', authentication: 'api_key',
    features: ['api'], sending_supported: false,
    fields: [{ name: 'api_key', label: 'API Key', type: 'password', secret: true, required: true }],
  };
  const settings: Settings = { id: 'sendgrid', configured: true, active: false,
    values: { api_key: { configured: true } }, authorization: { status: 'not_required' } };
  const html = renderToStaticMarkup(<ProviderSettings provider={provider} settings={settings} onUpdate={() => undefined} />);
  expect(html).toContain('sending transport is not available yet');
  expect(html).toMatch(/disabled=""[^>]*>Set as Active Provider/);
});
