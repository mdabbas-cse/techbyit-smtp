import { useEffect, useState } from 'react';
import { getDashboard } from '../api/logs';
import { formatCount, formatDate, recipientSummary, successRate } from '../format';
import type { DashboardData } from '../types';

export type Period = 7 | 30;

function Stat({ label, value }: { label: string; value: string }) {
  return <div className="techbyit-smtp-stat"><span className="techbyit-smtp-stat-label">{label}</span><span className="techbyit-smtp-stat-value">{value}</span></div>;
}

interface StatsProps { data: DashboardData; period: Period; onPeriod: (period: Period) => void }

export function DashboardStats({ data, period, onPeriod }: StatsProps) {
  const counts = period === 7 ? data.last_7_days : data.last_30_days;
  return <section className="techbyit-smtp-card techbyit-smtp-dashboard" aria-labelledby="techbyit-activity-title">
    <div className="techbyit-smtp-card-header"><h2 id="techbyit-activity-title">Email Activity</h2>
      <div className="techbyit-smtp-segmented" role="group" aria-label="Time period">
        {([7, 30] as Period[]).map((days) => <button key={days} type="button" className="button" aria-pressed={period === days} onClick={() => onPeriod(days)}>Last {days} days</button>)}
      </div>
    </div>
    {!data.logging_enabled && <div className="notice notice-warning inline"><p>Mail logging is off, so new messages are not counted. <a href="admin.php?page=techbyit-smtp-settings">Turn on logging</a></p></div>}
    <div className="techbyit-smtp-stats">
      <Stat label="Sent" value={formatCount(counts.sent)} />
      <Stat label="Failed" value={formatCount(counts.failed)} />
      <Stat label="Success rate" value={successRate(counts.sent, counts.failed)} />
    </div>
    {data.retention_days < period && <p className="techbyit-smtp-meta">Logs are kept for {data.retention_days} {data.retention_days === 1 ? 'day' : 'days'}, so older messages are not counted.</p>}
    <h3>Recent Failures</h3>
    {data.recent_failures.length === 0 ? <p>No failed messages are logged.</p> : <>
      <ul className="techbyit-smtp-failures">{data.recent_failures.map((log) => <li key={log.id}>
        <span><a href={`admin.php?page=techbyit-smtp-logs&log=${log.id}`}>{log.subject || '(no subject)'}</a><span className="techbyit-smtp-meta"> to {recipientSummary(log.to)} · {log.error_message}</span></span>
        <span className="techbyit-smtp-meta">{formatDate(log.created_at)}</span>
      </li>)}</ul>
      <a className="button" href="admin.php?page=techbyit-smtp-logs&status=failed">View All Failures</a>
    </>}
  </section>;
}

export function DashboardSummary() {
  const [data, setData] = useState<DashboardData | null>(null);
  const [error, setError] = useState('');
  const [period, setPeriod] = useState<Period>(7);
  useEffect(() => {
    let active = true;
    getDashboard().then((value) => { if (active) setData(value); })
      .catch(() => { if (active) setError('Could not load email activity.'); });
    return () => { active = false; };
  }, []);
  if (error) return <div className="notice notice-error" role="alert"><p>{error}</p></div>;
  if (!data) return <p role="status">Loading email activity…</p>;
  return <DashboardStats data={data} period={period} onPeriod={setPeriod} />;
}
