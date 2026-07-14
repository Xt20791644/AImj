import { createRouter, createWebHistory } from 'vue-router'
export default createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/auth', component: () => import('../views/Auth.vue') },
    { path: '/', component: () => import('../layouts/MainLayout.vue'), children: [
      { path: '', component: () => import('../views/Home.vue') }
    ]}
  ]
})
