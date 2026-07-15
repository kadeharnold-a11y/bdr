import { createRouter, createWebHistory } from 'vue-router'
import { getAccessToken } from '../lib/auth'
import { getStaffAccessToken } from '../lib/staffAuth'

const routes = [
  {
    path: '/signup',
    name: 'signup',
    component: () => import('../views/SignUp.vue'),
  },
  {
    path: '/login',
    name: 'login',
    component: () => import('../views/Login.vue'),
  },
  {
    path: '/dashboard',
    name: 'dashboard',
    component: () => import('../views/Dashboard.vue'),
    meta: { requiresCitizen: true },
  },
  {
    path: '/applications',
    name: 'applications',
    component: () => import('../views/Applications.vue'),
    meta: { requiresCitizen: true },
  },
  {
    path: '/certificates',
    name: 'certificates',
    component: () => import('../views/Certificates.vue'),
    meta: { requiresCitizen: true },
  },
  {
    path: '/notifications',
    name: 'notifications',
    component: () => import('../views/Notifications.vue'),
    meta: { requiresCitizen: true },
  },
  {
    path: '/profile',
    name: 'profile',
    component: () => import('../views/Profile.vue'),
    meta: { requiresCitizen: true },
  },
  {
    path: '/new-application',
    name: 'new-application',
    component: () => import('../views/NewApplication.vue'),
    meta: { requiresCitizen: true },
  },
  {
    path: '/track',
    name: 'track',
    component: () => import('../views/TrackApplication.vue'),
  },
  {
    path: '/officer',
    redirect: '/officer/queue',
  },
  {
    path: '/officer/login',
    name: 'officer-login',
    component: () => import('../views/officer/OfficerLogin.vue'),
  },
  {
    path: '/officer/queue',
    name: 'officer-queue',
    component: () => import('../views/officer/OfficerQueue.vue'),
    meta: { requiresStaff: true },
  },
  {
    path: '/officer/application/:id',
    name: 'officer-application',
    component: () => import('../views/officer/ApplicationWorkspace.vue'),
    meta: { requiresStaff: true },
  },
  {
    path: '/',
    redirect: '/signup',
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to) => {
  if (to.meta.requiresCitizen && !getAccessToken()) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }
  if (to.meta.requiresStaff && !getStaffAccessToken()) {
    return { name: 'officer-login', query: { redirect: to.fullPath } }
  }
})

export default router
