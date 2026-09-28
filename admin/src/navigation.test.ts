import { expect, test } from 'vitest';
import { pageFromSlug, pages } from './navigation';

test('maps each WordPress submenu slug to its React page', () => {
  for (const page of pages) expect(pageFromSlug(page.slug)).toBe(page.id);
  expect(pageFromSlug('unknown')).toBe('dashboard');
});
