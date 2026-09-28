import type { FieldSchema, ProviderValue } from '../types';

export function ProviderField({ field, value, configured, error, onChange }: {
  field: FieldSchema; value: string | number | boolean; configured?: ProviderValue; error?: string;
  onChange: (value: string | number | boolean) => void;
}) {
  const id = `techbyit-field-${field.name}`;
  const hasSecret = field.secret && typeof configured === 'object' && configured?.configured;
  return <div className="techbyit-smtp-field">
    <label htmlFor={id}>{field.label}{field.required && ' *'}</label>
    {field.type === 'toggle' ? <input id={id} type="checkbox" checked={value === true} onChange={(event) => onChange(event.target.checked)} />
      : field.type === 'select' ? <select id={id} value={String(value ?? '')} onChange={(event) => onChange(event.target.value)}><option value="">Select…</option>{field.options?.map((option) => <option key={option} value={option}>{option.toUpperCase()}</option>)}</select>
      : <input id={id} type={field.type === 'password' ? 'password' : field.type === 'number' ? 'number' : field.type === 'email' ? 'email' : 'text'}
        value={value === undefined ? '' : String(value)} min={field.min} max={field.max} maxLength={field.max_length} autoComplete={field.secret ? 'new-password' : 'off'}
        placeholder={hasSecret ? 'Leave blank to keep the saved value' : undefined}
        onChange={(event) => onChange(event.target.value)} />}
    {hasSecret && <span className="techbyit-smtp-meta">Configured. Enter a new value to replace it.</span>}
    {error && <span role="alert" className="techbyit-smtp-error">{error}</span>}
  </div>;
}
