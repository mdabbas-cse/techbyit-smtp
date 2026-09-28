import { renderToStaticMarkup } from 'react-dom/server';
import { expect, test } from 'vitest';
import { TestEmailForm } from './TestEmailPage';

test('warns when no sending provider is active', () => {
  const html = renderToStaticMarkup(<TestEmailForm activeProvider={null} sending={false} onSend={async () => undefined} />);
  expect(html).toContain('No active mail provider is configured');
  expect(html).toContain('Configure Provider');
  expect(html).not.toContain('Send Test Email</button>');
});

test('enables test send for active configured SMTP', () => {
  const html = renderToStaticMarkup(<TestEmailForm activeProvider="Custom SMTP" sending={false} onSend={async () => undefined} />);
  expect(html).toContain('Send Test Email</button>');
  expect(html).toContain('Custom SMTP');
});
