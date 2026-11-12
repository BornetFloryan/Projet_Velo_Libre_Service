import { createRouter, createWebHistory } from "vue-router";

import AccountView from "../views/user/AccountView.vue";
import LoginView from "../views/auth/LoginView.vue";
import OperationsView from "../views/operator/OperationsView.vue";
import OperatorDashboardView from "../views/operator/OperatorDashboardView.vue";
import RegisterView from "../views/auth/RegisterView.vue";
import StationsView from "../views/common/StationsView.vue";
import StatisticsView from "../views/operator/StatisticsView.vue";
import TripsView from "../views/user/TripsView.vue";
import UserDashboardView from "../views/user/UserDashboardView.vue";
import { useAppStore } from "../stores/app.store";

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: "/", redirect: "/connexion" },
    { path: "/connexion", component: LoginView, meta: { public: true } },
    { path: "/inscription", component: RegisterView, meta: { public: true } },
    { path: "/usager", component: UserDashboardView, meta: { role: "usager" } },
    { path: "/stations", component: StationsView, meta: { auth: true } },
    { path: "/usager/trajets", component: TripsView, meta: { role: "usager" } },
    {
      path: "/usager/compte",
      component: AccountView,
      meta: { role: "usager" },
    },
    {
      path: "/operateur",
      component: OperatorDashboardView,
      meta: { role: "operateur" },
    },
    {
      path: "/operateur/operations",
      component: OperationsView,
      meta: { role: "operateur" },
    },
    {
      path: "/operateur/statistiques",
      component: StatisticsView,
      meta: { role: "operateur" },
    },
    { path: "/:pathMatch(.*)*", redirect: "/connexion" },
  ],
});

router.beforeEach(async (to) => {
  const store = useAppStore();
  if (!store.initialized) await store.boot();
  if (to.meta.public && store.actor)
    return store.isOperator ? "/operateur" : "/usager";
  if ((to.meta.auth || to.meta.role) && !store.actor) return "/connexion";
  if (to.meta.role && store.actor?.type !== to.meta.role)
    return store.isOperator ? "/operateur" : "/usager";
  return true;
});
export default router;
