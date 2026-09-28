import type { FieldSchema, ProviderSettings } from '../types';

export type Draft = Record<string, string | number | boolean>;

export function validateProvider(fields: FieldSchema[], draft: Draft, settings: ProviderSettings): Record<string, string> {
  const errors: Record<string, string> = {};
  for (const field of fields) {
    const value = draft[field.name];
    const configured = Boolean((settings.values[field.name] as { configured?: boolean } | undefined)?.configured);
    const required = field.required || (field.required_when ? draft[field.required_when] === true : false);
    if (required && (value === undefined || value === '') && !(field.secret && configured)) {
      errors[field.name] = 'This field is required.';
      continue;
    }
    if (field.type === 'number' && value !== undefined && value !== '') {
      const number = Number(value);
      if (!Number.isInteger(number) || number < (field.min ?? 1) || number > (field.max ?? 65535)) errors[field.name] = 'Enter a valid number.';
    }
    if (field.type === 'email' && typeof value === 'string' && value !== '' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
      errors[field.name] = 'Enter a valid email address.';
    }
  }
  return errors;
}
