import { request } from "./api.service.js";
export const userService = {
  dashboard: () => request("/user/dashboard"),
  trips: () => request("/user/trips"),
  account: () => request("/user/account"),
  action: (type, payload) =>
    request(`/user/actions/${type}`, {
      method: "POST",
      body: JSON.stringify(payload),
    }),
};
