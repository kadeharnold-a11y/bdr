import { createRouter, createWebHistory } from 'vue-router'

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
  },
  {
    path: '/new-application',
    name: 'new-application',
    component: () => import('../views/NewApplication.vue'),
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
    path: '/officer/queue',
    name: 'officer-queue',
    component: () => import('../views/officer/OfficerQueue.vue'),
  },
  {
    path: '/officer/application/:id',
    name: 'officer-application',
    component: () => import('../views/officer/ApplicationWorkspace.vue'),
  },
  {
    path: '/',
    redirect: '/signup',
  },
]

export default createRouter({
  history: createWebHistory(),
  routes,
})
