<script setup>
import { useAuthStore } from '../stores/auth'
import { useRouter, useRoute } from 'vue-router'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()
function handleLogout() { auth.logout(); router.push('/') }
</script>

<template>
  <div class="main-app">
    <header class="main-header">
      <div class="header-left">
        <router-link to="/" class="logo">
          <span class="logo-icon">◆</span>
          <span class="logo-text">AI短剧</span>
        </router-link>
        <nav class="nav-links">
          <router-link to="/create" :class="{ active: route.path.startsWith('/create') }">创作工坊</router-link>
          <router-link to="/works" :class="{ active: route.path==='/works' }">作品广场</router-link>
        </nav>
      </div>
      <div class="header-right">
        <template v-if="auth.isLoggedIn">
          <span class="credit-badge">◆ {{ auth.user?.credits || 0 }}</span>
          <el-dropdown trigger="click">
            <span class="user-avatar">{{ auth.user?.name?.[0] }}</span>
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item @click="router.push('/profile')">个人中心</el-dropdown-item>
                <el-dropdown-item v-if="auth.isAdmin" @click="router.push('/admin')">管理后台</el-dropdown-item>
                <el-dropdown-item divided @click="handleLogout">退出</el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </template>
        <template v-else>
          <el-button text class="nav-btn" @click="router.push('/login')">登录</el-button>
          <el-button class="btn-primary" size="small" @click="router.push('/register')">注册</el-button>
        </template>
      </div>
    </header>
    <main class="main-content"><router-view /></main>
  </div>
</template>

<style scoped>
.main-app { min-height:100vh; display:flex; flex-direction:column; }
.main-header { display:flex; align-items:center; justify-content:space-between; padding:0 32px; height:56px; position:sticky; top:0; z-index:100; background:rgba(10,12,20,0.92); backdrop-filter:blur(16px); border-bottom:1px solid var(--border); }
.header-left { display:flex; align-items:center; gap:40px; }
.logo { display:flex; align-items:center; gap:8px; font-size:20px; font-weight:700; color:var(--text-bright); text-decoration:none; }
.logo-icon { color:var(--accent); font-size:24px; text-shadow:0 0 12px var(--accent); }
.logo-text { letter-spacing:2px; background:linear-gradient(135deg,var(--accent),var(--text-bright)); -webkit-background-clip:text; -webkit-text-fill-color:transparent; }
.nav-links { display:flex; gap:4px; }
.nav-links a { padding:6px 16px; border-radius:6px; color:var(--text-dim); font-size:14px; transition:all .2s; }
.nav-links a:hover,.nav-links a.active { color:var(--accent); background:rgba(0,229,255,0.08); }
.header-right { display:flex; align-items:center; gap:16px; }
.credit-badge { color:var(--warning); font-size:13px; padding:4px 10px; border-radius:4px; background:rgba(255,165,2,0.1); border:1px solid rgba(255,165,2,0.2); }
.user-avatar { width:34px; height:34px; border-radius:50%; display:flex; align-items:center; justify-content:center; background:linear-gradient(135deg,var(--accent),var(--accent2)); color:#000; font-weight:700; cursor:pointer; font-size:14px; }
.nav-btn { color:var(--text-dim)!important; }
.btn-primary { background:linear-gradient(135deg,var(--accent),#0099ff)!important; border:none!important; color:#000!important; font-weight:600!important; }
.main-content { flex:1; padding:32px; max-width:1400px; width:100%; margin:0 auto; }
</style>
