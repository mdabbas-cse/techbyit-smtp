const countFormat = new Intl.NumberFormat(undefined, { notation: 'compact', maximumFractionDigits: 1 });

export const formatCount = (value: number) => countFormat.format(value);

export function formatDate(value: string | null): string {
  if (!value) return '—';
  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? '—' : date.toLocaleString();
}

/** Share of finished messages accepted by the server; in-flight messages are excluded. */
export function successRate(sent: number, failed: number): string {
  const finished = sent + failed;
  return finished ? `${Math.round((sent / finished) * 1000) / 10}%` : '—';
}

export function recipientSummary(to: string[]): string {
  if (!to.length) return '—';
  return to.length === 1 ? to[0] : `${to[0]} +${to.length - 1} more`;
}
