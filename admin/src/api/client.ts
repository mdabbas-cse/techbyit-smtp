export class ApiError extends Error {
  constructor(message: string, public readonly errors: Record<string, string> = {}) {
    super(message);
  }
}

interface Envelope<T> { success: boolean; data?: T; message?: string; errors?: Record<string, string> }

// rest_url() returns "…/index.php?rest_route=/ns/" on sites with plain permalinks,
// where resolving a relative path with new URL() would drop the route.
export function endpointUrl(restUrl: string, path: string): URL {
  const url = new URL(restUrl);
  const [route, query = ''] = path.split('?', 2);
  const routeParam = url.searchParams.get('rest_route');
  if (routeParam !== null) {
    url.searchParams.set('rest_route', routeParam.replace(/\/?$/, '/') + route);
  } else {
    url.pathname = url.pathname.replace(/\/?$/, '/') + route;
  }
  new URLSearchParams(query).forEach((value, key) => url.searchParams.append(key, value));
  return url;
}

export async function request<T>(path: string, method: 'GET' | 'POST' | 'DELETE' = 'GET', body?: Record<string, unknown>): Promise<T> {
  const config = window.TechByItSMTPConfig;
  if (!config) throw new ApiError('TechByIt SMTP configuration is missing.');
  const response = await fetch(endpointUrl(config.restUrl, path), {
    method,
    credentials: 'same-origin',
    headers: { 'X-WP-Nonce': config.nonce, ...(body ? { 'Content-Type': 'application/json' } : {}) },
    ...(body ? { body: JSON.stringify(body) } : {}),
  });
  let envelope: Envelope<T>;
  try {
    envelope = await response.json() as Envelope<T>;
  } catch {
    throw new ApiError('The server returned an invalid response.');
  }
  if (!response.ok || !envelope.success || envelope.data === undefined) {
    throw new ApiError(envelope.message ?? 'The request failed.', envelope.errors ?? {});
  }
  return envelope.data;
}
