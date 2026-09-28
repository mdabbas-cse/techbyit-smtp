import { useEffect, useState } from 'react';
import { ApiError } from '../api/client';
import { getGeneralSettings, saveGeneralSettings } from '../api/providers';
import type { GeneralSettings } from '../types';

export function SettingsPage() {
  const [settings, setSettings] = useState<GeneralSettings | null>(null);
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [message, setMessage] = useState('');
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    let active = true;
    getGeneralSettings().then((result) => { if (active) setSettings(result); })
      .catch(() => { if (active) setMessage('Could not load settings.'); });
    return () => { active = false; };
  }, []);

  async function save(event: React.FormEvent) {
    event.preventDefault();
    if (!settings) return;
    const validation: Record<string, string> = {};
    if (settings.from_email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(settings.from_email)) validation.from_email = 'Enter a valid email address.';
    setErrors(validation);
    if (Object.keys(validation).length) return;
    setBusy(true); setMessage('');
    try {
      setSettings(await saveGeneralSettings({ from_name: settings.from_name, from_email: settings.from_email }));
      setMessage('Settings saved.');
    } catch (reason) {
      if (reason instanceof ApiError) { setErrors(reason.errors); setMessage(reason.message); }
      else setMessage('Could not save settings.');
    } finally { setBusy(false); }
  }

  if (!settings) return <p role="status">{message || 'Loading settings…'}</p>;
  return <section className="techbyit-smtp-card techbyit-smtp-settings-panel">
    <h3>General Settings</h3><p>These values are saved now; mail routing will be added later.</p>
    <form onSubmit={save} noValidate>
      <div className="techbyit-smtp-field"><label htmlFor="techbyit-from-name">From Name</label><input id="techbyit-from-name" type="text" value={settings.from_name} onChange={(event) => setSettings({ ...settings, from_name: event.target.value })} /></div>
      <div className="techbyit-smtp-field"><label htmlFor="techbyit-from-email">From Email</label><input id="techbyit-from-email" type="email" value={settings.from_email} onChange={(event) => setSettings({ ...settings, from_email: event.target.value })} />{errors.from_email && <span role="alert" className="techbyit-smtp-error">{errors.from_email}</span>}</div>
      <p>Active provider: <strong>{settings.active_provider || 'None selected'}</strong></p>
      <button type="submit" className="button button-primary" disabled={busy}>{busy ? 'Saving…' : 'Save Settings'}</button>
    </form>
    {message && <p role="status">{message}</p>}
  </section>;
}
