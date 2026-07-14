<script setup>
import { useRouter, useRoute } from 'vue-router'
const router = useRouter(); const route = useRoute()
</script>

<template>
  <div class="studio-shell">
    <aside class="studio-side">
      <div class="side-brand">
        <router-link to="/" class="brand-link">◆ AI短剧 Studio</router-link>
      </div>
      <nav class="side-nav">
        <div class="nav-section">项目管理</div>
        <router-link to="/studio" :class="{on:route.path==='/studio'}" class="nav-item">📊 项目仪表盘</router-link>
        <router-link to="/studio/characters" :class="{on:route.path.includes('characters')}" class="nav-item">👤 角色资产库</router-link>
        <router-link to="/studio/episodes" :class="{on:route.path.includes('episodes')}" class="nav-item">🎬 剧集管理</router-link>
        <div class="nav-section" style="margin-top:16px">快捷操作</div>
        <router-link to="/create" class="nav-item">➕ 新建项目</router-link>
        <router-link to="/" class="nav-item">← 返回前台</router-link>
      </nav>
    </aside>
    <main class="studio-body"><router-view /></main>
  </div>
</template>

<style scoped>
.studio-shell{display:flex;min-height:100vh;background:var(--bg-deep)}
.studio-side{width:220px;background:var(--bg-surface);border-right:1px solid var(--border-subtle);display:flex;flex-direction:column;flex-shrink:0}
.side-brand{padding:24px 20px;border-bottom:1px solid var(--border-subtle)}
.brand-link{color:var(--accent);font-weight:700;font-size:17px;letter-spacing:1px}
.side-nav{padding:12px 8px;flex:1;overflow-y:auto}
.nav-section{padding:8px 14px 4px;font-size:10px;color:var(--text-tertiary);text-transform:uppercase;letter-spacing:0.1em}
.nav-item{display:block;padding:8px 14px;border-radius:var(--radius-sm);color:var(--text-secondary);font-size:13px;transition:all var(--transition-fast);margin:1px 0}
.nav-item:hover{color:var(--text-primary);background:var(--bg-hover)}
.nav-item.on{color:var(--accent);background:var(--accent-dim)}
.studio-body{flex:1;padding:28px 32px;overflow-y:auto}
</style>
