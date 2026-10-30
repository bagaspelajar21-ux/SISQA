import { createRouter, createWebHistory } from 'vue-router';
import LoginView from './views/LoginView.vue';
import DashboardView from './views/DashboardView.vue';
import ScheduleView from './views/ScheduleView.vue';
import TestCaseView from './views/TestCaseView.vue';
import BugView from './views/BugView.vue';
import RetestView from './views/RetestView.vue';
import FlowView from './views/FlowView.vue';
import GuideView from './views/GuideView.vue';
import ReportView from './views/ReportView.vue';
import WaReportView from './views/WaReportView.vue';
import ActivityLogView from './views/ActivityLogView.vue';
import MasterAppView from './views/MasterAppView.vue';
import MasterModuleView from './views/MasterModuleView.vue';
import MasterMenuView from './views/MasterMenuView.vue';
import UserManagementView from './views/UserManagementView.vue';
import ProfileView from './views/ProfileView.vue';

const routes = [
  {
    path: '/login',
    name: 'login',
    component: LoginView,
    meta: { public: true, title: 'Masuk - SISQA' },
  },
  {
    path: '/',
    name: 'dashboard',
    component: DashboardView,
    meta: { title: 'Dashboard Ringkasan QA' },
  },
  {
    path: '/schedules',
    name: 'schedules',
    component: ScheduleView,
    meta: { title: 'Jadwal QA & Alokasi Pengujian' },
  },
  {
    path: '/test-cases',
    name: 'test-cases',
    component: TestCaseView,
    meta: { title: 'Test Management & Eksekusi' },
  },
  {
    path: '/bugs',
    name: 'bugs',
    component: BugView,
    meta: { title: 'Bug Tracking & Defect Management' },
  },
  {
    path: '/retests',
    name: 'retests',
    component: RetestView,
    meta: { title: 'Siklus Verifikasi Retesting' },
  },
  {
    path: '/flows',
    name: 'flows',
    component: FlowView,
    meta: { title: 'Flow & Alur Proses Bisnis' },
  },
  {
    path: '/guides',
    name: 'guides',
    component: GuideView,
    meta: { title: 'Panduan Penggunaan SISQA' },
  },
  {
    path: '/reports',
    name: 'reports',
    component: ReportView,
    meta: { title: 'Pusat Laporan & Ekspor QA' },
  },
  {
    path: '/reports/wa',
    name: 'wa-report',
    component: WaReportView,
    meta: { title: 'Report WA — QA Daily Testing & Progress Report' },
  },
  {
    path: '/activity-logs',
    name: 'activity-logs',
    component: ActivityLogView,
    meta: { title: 'Activity Logs & Audit Trail' },
  },
  {
    path: '/master/apps',
    name: 'master-apps',
    component: MasterAppView,
    meta: { title: 'Master Data Aplikasi' },
  },
  {
    path: '/master/modules',
    name: 'master-modules',
    component: MasterModuleView,
    meta: { title: 'Master Data Modul' },
  },
  {
    path: '/master/menus',
    name: 'master-menus',
    component: MasterMenuView,
    meta: { title: 'Master Data Menu' },
  },
  {
    path: '/users',
    name: 'users',
    component: UserManagementView,
    meta: { title: 'Manajemen Pengguna', adminOnly: true },
  },
  {
    path: '/profile',
    name: 'profile',
    component: ProfileView,
    meta: { title: 'Profil Saya' },
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/',
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

const getFirstAllowedForUser = (user) => {
  if (!user) return '/login';
  if (user.role === 'admin') return '/';
  const menus = user.permissions?.menus;
  if (Array.isArray(menus)) {
    return menus.length > 0 ? menus[0] : '/profile';
  }
  return '/';
};

router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('sisqa_token');
  const userStr = localStorage.getItem('sisqa_user');
  const user = userStr ? JSON.parse(userStr) : null;

  if (!to.meta.public && !token) {
    return next({ name: 'login' });
  }

  if (to.name === 'login' && token) {
    return next(getFirstAllowedForUser(user));
  }

  if (to.meta.adminOnly && user?.role !== 'admin') {
    return next(getFirstAllowedForUser(user));
  }

  if (!to.meta.public && user && user.role !== 'admin' && to.path !== '/profile') {
    const menus = user.permissions?.menus;
    const isAllowed = Array.isArray(menus)
      ? menus.includes(to.path)
      : (!to.path.startsWith('/master') && to.path !== '/users');

    if (!isAllowed) {
      const fallback = getFirstAllowedForUser(user);
      if (fallback !== to.path) {
        return next(fallback);
      }
    }
  }

  document.title = `${to.meta.title || 'SISQA'} — Software Quality Assurance`;
  next();
});

export default router;
