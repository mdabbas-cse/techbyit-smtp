import { expect, test } from 'vitest';
import { validateProvider } from './validateProvider';
import type { FieldSchema, ProviderSettings } from '../types';

const fields: FieldSchema[] = [
  { name: 'host', label: 'Host', type: 'text', required: true },
  { name: 'port', label: 'Port', type: 'number', required: true, min: 1, max: 65535 },
  { name: 'authentication', label: 'Authentication', type: 'toggle' },
  { name: 'password', label: 'Password', type: 'password', secret: true, required_when: 'authentication' },
];
const settings: ProviderSettings = { id: 'custom_smtp', values: { password: { configured: true } }, configured: true, active: false, authorization: { status: 'not_required' } };

test('rejects missing host and out-of-range SMTP port', () => {
  expect(validateProvider(fields, { port: '70000', authentication: true }, settings)).toEqual({ host: 'This field is required.', port: 'Enter a valid number.' });
});

test('allows a configured password to stay blank', () => {
  expect(validateProvider(fields, { host: 'smtp.example.com', port: 587, authentication: true }, settings)).toEqual({});
});
