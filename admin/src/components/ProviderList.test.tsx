import { renderToStaticMarkup } from 'react-dom/server';
import { expect, test } from 'vitest';
import { ProviderList } from './ProviderList';
import type { ProviderSummary } from '../types';

const summary = (id: string, name: string): ProviderSummary => ({
  id, name, description: `${name} description`, icon: 'email', authentication: 'api_key',
  features: ['api'], configured: false, active: false, sending_supported: false, authorization: { status: 'not_required' },
});

test('renders each provider from the API list', () => {
  const html = renderToStaticMarkup(<ProviderList providers={[summary('sendgrid', 'SendGrid'), summary('postmark', 'Postmark')]}
    selected="sendgrid" onSelect={() => undefined} />);
  expect(html).toContain('SendGrid');
  expect(html).toContain('Postmark');
  expect((html.match(/>Configure<\/button>/g) ?? []).length).toBe(2);
});
