import { afterEach, expect, test, vi } from 'vitest';
import { getBootstrap } from './bootstrap';

afterEach(() => vi.unstubAllGlobals());

test('passes the WordPress REST nonce and returns the plugin version', async () => {
  const fetchMock = vi.fn().mockResolvedValue({ ok: true, json: async () => ({ version: '0.1.0' }) });
  vi.stubGlobal('fetch', fetchMock);
  const result = await getBootstrap({ restUrl: 'https://example.test/wp-json/techbyit-smtp/v1/', nonce: 'nonce' });
  expect(result.version).toBe('0.1.0');
  expect(fetchMock).toHaveBeenCalledWith(new URL('https://example.test/wp-json/techbyit-smtp/v1/bootstrap'), {
    credentials: 'same-origin', headers: { 'X-WP-Nonce': 'nonce' },
  });
});

test('gives a clear authorization error', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ ok: false, status: 403 }));
  await expect(getBootstrap({ restUrl: 'https://example.test/wp-json/techbyit-smtp/v1/', nonce: 'bad' }))
    .rejects.toThrow('You do not have permission');
});

test('rejects malformed bootstrap data', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ ok: true, json: async () => ({}) }));
  await expect(getBootstrap({ restUrl: 'https://example.test/wp-json/techbyit-smtp/v1/', nonce: 'nonce' }))
    .rejects.toThrow('invalid response');
});
