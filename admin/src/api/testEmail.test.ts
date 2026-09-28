import { afterEach, expect, test, vi } from 'vitest';
import { sendTestEmail } from './testEmail';

afterEach(() => vi.unstubAllGlobals());

test('test email uses the protected POST route and returns safe status', async () => {
  vi.stubGlobal('window', { TechByItSMTPConfig: { restUrl: 'https://example.test/wp-json/techbyit-smtp/v1/', nonce: 'test-nonce' } });
  const fetchMock = vi.fn().mockResolvedValue({ ok: true, json: async () => ({ success: true, data: { message: 'Test email sent successfully.', provider: 'custom_smtp' } }) });
  vi.stubGlobal('fetch', fetchMock);
  const result = await sendTestEmail('admin@example.test');
  expect(result.provider).toBe('custom_smtp');
  const [url, options] = fetchMock.mock.calls[0];
  expect(String(url)).toBe('https://example.test/wp-json/techbyit-smtp/v1/test-email');
  expect(options.method).toBe('POST');
  expect(options.headers['X-WP-Nonce']).toBe('test-nonce');
  expect(options.body).toBe('{"to":"admin@example.test"}');
});
