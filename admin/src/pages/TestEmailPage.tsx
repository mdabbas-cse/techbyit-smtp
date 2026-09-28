import { useEffect, useState, type FormEvent } from 'react';
import { getProviders } from '../api/providers';
import { sendTestEmail } from '../api/testEmail';
import type { ProviderSummary } from '../types';

interface FormProps { activeProvider: string | null; sending: boolean; onSend: (email: string) => Promise<void> }

export function TestEmailForm({ activeProvider, sending, onSend }: FormProps) {
  const [email, setEmail] = useState('');
  const [result, setResult] = useState('');
  const [failed, setFailed] = useState(false);
  const submit = async (event: FormEvent) => {
    event.preventDefault();
    setResult('');
    try { await onSend(email); setResult('Test email sent successfully.'); setFailed(false); }
    catch (reason) { setResult(reason instanceof Error ? reason.message : 'The test email could not be sent.'); setFailed(true); }
  };
  return <section className="techbyit-smtp-card techbyit-smtp-test">
    <h2>Test Email</h2>
    {!activeProvider ? <div role="status"><p>No active mail provider is configured for sending.</p><a className="button" href="admin.php?page=techbyit-smtp-mailers">Configure Provider</a></div> : <>
      <p>Send a test email using {activeProvider}.</p>
      <form onSubmit={(event) => { void submit(event); }}>
        <label className="techbyit-smtp-field">Recipient Email<input type="email" required value={email} onChange={(event) => setEmail(event.target.value)} autoComplete="email" /></label>
        <button className="button button-primary" type="submit" disabled={sending}>{sending ? 'Sending…' : 'Send Test Email'}</button>
      </form>
      {result && <p role={failed ? 'alert' : 'status'} className={failed ? 'techbyit-smtp-error' : ''}>{result}</p>}
    </>}
  </section>;
}

export function TestEmailPage() {
  const [providers, setProviders] = useState<ProviderSummary[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [sending, setSending] = useState(false);
  useEffect(() => {
    let active = true;
    getProviders().then((items) => { if (active) setProviders(items); })
      .catch(() => { if (active) setError('Could not load provider status.'); })
      .finally(() => { if (active) setLoading(false); });
    return () => { active = false; };
  }, []);
  const provider = providers.find((item) => item.active && item.configured && item.sending_supported);
  const onSend = async (email: string) => {
    setSending(true);
    try { await sendTestEmail(email); } finally { setSending(false); }
  };
  return <>{loading && <p role="status">Loading provider status…</p>}{error && <div className="notice notice-error" role="alert"><p>{error}</p></div>}
    {!loading && !error && <TestEmailForm activeProvider={provider?.name ?? null} sending={sending} onSend={onSend} />}</>;
}
