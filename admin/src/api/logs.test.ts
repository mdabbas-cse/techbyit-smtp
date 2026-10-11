import { afterEach, expect, test, vi } from 'vitest';
import { deleteLog, logsPath } from './logs';

afterEach(() => vi.unstubAllGlobals());

const filters = { search: '', status: '' as const, provider: '', date_from: '', date_to: '', page: 1 };

test('omits empty filters and the first page from the logs query', () => {
  expect(logsPath(filters)).toBe('logs');
  expect(logsPath({ ...filters, search: 'a&b=c', status: 'failed', page: 3 })).toBe('logs?search=a%26b%3Dc&status=failed&page=3');
});

test('deletes a log entry with the DELETE method and nonce', async () => {
  vi.stubGlobal('window', { TechByItSMTPConfig: { restUrl: 'https://example.test/wp-json/techbyit-smtp/v1/', nonce: 'nonce' } });
  const fetchMock = vi.fn().mockResolvedValue({ ok: true, json: async () => ({ success: true, data: { deleted: 1 } }) });
  vi.stubGlobal('fetch', fetchMock);
  await expect(deleteLog(12)).resolves.toEqual({ deleted: 1 });
  const [url, init] = fetchMock.mock.calls[0];
  expect(String(url)).toBe('https://example.test/wp-json/techbyit-smtp/v1/logs/12');
  expect(init.method).toBe('DELETE');
  expect(init.headers['X-WP-Nonce']).toBe('nonce');
});
