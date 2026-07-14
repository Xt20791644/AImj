<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import axios from 'axios'

const api = axios.create({ baseURL: '/api' })
api.interceptors.request.use(c => { const t = localStorage.getItem('token'); if (t) c.headers.Authorization = 'Bearer ' + t; return c })

const activeTab = ref('create')
const user = ref(JSON.parse(localStorage.getItem('user') || 'null'))
const token = computed(() => localStorage.getItem('token'))
const isLoggedIn = computed(() => !!token.value)
const works = ref([]); const worksLoading = ref(false)
const creditTxs = ref([]); const workFilter = ref('all')
const viewing = ref(null); const showVideo = ref(false)
const worksKey = ref(0) // force re-render

// 全局监听：外部可触发切换 tab
window.switchToWorks = () => { activeTab.value = 'works'; loadWorks(); loadCredits() }

async function loadWorks() {
  worksLoading.value = true
  try { const { data } = await api.get('/works'); works.value = Array.isArray(data) ? data : data.data || [] } catch (e) {}
  worksLoading.value = false
}
async function loadCredits() {
  try { const { data } = await api.get('/credits/transactions'); creditTxs.value = data.data || data } catch (e) {}
}

onMounted(() => { if (isLoggedIn.value) { loadWorks(); loadCredits() } })

watch(activeTab, (tab) => { if (tab === 'works') { worksKey.value++; loadWorks(); loadCredits() } })

function logout() { localStorage.removeItem('token'); localStorage.removeItem('user'); user.value = null; window.location.reload() }
function formatDate(d) { if (!d) return ''; const t = new Date(d); return t.toLocaleDateString('zh-CN') + ' ' + t.toLocaleTimeString('zh-CN', { hour: '2-digit', minute: '2-digit' }) }

const filteredWorks = computed(() => {
  if (workFilter.value === 'all') return works.value
  return works.value.filter(w => {
    const c = w.content || ''
    if (workFilter.value === 'remake') return c.includes('爆款复刻')
    if (workFilter.value === 'ad') return c.includes('剧情广告')
    if (workFilter.value === 'studio') return c.includes('短剧Studio')
    if (workFilter.value === 'original') return !c.includes('爆款复刻') && !c.includes('剧情广告') && !c.includes('短剧Studio')
    return true
  })
})
</script>

<template>
  <div class="app-layout">
    <aside class="sidebar">
      <div class="side-brand">◆ AI短剧</div>
      <div class="side-tabs">
        <div class="side-tab" :class="{ active: activeTab === 'create' }" @click="activeTab = 'create'">⚡ 快速创作</div>
        <div class="side-tab" :class="{ active: activeTab === 'studio' }" @click="activeTab = 'studio'">🎬 短剧Studio</div>
        <div class="side-tab" :class="{ active: activeTab === 'assets' }" @click="activeTab = 'assets'">📦 我的资产</div>
        <div v-if="isLoggedIn" class="side-tab" :class="{ active: activeTab === 'works' }" @click="activeTab = 'works'">🎬 我的作品</div>
      </div>
      <div class="side-foot">
        <template v-if="isLoggedIn">
          <div class="side-user"><span>{{ user?.name }}</span><span class="credits">🪙 {{ user?.credits || 0 }}</span></div>
          <a @click="logout" class="side-link">退出</a>
        </template>
        <router-link v-else to="/auth" class="side-link">登录 / 注册</router-link>
      </div>
    </aside>

    <main class="main-area">
      <!-- 快速创作 -->
      <router-view v-if="activeTab === 'create'" />

      <!-- 短剧Studio -->
      <div v-else-if="activeTab === 'studio'" class="placeholder"><h2>短剧Studio</h2><p>开发中</p></div>

      <!-- 我的资产 -->
      <div v-else-if="activeTab === 'assets'" class="placeholder"><h2>我的资产</h2><p>开发中</p></div>

      <!-- 我的作品 -->
      <div v-else-if="activeTab === 'works'" class="works-section">
        <h2>我的作品</h2>
        <div class="tech-line"></div>
        <div class="filter-bar">
          <span class="fc" :class="{ on: workFilter === 'all' }" @click="workFilter = 'all'">全部</span>
          <span class="fc" :class="{ on: workFilter === 'original' }" @click="workFilter = 'original'">原创</span>
          <span class="fc" :class="{ on: workFilter === 'remake' }" @click="workFilter = 'remake'">爆款复刻</span>
          <span class="fc" :class="{ on: workFilter === 'ad' }" @click="workFilter = 'ad'">剧情广告</span>
          <span class="fc" :class="{ on: workFilter === 'studio' }" @click="workFilter = 'studio'">短剧Studio</span>
        </div>

        <div v-if="filteredWorks.length === 0" class="empty">暂无作品</div>
        <div v-else class="work-grid">
          <div v-for="w in filteredWorks" :key="w.id" class="w-card glass-panel" @click="viewing = w; showVideo = true">
            <div class="wc-cover">
              <img v-if="w.output_cover" :src="w.output_cover" class="wc-img" />
              <span v-else class="wc-icon">🎬</span>
              <span class="wc-status" :class="w.status">{{ w.status === 'completed' ? '✅完成' : w.status === 'failed' ? '❌失败' : w.status === 'processing' ? '⏳生成中' : '📝等待' }}</span>
            </div>
            <div class="wc-info">
              <h4>{{ w.title?.substring(0, 20) }}</h4>
              <div class="wc-meta"><span>{{ formatDate(w.created_at) }}</span><span>{{ w.duration || 0 }}秒</span></div>
            </div>
          </div>
        </div>

        <!-- Credit history -->
        <div v-if="creditTxs.length" style="margin-top: 32px">
          <h3>积分变动记录</h3>
          <div class="tech-line"></div>
          <div v-for="tx in creditTxs.slice(0, 20)" :key="tx.id" class="tx-row">
            <span class="tx-time">{{ formatDate(tx.created_at) }}</span>
            <span :style="{ color: tx.amount > 0 ? 'var(--success)' : 'var(--danger)', fontWeight: 600 }">{{ tx.amount > 0 ? '+' : '' }}{{ tx.amount }}</span>
            <span class="tx-desc">{{ tx.description }}</span>
          </div>
        </div>
      </div>

      <!-- Video Dialog -->
      <el-dialog v-model="showVideo" :title="viewing?.title?.substring(0, 30)" width="700px" top="5vh" @close="viewing = null">
        <video v-if="viewing?.output_video" :src="viewing.output_video" controls style="width: 100%; max-height: 450px; border-radius: 8px" />
        <div v-else style="height: 300px; display: flex; align-items: center; justify-content: center; background: var(--bg-elevated); border-radius: 8px; color: var(--text-tertiary); font-size: 18px">视频暂不可用</div>
      </el-dialog>
    </main>
  </div>
</template>

<style scoped>
.app-layout { display: flex; min-height: 100vh; background: var(--bg-deep) }
.sidebar { width: 200px; background: var(--bg-surface); border-right: 1px solid var(--border-subtle); display: flex; flex-direction: column; flex-shrink: 0 }
.side-brand { padding: 24px 20px; font-size: 18px; font-weight: 700; color: var(--accent); letter-spacing: 2px; border-bottom: 1px solid var(--border-subtle) }
.side-tabs { flex: 1; padding: 12px 8px; display: flex; flex-direction: column; gap: 2px }
.side-tab { padding: 10px 14px; border-radius: 8px; cursor: pointer; color: var(--text-tertiary); font-size: 14px; display: flex; align-items: center; gap: 8px; transition: all var(--transition) }
.side-tab:hover { color: var(--text-primary); background: var(--bg-hover) }
.side-tab.active { color: var(--accent); background: var(--accent-dim) }
.side-foot { padding: 16px 20px; border-top: 1px solid var(--border-subtle) }
.side-user { display: flex; flex-direction: column; gap: 4px; margin-bottom: 8px; font-size: 13px; color: var(--text-secondary) }
.credits { color: var(--warning); font-size: 12px }
.side-link { color: var(--text-tertiary); font-size: 13px; cursor: pointer }
.side-link:hover { color: var(--accent) }
.main-area { flex: 1; overflow-y: auto; padding: 24px 32px }
.placeholder { text-align: center; padding: 60px 20px; color: var(--text-tertiary) }
.placeholder h2 { color: var(--text-primary); margin-bottom: 8px }

.works-section { max-width: 1000px }
.works-section h2 { font-size: 24px; font-weight: 700; color: var(--text-primary) }
.works-section h3 { font-size: 16px; color: var(--text-primary); margin-bottom: 12px }
.tech-line { height: 1px; background: linear-gradient(90deg, var(--border-accent), transparent); margin: 12px 0 20px }
.filter-bar { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 20px }
.fc { padding: 6px 14px; border-radius: 20px; font-size: 13px; cursor: pointer; border: 1px solid var(--border-strong); color: var(--text-secondary) }
.fc.on { border-color: var(--accent); color: var(--accent); background: var(--accent-dim) }
.empty { text-align: center; padding: 40px; color: var(--text-tertiary) }

.work-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 12px }
.w-card { cursor: pointer; overflow: hidden; padding: 0; transition: all var(--transition) }
.w-card:hover { transform: translateY(-2px); border-color: var(--border-accent) !important }
.wc-cover { height: 160px; display: flex; align-items: center; justify-content: center; background: var(--bg-elevated); position: relative }
.wc-img { width: 100%; height: 100%; object-fit: cover }
.wc-icon { font-size: 40px; color: var(--text-tertiary) }
.wc-status { position: absolute; top: 8px; left: 8px; font-size: 11px; padding: 2px 6px; border-radius: 4px; background: rgba(0, 0, 0, 0.7) }
.wc-status.completed { color: var(--success) }
.wc-status.failed { color: var(--danger) }
.wc-status.processing { color: var(--warning) }
.wc-status.pending { color: var(--text-tertiary) }
.wc-info { padding: 12px }
.wc-info h4 { font-size: 14px; color: var(--text-primary); overflow: hidden; text-overflow: ellipsis; white-space: nowrap }
.wc-meta { display: flex; justify-content: space-between; font-size: 11px; color: var(--text-tertiary); margin-top: 4px }

.tx-row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid var(--border-subtle); font-size: 12px; gap: 12px }
.tx-time { color: var(--text-tertiary); white-space: nowrap }
.tx-desc { color: var(--text-secondary); flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap }
</style>
