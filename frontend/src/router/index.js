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
    component: () => import('../views/SignUp.vue'), // placeholder until login page is built
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
