import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  { path: '/', component: () => import('../views/Home.vue'), meta: { title: 'AI短剧' } },
]

const router = createRouter({ history: createWebHistory(), routes })
export default router
