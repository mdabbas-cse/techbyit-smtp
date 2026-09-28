import { request } from './client';

export interface TestEmailResponse { message: string; provider: string }
export const sendTestEmail = (to: string) => request<TestEmailResponse>('test-email', 'POST', { to });
