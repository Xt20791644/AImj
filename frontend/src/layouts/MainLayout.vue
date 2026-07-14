<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
const api = axios.create({ baseURL: '/api' })
api.interceptors.request.use(c => { const t = localStorage.getItem('token'); if (t) c.headers.Authorization = 'Bearer ' + t; return c })
const activeTab = ref('create')
const user = ref(JSON.parse(localStorage.getItem('user')||'null'))
const token = computed(() => localStorage.getItem('token'))
const isLoggedIn = computed(() => !!token.value)
const creditTxs = ref([])
onMounted(async () => {
  if (!localStorage.getItem('token')) return
  try {
    const { data } = await api.get('/credits/transactions', { headers: { Authorization: 'Bearer ' + localStorage.getItem('token') } })
    creditTxs.value = data.data || data
  } catch(e) {}
})

const tabs = computed(() => [
  { key:'create', label:'快速创作', icon:'⚡' },
  { key:'studio', label:'短剧Studio', icon:'🎬' },
  { key:'assets', label:'我的资产', icon:'📦' },
])

function logout() { localStorage.removeItem('token'); localStorage.removeItem('user'); user.value=null; window.location.reload() }
</script>

<template>
  <div class="app-layout">
    <aside class="sidebar">
      <div class="side-brand">◆ AI短剧</div>
      <div class="side-tabs">
        <div v-for="t in tabs" :key="t.key" class="side-tab" :class="{ active: activeTab===t.key }" @click="activeTab=t.key">
          <span class="tab-icon">{{ t.icon }}</span><span class="tab-label">{{ t.label }}</span>
        </div>
        <div v-if="isLoggedIn" class="side-tab" :class="{ active: activeTab==='works' }" @click="activeTab='works'">
          <span class="tab-icon">🎬</span><span class="tab-label">我的作品</span>
        </div>
      </div>
      <div class="side-foot">
        <template v-if="isLoggedIn">
          <div class="side-user"><span>{{ user?.name }}</span><span class="credits">🪙 {{ user?.credits||0 }}</span></div>
          <a @click="logout" class="side-link">退出</a>
        </template>
        <router-link v-else to="/auth" class="side-link">登录 / 注册</router-link>
      </div>
    </aside>
    <main class="main-area">
      <div v-if="activeTab==='works'&&isLoggedIn" class="works-section">
        <h2>我的作品</h2><div class="tech-line"></div>
        <div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap">
          <span class="filter-chip" :class="{on:workFilter==='all'}" @click="workFilter='all'">全部</span>
          <span class="filter-chip" :class="{on:workFilter==='original'}" @click="workFilter='original'">原创</span>
          <span class="filter-chip" :class="{on:workFilter==='remake'}" @click="workFilter='remake'">爆款复刻</span>
          <span class="filter-chip" :class="{on:workFilter==='ad'}" @click="workFilter='ad'">剧情广告</span>
          <span class="filter-chip" :class="{on:workFilter==='studio'}" @click="workFilter='studio'">短剧Studio</span>
        </div>
        <WorksList :filter="workFilter" />
        <div v-if="creditTxs.length" style="margin-top:32px">
          <h3 style="font-size:16px;color:var(--text-primary);margin-bottom:12px">积分变动记录</h3>
          <div class="tech-line"></div>
          <div v-for="tx in creditTxs.slice(0,20)" :key="tx.id" style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--border-subtle);font-size:12px;gap:12px">
            <span style="color:var(--text-tertiary);white-space:nowrap">{{ new Date(tx.created_at).toLocaleString('zh-CN') }}</span>
            <span :style="{color:tx.amount>0?'var(--success)':'var(--danger)','font-weight':600}">{{ tx.amount>0?'+':'' }}{{ tx.amount }}</span>
            <span style="color:var(--text-secondary);flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ tx.description }}</span>
          </div>
        </div>
      </div>
      <router-view v-else />
    </main>
  </div>
</template>

<script>
import { ref, onMounted, defineComponent, h } from 'vue'
const WorksList = defineComponent({
  props: ['filter'],
  setup(props) {
    const works = ref([]); const loading = ref(true)
    onMounted(async () => {
      try { const {data} = await api.get('/works'); works.value = Array.isArray(data)?data:data.data||[] } catch(e) {}
      loading.value = false
    })
    return () => {
      const filtered = works.value.filter(w => {
        const c = w.content || ''
        if (props.filter==='remake') return c.includes('爆款复刻')
        if (props.filter==='ad') return c.includes('剧情广告')
        if (props.filter==='studio') return c.includes('短剧Studio')
        if (props.filter==='original') return !c.includes('爆款复刻')&&!c.includes('剧情广告')&&!c.includes('短剧Studio')
        return true
      })
      if (loading.value) return h('div',{style:'textAlign:center;padding:40px;color:var(--text-tertiary)'},'加载中...')
      if (!filtered.length) return h('div',{style:'textAlign:center;padding:40px;color:var(--text-tertiary)'},'暂无作品')
      return h('div',{class:'work-grid'}, filtered.map(w => h('div',{class:'work-card glass-panel',onClick:()=>{viewing.value=w;showVideo.value=true}}, [
        h('div',{class:'wc-cover'}, [
          w.output_cover ? h('img',{src:w.output_cover,class:'wc-img'}) : h('span',{class:'wc-icon'},'🎬'),
          h('span',{class:'wc-status '+w.status}, w.status==='completed'?'✅':w.status==='failed'?'❌':w.status==='processing'?'⏳':'📝')
        ]),
        h('div',{class:'wc-info'}, [h('h4',w.title), h('div',{class:'wc-meta'}, [h('span',new Date(w.created_at).toLocaleDateString('zh-CN')), h('span',(w.duration||0)+'秒')])])
      ])))
    }
  }
})
const workFilter = ref('all'); const viewing = ref(null); const showVideo = ref(false)
</script>

<style scoped>
.app-layout{display:flex;min-height:100vh;background:var(--bg-deep)}
.sidebar{width:200px;background:var(--bg-surface);border-right:1px solid var(--border-subtle);display:flex;flex-direction:column;flex-shrink:0}
.side-brand{padding:24px 20px;font-size:18px;font-weight:700;color:var(--accent);letter-spacing:2px;border-bottom:1px solid var(--border-subtle)}
.side-tabs{flex:1;padding:12px 8px;display:flex;flex-direction:column;gap:2px}
.side-tab{padding:10px 14px;border-radius:8px;cursor:pointer;color:var(--text-tertiary);font-size:14px;display:flex;align-items:center;gap:8px;transition:all var(--transition)}.side-tab:hover{color:var(--text-primary);background:var(--bg-hover)}.side-tab.active{color:var(--accent);background:var(--accent-dim)}.tab-icon{font-size:16px}
.side-foot{padding:16px 20px;border-top:1px solid var(--border-subtle)}.side-user{display:flex;flex-direction:column;gap:4px;margin-bottom:8px;font-size:13px;color:var(--text-secondary)}.credits{color:var(--warning);font-size:12px}.side-link{color:var(--text-tertiary);font-size:13px;cursor:pointer}.side-link:hover{color:var(--accent)}
.main-area{flex:1;overflow-y:auto}
.works-section{padding:24px 0;max-width:900px;margin:0 auto}.works-section h2{font-size:24px;font-weight:700;color:var(--text-primary)}.tech-line{height:1px;background:linear-gradient(90deg,var(--border-accent),transparent);margin:12px 0 20px}
.filter-chip{padding:6px 14px;border-radius:20px;font-size:13px;cursor:pointer;border:1px solid var(--border-strong);color:var(--text-secondary)}.filter-chip.on{border-color:var(--accent);color:var(--accent);background:var(--accent-dim)}
.work-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px}
.work-card{cursor:pointer;overflow:hidden;padding:0;transition:all var(--transition)}.work-card:hover{transform:translateY(-2px);border-color:var(--border-accent)!important}
.wc-cover{height:160px;display:flex;align-items:center;justify-content:center;background:var(--bg-elevated);position:relative}.wc-img{width:100%;height:100%;object-fit:cover}.wc-icon{font-size:40px;color:var(--text-tertiary)}
.wc-status{position:absolute;top:8px;left:8px;font-size:11px;padding:2px 6px;border-radius:4px;background:rgba(0,0,0,0.7)}.wc-status.completed{color:var(--success)}.wc-status.failed{color:var(--danger)}.wc-status.processing{color:var(--warning)}
.wc-info{padding:12px}.wc-info h4{font-size:14px;color:var(--text-primary);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.wc-meta{display:flex;justify-content:space-between;font-size:11px;color:var(--text-tertiary);margin-top:4px}
</style>
