<script setup>
import { ref, computed } from 'vue'
const activeTab = ref('create')
const tabs = [
  { key: 'create', label: '快速创作', icon: '⚡' },
  { key: 'studio', label: '短剧Studio', icon: '🎬' },
  { key: 'assets', label: '我的资产', icon: '📦' },
]
</script>

<template>
  <div class="app-layout">
    <aside class="sidebar">
      <div class="side-brand">◆ AI短剧</div>
      <div class="side-tabs">
        <div v-for="t in tabs" :key="t.key" class="side-tab" :class="{ active: activeTab === t.key }" @click="activeTab = t.key">
          <span class="tab-icon">{{ t.icon }}</span><span class="tab-label">{{ t.label }}</span>
        </div>
      </div>
      <div class="side-foot">
        <router-link to="/login" class="side-link">登录</router-link>
      </div>
    </aside>
    <main class="main-area">
      <router-view />
    </main>
  </div>
</template>

<style scoped>
.app-layout{display:flex;min-height:100vh;background:var(--bg-deep)}
.sidebar{width:200px;background:var(--bg-surface);border-right:1px solid var(--border-subtle);display:flex;flex-direction:column;flex-shrink:0}
.side-brand{padding:24px 20px;font-size:18px;font-weight:700;color:var(--accent);letter-spacing:2px;border-bottom:1px solid var(--border-subtle)}
.side-tabs{flex:1;padding:12px 8px;display:flex;flex-direction:column;gap:2px}
.side-tab{padding:10px 14px;border-radius:8px;cursor:pointer;color:var(--text-tertiary);font-size:14px;display:flex;align-items:center;gap:8px;transition:all var(--transition)}.side-tab:hover{color:var(--text-primary);background:var(--bg-hover)}.side-tab.active{color:var(--accent);background:var(--accent-dim)}.tab-icon{font-size:16px}
.side-foot{padding:16px 20px;border-top:1px solid var(--border-subtle)}.side-link{color:var(--text-tertiary);font-size:13px}
.main-area{flex:1;overflow-y:auto}
</style>
