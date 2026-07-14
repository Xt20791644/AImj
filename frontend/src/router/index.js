import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  {
    path: '/',
    component: () => import('../layouts/MainLayout.vue'),
    children: [
      { path: '', name: 'Home', component: () => import('../views/Home.vue'), meta: { title: '首页' } },
      { path: 'create', name: 'Create', component: () => import('../views/Create.vue'), meta: { title: '创作工坊', auth: true } },
      { path: 'works', name: 'Works', component: () => import('../views/Works.vue'), meta: { title: '作品广场' } },
      { path: 'profile', name: 'Profile', component: () => import('../views/Profile.vue'), meta: { title: '个人中心', auth: true } },
    ]
  },
  {
    path: '/studio',
    component: () => import('../layouts/StudioLayout.vue'),
    meta: { auth: true },
    children: [
      { path: '', name: 'StudioDashboard', component: () => import('../views/studio/Dashboard.vue'), meta: { title: '项目管理' } },
      { path: 'characters', name: 'StudioCharacters', component: () => import('../views/studio/Characters.vue'), meta: { title: '角色资产库' } },
      { path: 'episodes', name: 'StudioEpisodes', component: () => import('../views/studio/Episodes.vue'), meta: { title: '剧集管理' } },
      { path: 'episodes/:id', name: 'StudioEpisode', component: () => import('../views/studio/Episode.vue'), meta: { title: '分镜编辑' } },
    ]
  },
  {
    path: '/login',
    name: 'Login',
    component: () => import('../views/auth/Login.vue'),
    meta: { title: '登录' }
  },
  {
    path: '/register',
    name: 'Register',
    component: () => import('../views/auth/Register.vue'),
    meta: { title: '注册' }
  },
  {
    path: '/admin',
    component: () => import('../layouts/AdminLayout.vue'),
    meta: { auth: true, admin: true },
    children: [
      { path: '', name: 'AdminDashboard', component: () => import('../views/admin/Dashboard.vue'), meta: { title: '管理后台' } },
      { path: 'users', name: 'AdminUsers', component: () => import('../views/admin/Users.vue'), meta: { title: '用户管理' } },
      { path: 'recharge', name: 'AdminRecharge', component: () => import('../views/admin/Recharge.vue'), meta: { title: '积分充值' } },
    ]
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'NotFound',
    component: () => import('../views/NotFound.vue'),
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior: () => ({ top: 0 })
})

// Navigation guard
router.beforeEach((to, from, next) => {
  document.title = to.meta.title ? `${to.meta.title} - AI短剧` : 'AI短剧'
  const token = localStorage.getItem('token')
  const user = JSON.parse(localStorage.getItem('user') || '{}')

  if (to.meta.auth && !token) {
    return next('/login')
  }
  if (to.meta.admin && (!token || user.role !== 'admin')) {
    return next('/')
  }
  next()
})

export default router
