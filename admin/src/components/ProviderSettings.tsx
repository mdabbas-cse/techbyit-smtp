import { useEffect, useState } from 'react';
import { activateProvider, saveProviderSettings } from '../api/providers';
import { ApiError } from '../api/client';
import { validateProvider, type Draft } from '../state/validateProvider';
import type { ProviderMetadata, ProviderSettings as ProviderSettingsData } from '../types';
import { ProviderField } from './ProviderField';

export function ProviderSettings({ provider, settings, onUpdate }: {
  provider: ProviderMetadata; settings: ProviderSettingsData; onUpdate: (settings: ProviderSettingsData) => void;
}) {
  const [draft, setDraft] = useState<Draft>({});
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [message, setMessage] = useState('');
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    const next: Draft = {};
    for (const field of provider.fields) {
      if (field.secret) continue;
      const value = settings.values[field.name];
      if (typeof value === 'string' || typeof value === 'number' || typeof value === 'boolean') next[field.name] = value;
      else if (field.type === 'toggle') next[field.name] = false;
    }
    setDraft(next);
    setErrors({});
    setMessage('');
  }, [provider, settings]);

  async function save(event: React.FormEvent) {
    event.preventDefault();
    const validation = validateProvider(provider.fields, draft, settings);
    setErrors(validation);
    if (Object.keys(validation).length) return;
    setBusy(true); setMessage('');
    try {
      const payload: Draft = {};
      for (const field of provider.fields) {
        const value = draft[field.name];
        if (value !== undefined && (!field.secret || value !== '')) payload[field.name] = value;
      }
      const result = await saveProviderSettings(provider.id, payload);
      onUpdate(result);
      setMessage('Settings saved.');
    } catch (reason) {
      if (reason instanceof ApiError) { setErrors(reason.errors); setMessage(reason.message); }
      else setMessage('Could not save settings.');
    } finally {
      setDraft((current) => {
        const cleared = { ...current };
        for (const field of provider.fields) if (field.secret) delete cleared[field.name];
        return cleared;
      });
      setBusy(false);
    }
  }

  async function activate() {
    setBusy(true); setMessage('');
    try {
      onUpdate(await activateProvider(provider.id));
      setMessage('Provider selected as active.');
    } catch (reason) {
      setMessage(reason instanceof ApiError ? reason.message : 'Could not activate provider.');
    } finally { setBusy(false); }
  }

  const oauth = provider.authentication === 'oauth2';
  return <section className="techbyit-smtp-card techbyit-smtp-settings-panel">
    <h3>{provider.name}</h3><p>{provider.description}</p>
    {oauth && <div className="notice notice-info"><p>OAuth authorization is not available in this phase. Saving client credentials does not connect the account.</p></div>}
    {!provider.sending_supported && <div className="notice notice-info"><p>This provider can be configured, but its sending transport is not available yet.</p></div>}
    <form onSubmit={save} noValidate>
      {provider.fields.map((field) => <ProviderField key={field.name} field={field}
        value={draft[field.name] ?? (field.type === 'toggle' ? false : '')} configured={settings.values[field.name]}
        error={errors[field.name]} onChange={(value) => setDraft((current) => ({ ...current, [field.name]: value }))} />)}
      <button type="submit" className="button button-primary" disabled={busy}>{busy ? 'Saving…' : 'Save Settings'}</button>
    </form>
    <div className="techbyit-smtp-actions">
      <button type="button" className="button" onClick={activate} disabled={busy || !provider.sending_supported || !settings.configured || settings.active || (oauth && settings.authorization.status !== 'connected')}>Set as Active Provider</button>
      {settings.active && <span className="techbyit-smtp-meta">Active provider</span>}
      {oauth && <button type="button" className="button" disabled title="OAuth authorization will be implemented in a later phase">Connect (later phase)</button>}
    </div>
    {message && <p role="status">{message}</p>}
  </section>;
}
