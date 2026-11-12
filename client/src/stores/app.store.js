import { request } from '../services/api.service.js';
import { authService } from '../services/auth.service.js';
import { userService } from '../services/user.service.js';
import { operatorService } from '../services/operator.service.js';

const { defineStore } = window.Pinia;
export const useAppStore = defineStore('app', {
  state: () => ({ actor: null, view: 'dashboard', data: {}, form: {}, loading: false, error: '', success: '', authMode: 'login', login: { email: '', password: '' }, register: { prenom: '', nom: '', email: '', password: '' } }),
  getters: { isOperator: state => state.actor?.type === 'operateur', title: state => ({ dashboard: 'Tableau de bord', stations: 'Stations', trips: 'Mes trajets', account: 'Mon compte', operations: 'Opérations', statistics: 'Statistiques' }[state.view] || 'VéloLibre') },
  actions: {
    async run(task) { this.error = ''; this.success = ''; this.loading = true; try { await task(); } catch (e) { this.error = e.message; } finally { this.loading = false; } },
    async load(next) { await this.run(async () => { this.view = next; if (next === 'stations') this.data = await request('/network/stations'); else if (this.isOperator) this.data = await operatorService[{ dashboard: 'overview', operations: 'operations', statistics: 'statistics' }[next] || 'overview'](); else this.data = await userService[{ dashboard: 'dashboard', trips: 'trips', account: 'account' }[next] || 'dashboard'](); this.data.form = this.form; }); },
    async boot() { try { const result = await authService.me(); this.actor = result.actor; if (this.actor) await this.load('dashboard'); } catch (_) {} },
    async loginUser() { await this.run(async () => { this.actor = (await authService.login(this.login)).actor; await this.load('dashboard'); }); },
    async registerUser() { await this.run(async () => { this.success = (await authService.register(this.register)).message; this.authMode = 'login'; }); },
    async logout() { await this.run(async () => { await authService.logout(); this.actor = null; this.data = {}; }); },
    async action(type, payload, next = this.view) { await this.run(async () => { const result = this.isOperator ? await operatorService.action(type, payload) : await userService.action(type, payload); this.success = result.message; await this.load(next); }); },
    async resetDatabase() { await this.run(async () => { if (!window.confirm('Remettre la base dans son état initial ? Toutes les données actuelles seront supprimées.')) return; this.success = (await request('/common/reset-database', { method: 'POST' })).message; await this.load('dashboard'); }); }
  }
});
