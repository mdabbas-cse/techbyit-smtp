import { renderToStaticMarkup } from 'react-dom/server';
import { expect, test } from 'vitest';
import { ProviderField } from './ProviderField';

test('secret field starts empty and shows configured state', () => {
  const html = renderToStaticMarkup(<ProviderField field={{ name: 'password', label: 'Password', type: 'password', secret: true }}
    value="" configured={{ configured: true }} onChange={() => undefined} />);
  expect(html).toContain('Configured');
  expect(html).toContain('type="password"');
  expect(html).toContain('value=""');
  expect(html).not.toContain('secret-123');
});

test('select field renders provider schema choices', () => {
  const html = renderToStaticMarkup(<ProviderField field={{ name: 'encryption', label: 'Encryption', type: 'select', options: ['none', 'ssl', 'tls'] }}
    value="tls" onChange={() => undefined} />);
  expect(html).toContain('value="tls"');
  expect(html).toContain('TLS');
});
