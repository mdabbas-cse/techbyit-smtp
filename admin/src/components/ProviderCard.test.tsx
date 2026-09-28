import { renderToStaticMarkup } from 'react-dom/server';
import { expect, test } from 'vitest';
import { ProviderCard } from './ProviderCard';
import type { ProviderSummary } from '../types';

const provider: ProviderSummary = {
  id: 'gmail', name: 'Gmail', description: 'Connect a Gmail account.', icon: 'google',
  authentication: 'oauth2', features: ['api'], configured: true, active: false, sending_supported: false,
  authorization: { status: 'disconnected' },
};

test('shows configured and OAuth state without exposing credentials', () => {
  const html = renderToStaticMarkup(<ProviderCard provider={provider} selected={false} onSelect={() => undefined} />);
  expect(html).toContain('Configured');
  expect(html).toContain('Inactive');
  expect(html).toContain('disconnected');
  expect(html).toContain('Configure');
  expect(html).not.toContain('client_secret');
});
