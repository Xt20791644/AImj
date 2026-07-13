<script setup>
import { ref, onMounted } from 'vue'
import { useWorkStore } from '../stores/work'
import api from '../api'

const workStore = useWorkStore()
const showDetail = ref(false)
const viewing = ref(null)

onMounted(() => workStore.fetchWorks())

function formatDate(d) { if (!d) return ''; const t = new Date(d); return t.toLocaleDateString('zh-CN') + ' ' + t.toLocaleTimeString('zh-CN', {hour:'2-digit',minute:'2-digit',second:'2-digit'}) }
function styleLabel(s) { const m={realistic:'真人写实',anime:'日系动画','3d':'3D动画',cyberpunk:'赛博朋克'}; return m[s]||s }

async function openWork(work) {
  try {
    const result = await api.get(`/works/${work.id}`)
    viewing.value = result
    showDetail.value = true
  } catch (e) {}
}
</script>

<template>
  <div class="works-page">
    <div class="page-hero"><h1>作品广场</h1><p class="hero-sub">浏览社区 AI 创作的短剧作品</p></div>
    <el-empty v-if="!workStore.loading && workStore.works.length===0" description="还没有作品，快去创作第一个吧！" />
    <el-row :gutter="16" v-loading="workStore.loading">
      <el-col :span="6" v-for="work in workStore.works" :key="work.id">
        <div class="work-card glass-panel" @click="openWork(work)">
          <div class="wc-cover">
            <img v-if="work.output_cover" :src="work.output_cover" class="wc-img" />
            <span v-else class="wc-icon">🎬</span>
            <div class="wc-duration" v-if="work.duration">{{ work.duration }}s</div>
          </div>
          <div class="wc-info">
            <h3>{{ work.title }}</h3>
            <div class="wc-meta"><span>{{ formatDate(work.created_at) }}</span><span>{{ work.views||0 }} 次播放</span></div>
          </div>
        </div>
      </el-col>
    </el-row>

    <!-- Detail Dialog -->
    <el-dialog v-model="showDetail" :title="viewing?.title" width="700px" top="5vh" @close="viewing=null">
      <div v-if="viewing" class="detail">
        <div class="detail-video">
          <video v-if="viewing.output_video" :src="viewing.output_video" controls style="width:100%;max-height:500px;border-radius:var(--radius)" />
          <div v-else class="no-video">🎬 视频暂不可用</div>
        </div>
        <div class="detail-meta"><span>风格: {{ styleLabel(viewing.style) }}</span><span>时长: {{ viewing.duration||0 }}秒</span><span>{{ formatDate(viewing.created_at) }}</span></div>
      </div>
    </el-dialog>
  </div>
</template>

<style scoped>
.works-page{max-width:1200px;margin:0 auto;padding:20px}
.page-hero{margin-bottom:24px}.page-hero h1{font-size:28px;font-weight:800;color:var(--text-primary)}.hero-sub{color:var(--text-tertiary);font-size:14px;margin-top:4px}
.work-card{cursor:pointer;overflow:hidden;border-radius:var(--radius);transition:all var(--transition);padding:0}
.work-card:hover{transform:translateY(-2px);border-color:var(--border-accent)!important}
.wc-cover{height:240px;display:flex;align-items:center;justify-content:center;background:var(--bg-elevated);position:relative;overflow:hidden}
.wc-img{width:100%;height:100%;object-fit:cover}
.wc-icon{font-size:48px;color:var(--text-tertiary)}
.wc-duration{position:absolute;bottom:8px;right:8px;padding:2px 8px;border-radius:4px;background:rgba(0,0,0,0.7);color:#fff;font-size:12px;font-family:var(--font-mono)}
.wc-info{padding:14px 16px}
.wc-info h3{font-size:14px;color:var(--text-primary);margin-bottom:6px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.wc-meta{display:flex;justify-content:space-between;font-size:11px;color:var(--text-tertiary)}
.detail{padding:0}.detail-video{margin-bottom:16px}.no-video{height:300px;display:flex;align-items:center;justify-content:center;background:var(--bg-elevated);border-radius:var(--radius);color:var(--text-tertiary);font-size:24px}
.detail-meta{display:flex;gap:16px;font-size:12px;color:var(--text-tertiary);margin-bottom:12px;padding-bottom:12px;border-bottom:1px solid var(--border-subtle)}
.detail-section{margin-top:12px}.ds-label{display:block;font-size:12px;color:var(--accent);font-weight:600;margin-bottom:6px}
.ds-text{background:var(--bg-elevated);padding:12px;border-radius:var(--radius-sm);font-size:12px;color:var(--text-secondary);white-space:pre-wrap;max-height:200px;overflow-y:auto}
</style>
