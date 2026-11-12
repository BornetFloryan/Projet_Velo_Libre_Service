import { request } from "./api.service.js";
export const authService = {
  me: () => request("/auth/me"),
  login: (credentials) =>
    request("/auth/login", {
      method: "POST",
      body: JSON.stringify(credentials),
    }),
  register: (profile) =>
    request("/auth/register", {
      method: "POST",
      body: JSON.stringify(profile),
    }),
  logout: () => request("/auth/logout", { method: "POST" }),
};
