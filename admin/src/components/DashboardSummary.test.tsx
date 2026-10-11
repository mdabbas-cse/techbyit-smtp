import { renderToStaticMarkup } from 'react-dom/server';
import { expect, test } from 'vitest';
import { DashboardStats } from './DashboardSummary';
import { successRate } from '../format';
import type { DashboardData } from '../types';

const data: DashboardData = {
  logging_enabled: true,
  retention_days: 30,
  last_7_days: { total: 5, sent: 3, failed: 1, sending: 1 },
  last_30_days: { total: 12, sent: 10, failed: 2, sending: 0 },
  recent_failures: [{
    id: 4, provider: 'custom_smtp', from_email: '', from_name: '', to: ['one@example.test'], subject: 'Order receipt', status: 'failed',
    error_code: 'smtp_authentication_failed', error_message: 'The SMTP server rejected authentication.', created_at: '2026-10-11T08:00:00+00:00', sent_at: null,
  }],
};
const noop = () => undefined;

test('summarizes the selected period and links recent failures to their details', () => {
  const html = renderToStaticMarkup(<DashboardStats data={data} period={7} onPeriod={noop} />);
  expect(html).toContain('75%');
  expect(html).toContain('aria-pressed="true"');
  expect(html).toContain('admin.php?page=techbyit-smtp-logs&amp;log=4');
  expect(html).toContain('The SMTP server rejected authentication.');
  expect(html).not.toContain('Mail logging is off');
});

test('warns when logging is off or retention is shorter than the period', () => {
  const html = renderToStaticMarkup(<DashboardStats data={{ ...data, logging_enabled: false, retention_days: 7, recent_failures: [] }} period={30} onPeriod={noop} />);
  expect(html).toContain('Mail logging is off');
  expect(html).toContain('Logs are kept for 7 days');
  expect(html).toContain('No failed messages are logged.');
});

test('success rate ignores in-flight messages and handles no data', () => {
  expect(successRate(2, 1)).toBe('66.7%');
  expect(successRate(0, 0)).toBe('—');
});
