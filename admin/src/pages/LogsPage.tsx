import { useEffect, useRef, useState, type FormEvent } from 'react';
import { clearLogs, deleteLog, getLog, getLogs } from '../api/logs';
import { getProviders } from '../api/providers';
import { StatusBadge } from '../components/StatusBadge';
import { formatDate, recipientSummary } from '../format';
import type { LogDetails, LogFilters, LogPage, LogStatus, LogSummary, ProviderSummary } from '../types';

export const emptyFilters: LogFilters = { search: '', status: '', provider: '', date_from: '', date_to: '', page: 1 };
const statuses: LogStatus[] = ['sent', 'failed', 'sending'];

/** Reads deep links such as `&status=failed&log=12` from the admin page URL. */
export function initialView(search: string): { filters: LogFilters; logId: number | null } {
  const params = new URLSearchParams(search);
  const status = params.get('status') ?? '';
  const logId = Number(params.get('log'));
  return {
    filters: { ...emptyFilters, status: (statuses as string[]).includes(status) ? status as LogStatus : '' },
    logId: Number.isInteger(logId) && logId > 0 ? logId : null,
  };
}

interface FilterBarProps { draft: LogFilters; providers: ProviderSummary[]; onChange: (draft: LogFilters) => void; onApply: () => void; onReset: () => void }

export function LogFilterBar({ draft, providers, onChange, onApply, onReset }: FilterBarProps) {
  const set = (key: keyof LogFilters, value: string) => onChange({ ...draft, [key]: value });
  const submit = (event: FormEvent) => { event.preventDefault(); onApply(); };
  return <form className="techbyit-smtp-filters" onSubmit={submit} role="search" aria-label="Filter mail logs">
    <div className="techbyit-smtp-field techbyit-smtp-field-wide"><label htmlFor="techbyit-log-search">Search</label><input id="techbyit-log-search" type="search" maxLength={200} placeholder="Subject, recipient, or sender" value={draft.search} onChange={(event) => set('search', event.target.value)} /></div>
    <div className="techbyit-smtp-field"><label htmlFor="techbyit-log-status">Status</label><select id="techbyit-log-status" value={draft.status} onChange={(event) => set('status', event.target.value)}>
      <option value="">All statuses</option><option value="sent">Sent</option><option value="failed">Failed</option><option value="sending">Sending</option>
    </select></div>
    <div className="techbyit-smtp-field"><label htmlFor="techbyit-log-provider">Provider</label><select id="techbyit-log-provider" value={draft.provider} onChange={(event) => set('provider', event.target.value)}>
      <option value="">All providers</option>{providers.map((provider) => <option key={provider.id} value={provider.id}>{provider.name}</option>)}
    </select></div>
    <div className="techbyit-smtp-field"><label htmlFor="techbyit-log-from">From date</label><input id="techbyit-log-from" type="date" value={draft.date_from} onChange={(event) => set('date_from', event.target.value)} /></div>
    <div className="techbyit-smtp-field"><label htmlFor="techbyit-log-to">To date</label><input id="techbyit-log-to" type="date" value={draft.date_to} min={draft.date_from || undefined} onChange={(event) => set('date_to', event.target.value)} /></div>
    <div className="techbyit-smtp-actions"><button type="submit" className="button button-primary">Apply Filters</button><button type="button" className="button" onClick={onReset}>Reset</button></div>
  </form>;
}

interface TableProps { items: LogSummary[]; providerNames: Record<string, string>; onView: (id: number) => void }

export function LogTable({ items, providerNames, onView }: TableProps) {
  if (!items.length) return <p className="techbyit-smtp-empty">No mail logs match these filters.</p>;
  return <div className="techbyit-smtp-table-wrap"><table className="wp-list-table widefat striped techbyit-smtp-log-table">
    <thead><tr><th scope="col">Status</th><th scope="col">Subject</th><th scope="col">To</th><th scope="col">Provider</th><th scope="col">Date</th><th scope="col"><span className="screen-reader-text">Actions</span></th></tr></thead>
    <tbody>{items.map((log) => <tr key={log.id}>
      <td><StatusBadge status={log.status} /></td>
      <td>{log.subject || '(no subject)'}{log.status === 'failed' && log.error_message && <div className="techbyit-smtp-meta">{log.error_message}</div>}</td>
      <td>{recipientSummary(log.to)}</td>
      <td>{providerNames[log.provider] ?? log.provider}</td>
      <td>{formatDate(log.created_at)}</td>
      <td><button type="button" className="button button-small" onClick={() => onView(log.id)} aria-label={`View details for ${log.subject || 'message without subject'}`}>View</button></td>
    </tr>)}</tbody>
  </table></div>;
}

interface DetailsProps { log: LogDetails; providerName: string; deleting: boolean; onClose: () => void; onDelete: () => void }

export function LogDetailsPanel({ log, providerName, deleting, onClose, onDelete }: DetailsProps) {
  const heading = useRef<HTMLHeadingElement>(null);
  useEffect(() => { heading.current?.focus(); }, [log.id]);
  const rows: [string, string][] = [
    ['From', log.from_name ? `${log.from_name} <${log.from_email}>` : log.from_email || '—'],
    ['To', log.to.join(', ') || '—'],
    ...(log.cc.length ? [['Cc', log.cc.join(', ')] as [string, string]] : []),
    ...(log.bcc.length ? [['Bcc', log.bcc.join(', ')] as [string, string]] : []),
    ...(log.reply_to.length ? [['Reply-To', log.reply_to.join(', ')] as [string, string]] : []),
    ['Provider', providerName],
    ['Created', formatDate(log.created_at)],
    ['Sent', formatDate(log.sent_at)],
    ['Message ID', log.message_id || '—'],
    ['Content type', log.content_type],
    ['Attachments', log.attachments.join(', ') || 'None'],
  ];
  return <section className="techbyit-smtp-card techbyit-smtp-details" aria-labelledby="techbyit-log-details-title">
    <div className="techbyit-smtp-card-header"><h3 id="techbyit-log-details-title" ref={heading} tabIndex={-1}>{log.subject || '(no subject)'}</h3><StatusBadge status={log.status} /></div>
    {log.status === 'failed' && <div className="notice notice-error inline"><p>{log.error_message || 'The message could not be sent.'}{log.error_code && <> <code>{log.error_code}</code></>}</p></div>}
    <dl>{rows.map(([label, value]) => <div key={label} className="techbyit-smtp-detail-row"><dt>{label}</dt><dd>{value}</dd></div>)}</dl>
    <h4>Message body</h4>
    {log.body === null
      ? <p>The message body was not stored. Turn on <strong>Store message bodies</strong> in Settings to keep bodies of new messages.</p>
      : <>{log.content_type === 'text/html' && <p className="techbyit-smtp-meta">HTML is shown as source text and is not rendered.</p>}<pre className="techbyit-smtp-body">{log.body}</pre></>}
    <div className="techbyit-smtp-actions"><button type="button" className="button" onClick={onClose}>Close</button><button type="button" className="button button-link-delete" disabled={deleting} onClick={onDelete}>{deleting ? 'Deleting…' : 'Delete Entry'}</button></div>
  </section>;
}

export function LogsPage() {
  const [start] = useState(() => initialView(window.location.search));
  const [filters, setFilters] = useState<LogFilters>(start.filters);
  const [draft, setDraft] = useState<LogFilters>(start.filters);
  const [data, setData] = useState<LogPage | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [providers, setProviders] = useState<ProviderSummary[]>([]);
  const [selected, setSelected] = useState<LogDetails | null>(null);
  const [notice, setNotice] = useState('');
  const [busy, setBusy] = useState(false);
  const [reload, setReload] = useState(0);

  useEffect(() => {
    let active = true;
    getProviders().then((items) => { if (active) setProviders(items); }).catch(() => undefined);
    return () => { active = false; };
  }, []);

  useEffect(() => {
    let active = true;
    setLoading(true); setError('');
    getLogs(filters).then((value) => {
      if (!active) return;
      // A deletion can leave the current page past the end; step back to the last page.
      if (!value.items.length && value.total > 0 && value.page > value.total_pages) setFilters((current) => ({ ...current, page: value.total_pages }));
      else setData(value);
    })
      .catch((reason: unknown) => { if (active) setError(reason instanceof Error ? reason.message : 'Could not load mail logs.'); })
      .finally(() => { if (active) setLoading(false); });
    return () => { active = false; };
  }, [filters, reload]);

  const view = async (id: number) => {
    setNotice('');
    try { setSelected(await getLog(id)); }
    catch (reason) { setNotice(reason instanceof Error ? reason.message : 'Could not load the log entry.'); }
  };

  useEffect(() => { if (start.logId) void view(start.logId); }, [start]);

  const remove = async () => {
    if (!selected || !window.confirm('Delete this log entry? This cannot be undone.')) return;
    setBusy(true);
    try { await deleteLog(selected.id); setSelected(null); setNotice('Log entry deleted.'); setReload((value) => value + 1); }
    catch (reason) { setNotice(reason instanceof Error ? reason.message : 'Could not delete the log entry.'); }
    finally { setBusy(false); }
  };

  const clearAll = async () => {
    if (!window.confirm('Delete all mail logs? This cannot be undone.')) return;
    setBusy(true);
    try {
      const result = await clearLogs();
      setSelected(null); setNotice(`Deleted ${result.deleted} log ${result.deleted === 1 ? 'entry' : 'entries'}.`);
      setFilters({ ...filters, page: 1 }); setReload((value) => value + 1);
    } catch (reason) { setNotice(reason instanceof Error ? reason.message : 'Could not delete mail logs.'); }
    finally { setBusy(false); }
  };

  const providerNames = Object.fromEntries(providers.map((provider) => [provider.id, provider.name]));
  const goTo = (page: number) => setFilters({ ...filters, page });

  return <div className="techbyit-smtp-stack">
    <section className="techbyit-smtp-card">
      <LogFilterBar draft={draft} providers={providers} onChange={setDraft} onApply={() => setFilters({ ...draft, page: 1 })} onReset={() => { setDraft(emptyFilters); setFilters(emptyFilters); }} />
    </section>
    {notice && <p role="status">{notice}</p>}
    {selected && <LogDetailsPanel log={selected} providerName={providerNames[selected.provider] ?? selected.provider} deleting={busy} onClose={() => setSelected(null)} onDelete={() => { void remove(); }} />}
    {error && <div className="notice notice-error" role="alert"><p>{error}</p></div>}
    {loading && !data && <p role="status">Loading mail logs…</p>}
    {data && <section className="techbyit-smtp-card" aria-busy={loading}>
      <div className="techbyit-smtp-card-header"><h3>{data.total} {data.total === 1 ? 'message' : 'messages'}</h3>
        {data.total > 0 && <button type="button" className="button button-link-delete" disabled={busy} onClick={() => { void clearAll(); }}>Delete All Logs</button>}</div>
      <LogTable items={data.items} providerNames={providerNames} onView={(id) => { void view(id); }} />
      {data.total_pages > 1 && <nav className="techbyit-smtp-pagination" aria-label="Mail log pages">
        <button type="button" className="button" disabled={data.page <= 1 || loading} onClick={() => goTo(data.page - 1)}>Previous</button>
        <span>Page {data.page} of {data.total_pages}</span>
        <button type="button" className="button" disabled={data.page >= data.total_pages || loading} onClick={() => goTo(data.page + 1)}>Next</button>
      </nav>}
    </section>}
  </div>;
}
