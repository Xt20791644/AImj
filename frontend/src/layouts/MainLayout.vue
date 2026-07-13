<script setup>
import { useAuthStore } from '../stores/auth'
import { useRouter, useRoute } from 'vue-router'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

function handleLogout() {
  auth.logout()
  router.push('/')
}
</script>

<template>
  <el-container class="main-layout">
    <el-header class="header">
      <div class="header-left">
        <router-link to="/" class="logo">🎬 AI短剧</router-link>
        <el-menu mode="horizontal" :default-active="route.path" router class="nav-menu">
          <el-menu-item index="/create">创作工坊</el-menu-item>
          <el-menu-item index="/works">作品广场</el-menu-item>
        </el-menu>
      </div>
      <div class="header-right">
        <template v-if="auth.isLoggedIn">
          <span class="credits">🎯 {{ auth.user?.credits || 0 }} 积分</span>
          <el-dropdown>
            <span class="user-info">
              {{ auth.user?.name }} <el-icon><ArrowDown /></el-icon>
            </span>
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item @click="router.push('/profile')">个人中心</el-dropdown-item>
                <el-dropdown-item v-if="auth.isAdmin" @click="router.push('/admin')">管理后台</el-dropdown-item>
                <el-dropdown-item divided @click="handleLogout">退出登录</el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </template>
        <template v-else>
          <el-button text @click="router.push('/login')">登录</el-button>
          <el-button type="primary" @click="router.push('/register')">免费注册</el-button>
        </template>
      </div>
    </el-header>
    <el-main>
      <router-view />
    </el-main>
    <el-footer class="footer">
      <p>© 2026 AI短剧 — AI驱动的短剧创作平台</p>
    </el-footer>
  </el-container>
</template>

<style scoped>
.main-layout { min-height: 100vh; display: flex; flex-direction: column; }
.header { display: flex; align-items: center; justify-content: space-between; padding: 0 24px; background: #fff; box-shadow: 0 1px 4px rgba(0,0,0,0.08); height: 60px; position: sticky; top: 0; z-index: 100; }
.header-left { display: flex; align-items: center; gap: 32px; }
.logo { font-size: 20px; font-weight: 700; color: #409eff; }
.nav-menu { border-bottom: none !important; }
.nav-menu .el-menu-item { height: 60px; line-height: 60px; }
.header-right { display: flex; align-items: center; gap: 16px; }
.credits { color: #e6a23c; font-weight: 600; font-size: 14px; }
.user-info { cursor: pointer; color: #303133; }
.footer { text-align: center; padding: 20px; color: #909399; font-size: 13px; }
</style>
