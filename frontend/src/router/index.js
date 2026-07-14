import { createRouter, createWebHistory } from 'vue-router'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/auth', component: () => import('../views/Auth.vue') },
    { path: '/', component: () => import('../layouts/MainLayout.vue'), meta: { auth: true }, children: [
      { path: '', component: () => import('../views/Home.vue') }
    ]}
  ]
})

router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token')
  if (to.meta.auth && !token) return next('/auth')
  if (to.path === '/auth' && token) return next('/')
  next()
})

export default router
