<script setup>
import { useAuthStore } from '../stores/auth'
import { useRouter, useRoute } from 'vue-router'
const auth = useAuthStore(); const router = useRouter(); const route = useRoute()
function handleLogout() { auth.logout(); router.push('/') }
</script>

<template>
  <div class="app-shell">
    <header class="shell-header">
      <div class="h-left">
        <router-link to="/" class="brand">
          <span class="brand-mark">◆</span>
          <span class="brand-text">AI短剧</span>
        </router-link>
        <nav class="h-nav">
          <router-link to="/create" :class="{ on: route.path.startsWith('/create') }">
            <span class="nav-icon">▸</span>创作工坊
          </router-link>
          <router-link to="/works" :class="{ on: route.path==='/works' }">
            <span class="nav-icon">▹</span>作品广场
          </router-link>
          <router-link to="/studio" :class="{ on: route.path.startsWith('/studio') }">
            <span class="nav-icon">▹</span>创作工作台
          </router-link>
        </nav>
      </div>
      <div class="h-right">
        <template v-if="auth.isLoggedIn">
          <span class="credit-chip"><span class="dot"></span>{{ auth.user?.credits || 0 }}</span>
          <el-dropdown trigger="click">
            <span class="user-chip">{{ auth.user?.name?.[0] }}</span>
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
          <el-button class="signup-btn" size="small" @click="router.push('/register')">注册</el-button>
        </template>
      </div>
    </header>
    <main class="shell-body"><router-view /></main>
  </div>
</template>

<style scoped>
.app-shell{min-height:100vh;display:flex;flex-direction:column}
.shell-header{display:flex;align-items:center;justify-content:space-between;padding:0 28px;height:56px;position:sticky;top:0;z-index:100;background:rgba(6,8,13,0.9);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border-bottom:1px solid var(--border-subtle)}
.h-left{display:flex;align-items:center;gap:36px}
.brand{display:flex;align-items:center;gap:10px;font-weight:700;color:var(--text-primary);text-decoration:none;font-size:18px}
.brand-mark{color:var(--accent);font-size:22px;text-shadow:0 0 16px var(--accent)}
.brand-text{letter-spacing:2px;background:linear-gradient(135deg, var(--accent) 0%, #60a5fa 100%);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.h-nav{display:flex;gap:2px}
.h-nav a{display:flex;align-items:center;gap:6px;padding:6px 14px;border-radius:var(--radius-sm);color:var(--text-tertiary);font-size:13px;font-weight:500;transition:all var(--transition)}
.h-nav a:hover{color:var(--text-secondary);background:var(--bg-hover)}
.h-nav a.on{color:var(--accent);background:var(--accent-dim)}
.nav-icon{font-size:10px}
.h-right{display:flex;align-items:center;gap:14px}
.credit-chip{display:flex;align-items:center;gap:6px;padding:4px 12px;border-radius:var(--radius-sm);font-size:12px;font-weight:600;font-family:var(--font-mono);color:var(--warning);background:var(--warning-dim);border:1px solid rgba(245,158,11,0.15)}
.dot{width:5px;height:5px;border-radius:50%;background:var(--warning);box-shadow:0 0 4px var(--warning)}
.user-chip{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg, var(--accent), var(--accent2));color:#000;font-weight:700;cursor:pointer;font-size:13px;transition:all var(--transition)}
.user-chip:hover{transform:scale(1.05);box-shadow:0 0 16px var(--accent-glow)}
.nav-btn{color:var(--text-tertiary)!important}
.signup-btn{background:linear-gradient(135deg, var(--accent), #0072ff)!important;border:none!important;color:#fff!important;font-weight:600!important;font-size:12px!important;border-radius:var(--radius-sm)!important;padding:6px 16px!important}
.shell-body{flex:1;padding:28px;max-width:1400px;width:100%;margin:0 auto}
</style>
