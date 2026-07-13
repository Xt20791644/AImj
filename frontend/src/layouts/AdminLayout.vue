<script setup>
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
const router = useRouter()
const auth = useAuthStore()
</script>

<template>
  <div class="admin-shell">
    <aside class="admin-side">
      <div class="side-head">
        <router-link to="/" class="side-logo">◆ AI短剧</router-link>
        <span class="tech-badge">ADMIN</span>
      </div>
      <nav class="side-nav">
        <router-link to="/admin" :class="{ on: $route.path === '/admin' }">📊 数据概览</router-link>
        <router-link to="/admin/users" :class="{ on: $route.path === '/admin/users' }">👥 用户管理</router-link>
        <router-link to="/admin/recharge" :class="{ on: $route.path === '/admin/recharge' }">💎 积分充值</router-link>
      </nav>
      <div class="side-foot">
        <router-link to="/">← 返回前台</router-link>
      </div>
    </aside>
    <main class="admin-body"><router-view /></main>
  </div>
</template>

<style scoped>
.admin-shell { display:flex; min-height:100vh; background:var(--bg-deep) }
.admin-side { width:220px; background:var(--bg-surface); border-right:1px solid var(--border-subtle); display:flex; flex-direction:column; flex-shrink:0 }
.side-head { padding:20px 20px 16px; border-bottom:1px solid var(--border-subtle); display:flex; align-items:center; justify-content:space-between }
.side-logo { color:var(--accent); font-weight:700; font-size:17px; letter-spacing:1px }
.side-nav { flex:1; padding:12px 8px; display:flex; flex-direction:column; gap:2px }
.side-nav a { padding:10px 14px; border-radius:var(--radius-sm); color:var(--text-tertiary); font-size:14px; transition:all var(--transition-fast) }
.side-nav a:hover { color:var(--text-primary); background:var(--bg-hover) }
.side-nav a.on { color:var(--accent); background:var(--accent-dim) }
.side-foot { padding:16px 20px; border-top:1px solid var(--border-subtle) }
.side-foot a { color:var(--text-tertiary); font-size:13px }
.side-foot a:hover { color:var(--text-primary) }
.admin-body { flex:1; padding:28px 32px; overflow-y:auto }
</style>
