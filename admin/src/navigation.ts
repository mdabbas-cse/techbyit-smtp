import type { Page } from './types';

export const pages: { id: Page; label: string; slug: string }[] = [
  { id: 'dashboard', label: 'Dashboard', slug: 'techbyit-smtp' },
  { id: 'mailers', label: 'Providers', slug: 'techbyit-smtp-mailers' },
  { id: 'logs', label: 'Mail Logs', slug: 'techbyit-smtp-logs' },
  { id: 'settings', label: 'Settings', slug: 'techbyit-smtp-settings' },
];

export function pageFromSlug(slug: string): Page {
  return pages.find((page) => page.slug === slug)?.id ?? 'dashboard';
}
