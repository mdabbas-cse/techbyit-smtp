import { afterEach, expect, test, vi } from 'vitest';
import { request } from './client';

afterEach(() => vi.unstubAllGlobals());

test('POST sends JSON credentials in the request body with a nonce', async () => {
  vi.stubGlobal('window', { TechByItSMTPConfig: { restUrl: 'https://example.test/wp-json/techbyit-smtp/v1/', nonce: 'nonce' } });
  const fetchMock = vi.fn().mockResolvedValue({ ok: true, json: async () => ({ success: true, data: { configured: true } }) });
  vi.stubGlobal('fetch', fetchMock);
  await request('providers/sendgrid/settings', 'POST', { api_key: 'test-only-secret' });
  const [, init] = fetchMock.mock.calls[0];
  expect(init.body).toBe('{"api_key":"test-only-secret"}');
  expect(init.headers['X-WP-Nonce']).toBe('nonce');
  expect(String(fetchMock.mock.calls[0][0])).not.toContain('test-only-secret');
});

test('validation errors retain field messages', async () => {
  vi.stubGlobal('window', { TechByItSMTPConfig: { restUrl: 'https://example.test/wp-json/techbyit-smtp/v1/', nonce: 'nonce' } });
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ ok: false, json: async () => ({ success: false, message: 'Invalid', errors: { host: 'Required' } }) }));
  await expect(request('providers/custom_smtp/settings')).rejects.toMatchObject({ message: 'Invalid', errors: { host: 'Required' } });
});
