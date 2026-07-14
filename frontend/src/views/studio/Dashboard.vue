<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '../../api'

const router = useRouter()
const projects = ref([])
const loading = ref(true)

onMounted(async () => {
  try { const result = await api.get('/works', { params: { my: 1 } }); projects.value = result.data || result } catch(e) {}
  loading.value = false
})

function formatDate(d) { if (!d) return ''; const t = new Date(d); return t.toLocaleDateString('zh-CN') + ' ' + t.toLocaleTimeString('zh-CN', { hour: '2-digit', minute: '2-digit' }) }
</script>

<template>
  <div class="dash">
    <div class="dash-head"><h1>项目仪表盘</h1><el-button type="primary" @click="router.push('/create')">+ 新建项目</el-button></div>
    <div class="tech-line"></div>

    <div v-loading="loading" v-if="projects.length===0" class="empty-hint">
      <h3>还没有项目</h3><p>点击"新建项目"开始你的第一个AI短剧</p>
    </div>

    <div v-else class="project-grid">
      <div v-for="p in projects" :key="p.id" class="proj-card glass-panel" @click="router.push(`/studio/episodes`)">
        <div class="pc-cover">
          <img v-if="p.output_cover" :src="p.output_cover" class="pc-img"/>
          <span v-else class="pc-icon">🎬</span>
        </div>
        <div class="pc-info">
          <h3>{{ p.title }}</h3>
          <div class="pc-meta"><span>{{ p.style }}</span><span>{{ formatDate(p.created_at) }}</span></div>
          <div class="pc-tags">
            <span class="tech-badge">{{ p.project_type==='series'?'剧集':'单集' }}</span>
            <span class="tech-badge">{{ p.total_episodes||1 }}集</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.dash{padding:8px 0}
.dash-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px}
.dash-head h1{font-size:24px;font-weight:700;color:var(--text-primary)}
.tech-line{height:1px;background:linear-gradient(90deg,var(--border-accent),transparent);margin-bottom:24px}
.empty-hint{text-align:center;padding:60px 0}.empty-hint h3{color:var(--text-primary);margin-bottom:8px}.empty-hint p{color:var(--text-tertiary)}
.project-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:16px}
.proj-card{cursor:pointer;overflow:hidden;padding:0;transition:all var(--transition)}.proj-card:hover{transform:translateY(-2px);border-color:var(--border-accent)!important}
.pc-cover{height:140px;display:flex;align-items:center;justify-content:center;background:var(--bg-elevated)}.pc-img{width:100%;height:100%;object-fit:cover}.pc-icon{font-size:40px;color:var(--text-tertiary)}
.pc-info{padding:14px 16px}.pc-info h3{font-size:15px;color:var(--text-primary);margin-bottom:6px}.pc-meta{display:flex;justify-content:space-between;font-size:11px;color:var(--text-tertiary)}.pc-tags{margin-top:8px;display:flex;gap:6px}
</style>
