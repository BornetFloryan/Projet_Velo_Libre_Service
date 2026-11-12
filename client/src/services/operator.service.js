import { request } from './api.service.js';
export const operatorService = { overview: () => request('/operator/overview'), operations: () => request('/operator/operations'), statistics: () => request('/operator/statistics'), action: (type, payload) => request(`/operator/actions/${type}`, { method: 'POST', body: JSON.stringify(payload) }) };
