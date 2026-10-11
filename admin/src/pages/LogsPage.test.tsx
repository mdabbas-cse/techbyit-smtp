import { renderToStaticMarkup } from 'react-dom/server';
import { expect, test } from 'vitest';
import { LogDetailsPanel, LogTable, initialView } from './LogsPage';
import type { LogDetails, LogSummary } from '../types';

const summary: LogSummary = {
  id: 7, provider: 'custom_smtp', from_email: 'sender@example.test', from_name: 'Sender', to: ['one@example.test', 'two@example.test'],
  subject: '<script>alert(1)</script>', status: 'failed', error_code: 'smtp_connection_failed', error_message: 'The SMTP server could not be reached.',
  created_at: '2026-10-11T08:00:00+00:00', sent_at: null,
};
const details: LogDetails = {
  ...summary, cc: [], bcc: ['hidden@example.test'], reply_to: [], headers: [], attachments: ['invoice.pdf'],
  content_type: 'text/html', body: null, message_id: '', attempt_count: 1, updated_at: summary.created_at,
};
const noop = () => undefined;

test('lists log rows with status labels, provider names, and escaped subjects', () => {
  const html = renderToStaticMarkup(<LogTable items={[summary]} providerNames={{ custom_smtp: 'Custom SMTP' }} onView={noop} />);
  expect(html).toContain('Failed');
  expect(html).toContain('dashicons-warning');
  expect(html).toContain('Custom SMTP');
  expect(html).toContain('one@example.test +1 more');
  expect(html).toContain('The SMTP server could not be reached.');
  expect(html).not.toContain('<script>');
});

test('shows an empty state when no logs match', () => {
  expect(renderToStaticMarkup(<LogTable items={[]} providerNames={{}} onView={noop} />)).toContain('No mail logs match these filters.');
});

test('explains a missing body and lists attachment names only', () => {
  const html = renderToStaticMarkup(<LogDetailsPanel log={details} providerName="Custom SMTP" deleting={false} onClose={noop} onDelete={noop} />);
  expect(html).toContain('The message body was not stored');
  expect(html).toContain('invoice.pdf');
  expect(html).toContain('hidden@example.test');
  expect(html).toContain('smtp_connection_failed');
});

test('shows a stored HTML body as source text instead of rendering it', () => {
  const html = renderToStaticMarkup(<LogDetailsPanel log={{ ...details, status: 'sent', body: '<img src=x onerror=alert(1)>' }} providerName="Custom SMTP" deleting={false} onClose={noop} onDelete={noop} />);
  expect(html).toContain('&lt;img src=x onerror=alert(1)&gt;');
  expect(html).not.toContain('<img');
  expect(html).toContain('HTML is shown as source text');
});

test('reads status filters and a log id from dashboard links', () => {
  expect(initialView('?page=techbyit-smtp-logs&status=failed&log=12')).toMatchObject({ filters: { status: 'failed', page: 1 }, logId: 12 });
  expect(initialView('?status=deleted&log=-1')).toMatchObject({ filters: { status: '' }, logId: null });
});
