import type { LogStatus } from '../types';

const statuses: Record<LogStatus, { label: string; icon: string }> = {
  sent: { label: 'Sent', icon: 'yes-alt' },
  failed: { label: 'Failed', icon: 'warning' },
  sending: { label: 'Sending', icon: 'clock' },
};

export function StatusBadge({ status }: { status: LogStatus }) {
  const item = statuses[status] ?? { label: status, icon: 'marker' };
  return <span className={`techbyit-smtp-status is-${status}`}><span className={`dashicons dashicons-${item.icon}`} aria-hidden="true" />{item.label}</span>;
}
