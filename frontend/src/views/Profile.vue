<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '../stores/auth'
import { useCreditStore } from '../stores/credit'
import { useRouter } from 'vue-router'
import api from '../api'

const auth = useAuthStore()
const creditStore = useCreditStore()
const router = useRouter()

const myWorks = ref([])
const worksLoading = ref(false)
const viewing = ref(null)
const showDetail = ref(false)

onMounted(() => {
  if (!auth.isLoggedIn) { router.push('/login'); return }
  creditStore.fetchBalance()
  creditStore.fetchTransactions()
  fetchMyWorks()
})

async function fetchMyWorks() {
  worksLoading.value = true
  try { const result = await api.get('/works', { params: { my: 1 } }); myWorks.value = result.data || result } catch (e) {}
  worksLoading.value = false
}

async function playWork(work) {
  try { const r = await api.get(`/works/${work.id}`); viewing.value = r; showDetail.value = true } catch (e) {}
}

function formatDate(d) { if (!d) return ''; const t = new Date(d); return t.toLocaleDateString('zh-CN') + ' ' + t.toLocaleTimeString('zh-CN', {hour:'2-digit',minute:'2-digit',second:'2-digit'}) }
</script>

<template>
  <div class="profile-page">
    <div class="page-hero"><h1>个人中心</h1></div>

    <el-row :gutter="16">
      <el-col :span="8">
        <div class="glass-panel info-card">
          <div class="avatar-wrap"><div class="avatar">{{ auth.user?.name?.[0] }}</div></div>
          <h2>{{ auth.user?.name }}</h2>
          <p class="email">{{ auth.user?.email }}</p>
        </div>
        <div class="glass-panel credit-card">
          <div class="cc-head">我的积分</div>
          <div class="cc-num">{{ creditStore.balance }}</div>
        </div>
      </el-col>

      <el-col :span="16">
        <!-- 我的作品 -->
        <div class="glass-panel section-card">
          <div class="sc-head">🎬 我的作品</div>
          <div v-if="myWorks.length===0" class="empty-hint">暂无作品，<router-link to="/create">去创作</router-link></div>
          <div v-else class="works-grid" v-loading="worksLoading">
            <div v-for="w in myWorks" :key="w.id" class="mw-card glass-panel" @click="playWork(w)">
              <div class="mw-cover">
                <img v-if="w.output_cover" :src="w.output_cover" class="mw-img" />
                <span v-else class="mw-icon">🎬</span>
                <div class="mw-dur" v-if="w.duration">{{ w.duration }}s</div>
              </div>
              <div class="mw-info">
                <h4>{{ w.title }}</h4>
                <span class="mw-date">{{ formatDate(w.created_at) }}</span>
              </div>
            </div>
          </div>
        </div>
      </el-col>
    </el-row>

    <!-- Video Dialog -->
    <el-dialog v-model="showDetail" :title="viewing?.title" width="800px" top="5vh" @close="viewing=null">
      <div v-if="viewing" class="detail">
        <div class="detail-video">
          <video v-if="viewing.output_video" :src="viewing.output_video" controls style="width:100%;max-height:500px;border-radius:var(--radius)" />
          <div v-else class="no-video">🎬 视频暂不可用</div>
        </div>
        <div class="detail-meta mono"><span>风格:{{ viewing.style }}</span><span>时长:{{ viewing.duration||0 }}秒</span><span>{{ formatDate(viewing.created_at) }}</span></div>
        <div class="detail-section" v-if="viewing.meta?.script||viewing.content"><span class="ds-label">📝 故事</span><pre class="ds-text">{{ viewing.meta?.script||viewing.content }}</pre></div>
        <div class="detail-section" v-if="viewing.meta?.kling_config"><span class="ds-label">⚙️ 配置</span><pre class="ds-text">{{ JSON.stringify(viewing.meta.kling_config,null,2) }}</pre></div>
      </div>
    </el-dialog>
  </div>
</template>

<style scoped>
.profile-page{max-width:1100px;margin:0 auto;padding:20px}
.page-hero{margin-bottom:24px}.page-hero h1{font-size:28px;font-weight:800;color:var(--text-primary)}
.info-card{padding:28px;text-align:center;margin-bottom:16px}
.avatar-wrap{display:flex;justify-content:center;margin-bottom:12px}
.avatar{width:64px;height:64px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,var(--accent),var(--accent2));color:#000;font-size:26px;font-weight:700}
.info-card h2{font-size:18px;color:var(--text-primary);margin-bottom:4px}
.email{color:var(--text-tertiary);font-size:13px}
.credit-card{padding:24px;text-align:center}.cc-head{color:var(--text-tertiary);font-size:13px;margin-bottom:8px}.cc-num{font-size:42px;font-weight:800;color:var(--warning);font-family:var(--font-mono)}
.section-card{padding:20px}.sc-head{font-size:16px;font-weight:700;color:var(--text-primary);margin-bottom:16px}
.empty-hint{text-align:center;padding:40px 0;color:var(--text-tertiary)}.empty-hint a{color:var(--accent)}
.works-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px}
.mw-card{cursor:pointer;overflow:hidden;padding:0;transition:all var(--transition)}
.mw-card:hover{transform:translateY(-2px);border-color:var(--border-accent)!important}
.mw-cover{height:140px;display:flex;align-items:center;justify-content:center;background:var(--bg-elevated);position:relative;overflow:hidden}
.mw-img{width:100%;height:100%;object-fit:cover}
.mw-icon{font-size:36px;color:var(--text-tertiary)}
.mw-dur{position:absolute;bottom:6px;right:6px;padding:1px 6px;border-radius:4px;background:rgba(0,0,0,0.7);color:#fff;font-size:11px;font-family:var(--font-mono)}
.mw-info{padding:10px 12px}.mw-info h4{font-size:13px;color:var(--text-primary);margin-bottom:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.mw-date{font-size:11px;color:var(--text-tertiary)}
.detail{padding:0}.detail-video{margin-bottom:16px}.no-video{height:300px;display:flex;align-items:center;justify-content:center;background:var(--bg-elevated);border-radius:var(--radius);color:var(--text-tertiary);font-size:24px}
.detail-meta{display:flex;gap:16px;font-size:12px;color:var(--text-tertiary);margin-bottom:12px;padding-bottom:12px;border-bottom:1px solid var(--border-subtle)}
.detail-section{margin-top:12px}.ds-label{display:block;font-size:12px;color:var(--accent);font-weight:600;margin-bottom:6px}
.ds-text{background:var(--bg-elevated);padding:12px;border-radius:var(--radius-sm);font-size:12px;color:var(--text-secondary);white-space:pre-wrap;max-height:200px;overflow-y:auto}
</style>
