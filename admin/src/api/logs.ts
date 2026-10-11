import { request } from './client';
import type { DashboardData, LogDetails, LogFilters, LogPage } from '../types';

export function logsPath(filters: LogFilters): string {
  const params = new URLSearchParams();
  for (const [key, value] of Object.entries(filters)) {
    if (value !== '' && !(key === 'page' && value === 1)) params.set(key, String(value));
  }
  const query = params.toString();
  return query ? `logs?${query}` : 'logs';
}

export const getLogs = (filters: LogFilters) => request<LogPage>(logsPath(filters));
export const getLog = (id: number) => request<LogDetails>(`logs/${id}`);
export const deleteLog = (id: number) => request<{ deleted: number }>(`logs/${id}`, 'DELETE');
export const clearLogs = () => request<{ deleted: number }>('logs', 'DELETE');
export const getDashboard = () => request<DashboardData>('dashboard');
