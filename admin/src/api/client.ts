export class ApiError extends Error {
  constructor(message: string, public readonly errors: Record<string, string> = {}) {
    super(message);
  }
}

interface Envelope<T> { success: boolean; data?: T; message?: string; errors?: Record<string, string> }

export async function request<T>(path: string, method: 'GET' | 'POST' = 'GET', body?: Record<string, unknown>): Promise<T> {
  const config = window.TechByItSMTPConfig;
  if (!config) throw new ApiError('TechByIt SMTP configuration is missing.');
  const response = await fetch(new URL(path, config.restUrl), {
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
