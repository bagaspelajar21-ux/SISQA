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
import WeeklyWorkReportView from './views/WeeklyWorkReportView.vue';
import ActivityLogView from './views/ActivityLogView.vue';
import MasterAppView from './views/MasterAppView.vue';
import MasterModuleView from './views/MasterModuleView.vue';
import MasterMenuView from './views/MasterMenuView.vue';
import UserManagementView from './views/UserManagementView.vue';
import AdminReminderView from './views/AdminReminderView.vue';
import ProfileView from './views/ProfileView.vue';
import { useAuth } from './auth';

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
    meta: { menu: '/', title: 'Dashboard Ringkasan QA' },
  },
  {
    path: '/schedules',
    name: 'schedules',
    component: ScheduleView,
    meta: { menu: '/schedules', title: 'Jadwal QA & Alokasi Pengujian' },
  },
  {
    path: '/test-cases',
    name: 'test-cases',
    component: TestCaseView,
    meta: { menu: '/test-cases', title: 'Test Management & Eksekusi' },
  },
  {
    path: '/bugs',
    name: 'bugs',
    component: BugView,
    meta: { menu: '/bugs', title: 'Bug Tracking & Defect Management' },
  },
  {
    path: '/retests',
    name: 'retests',
    component: RetestView,
    meta: { menu: '/retests', title: 'Siklus Verifikasi Retesting' },
  },
  {
    path: '/flows',
    name: 'flows',
    component: FlowView,
    meta: { menu: '/flows', title: 'Flow & Alur Proses Bisnis' },
  },
  {
    path: '/guides',
    name: 'guides',
    component: GuideView,
    meta: { menu: '/guides', title: 'Panduan Penggunaan SISQA' },
  },
  {
    path: '/reports',
    name: 'reports',
    component: ReportView,
    meta: { menu: '/reports', title: 'Pusat Laporan & Ekspor QA' },
  },
  {
    path: '/reports/wa',
    name: 'wa-report',
    component: WaReportView,
    meta: { menu: '/reports/wa', title: 'Report WA — QA Daily Testing & Progress Report' },
  },
  {
    path: '/reports/weekly',
    name: 'weekly-work-report',
    component: WeeklyWorkReportView,
    meta: { menu: '/reports/weekly', title: 'Catatan Pekerjaan Mingguan Pribadi' },
  },
  {
    path: '/activity-logs',
    name: 'activity-logs',
    component: ActivityLogView,
    meta: { menu: '/activity-logs', title: 'Activity Logs & Audit Trail' },
  },
  {
    path: '/master/apps',
    name: 'master-apps',
    component: MasterAppView,
    meta: { menu: '/master/apps', adminOnly: true, title: 'Master Data Aplikasi' },
  },
  {
    path: '/master/modules',
    name: 'master-modules',
    component: MasterModuleView,
    meta: { menu: '/master/modules', adminOnly: true, title: 'Master Data Modul' },
  },
  {
    path: '/master/menus',
    name: 'master-menus',
    component: MasterMenuView,
    meta: { menu: '/master/menus', adminOnly: true, title: 'Master Data Menu' },
  },
  {
    path: '/users',
    name: 'users',
    component: UserManagementView,
    meta: { title: 'Manajemen Pengguna', adminOnly: true },
  },
  {
    path: '/admin/reminders',
    name: 'admin-reminders',
    component: AdminReminderView,
    meta: { title: 'Pengingat & Notifikasi', adminOnly: true },
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

router.beforeEach(async (to) => {
  const token = localStorage.getItem('sisqa_token');

  if (!to.meta.public && !token) {
    return { name: 'login' };
  }

  const { checkAuth, currentUser, hasMenuAccess, getFirstAllowedPath } = useAuth();
  const user = token ? await checkAuth() : currentUser.value;

  if (!to.meta.public && !user) {
    return { name: 'login' };
  }

  if (to.name === 'login' && user) {
    return getFirstAllowedPath(user);
  }

  if (to.meta.adminOnly && user?.role !== 'admin') {
    return getFirstAllowedPath(user);
  }

  if (to.meta.menu && !hasMenuAccess(to.meta.menu)) {
    return getFirstAllowedPath(user);
  }

  document.title = `${to.meta.title || 'SISQA'} — Software Quality Assurance`;
});

export default router;
