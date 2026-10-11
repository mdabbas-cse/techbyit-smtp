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
    if (!Number.isInteger(settings.log_retention_days) || settings.log_retention_days < 1 || settings.log_retention_days > 365) validation.log_retention_days = 'Enter a whole number of days from 1 to 365.';
    setErrors(validation);
    if (Object.keys(validation).length) return;
    setBusy(true); setMessage('');
    try {
      const { from_name, from_email, logging_enabled, log_body, log_retention_days } = settings;
      setSettings(await saveGeneralSettings({ from_name, from_email, logging_enabled, log_body, log_retention_days }));
      setMessage('Settings saved.');
    } catch (reason) {
      if (reason instanceof ApiError) { setErrors(reason.errors); setMessage(reason.message); }
      else setMessage('Could not save settings.');
    } finally { setBusy(false); }
  }

  if (!settings) return <p role="status">{message || 'Loading settings…'}</p>;
  return <section className="techbyit-smtp-card techbyit-smtp-settings-panel">
    <h3>General Settings</h3><p>These values are used as the default sender when TechByIt SMTP routes mail.</p>
    <form onSubmit={save} noValidate>
      <div className="techbyit-smtp-field"><label htmlFor="techbyit-from-name">From Name</label><input id="techbyit-from-name" type="text" value={settings.from_name} onChange={(event) => setSettings({ ...settings, from_name: event.target.value })} /></div>
      <div className="techbyit-smtp-field"><label htmlFor="techbyit-from-email">From Email</label><input id="techbyit-from-email" type="email" value={settings.from_email} onChange={(event) => setSettings({ ...settings, from_email: event.target.value })} />{errors.from_email && <span role="alert" className="techbyit-smtp-error">{errors.from_email}</span>}</div>
      <p>Active provider: <strong>{settings.active_provider || 'None selected'}</strong></p>
      <h3>Mail Logging</h3>
      <p>Logs record messages sent through TechByIt SMTP so you can find delivery problems.</p>
      <div className="techbyit-smtp-field techbyit-smtp-check"><label><input type="checkbox" checked={settings.logging_enabled} onChange={(event) => setSettings({ ...settings, logging_enabled: event.target.checked })} /> Log emails sent through TechByIt SMTP</label></div>
      <div className="techbyit-smtp-field techbyit-smtp-check"><label><input type="checkbox" aria-describedby="techbyit-log-body-help" checked={settings.log_body} disabled={!settings.logging_enabled} onChange={(event) => setSettings({ ...settings, log_body: event.target.checked })} /> Store message bodies</label>
        <span id="techbyit-log-body-help" className="techbyit-smtp-meta">Bodies can contain private information, such as password reset links. Leave this off unless you need to inspect message content.</span></div>
      <div className="techbyit-smtp-field"><label htmlFor="techbyit-log-retention">Delete logs after (days)</label><input id="techbyit-log-retention" type="number" min={1} max={365} step={1} value={Number.isNaN(settings.log_retention_days) ? '' : settings.log_retention_days} onChange={(event) => setSettings({ ...settings, log_retention_days: event.target.valueAsNumber })} />{errors.log_retention_days && <span role="alert" className="techbyit-smtp-error">{errors.log_retention_days}</span>}</div>
      <button type="submit" className="button button-primary" disabled={busy}>{busy ? 'Saving…' : 'Save Settings'}</button>
    </form>
    {message && <p role="status">{message}</p>}
  </section>;
}
