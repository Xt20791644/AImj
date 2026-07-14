<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { ElMessage } from 'element-plus'
import api from '../api'

const router = useRouter()
const auth = useAuthStore()
const promptInput = ref('')
const quickLoading = ref(false)

const scenarios = [
  { icon: '🎬', title: '真人短剧', desc: '输入一句话，AI自动生成完整短剧视频', tag: '热门', action: 'fast', preset: 'short_drama' },
  { icon: '🎙️', title: '解说短剧', desc: 'AI智能旁白改编，沉浸式解说风格', tag: '推荐', action: 'fast', preset: 'short_drama' },
  { icon: '🎨', title: 'AI漫剧', desc: '二次元动漫画风，批量生成连载漫剧', tag: '新功能', action: 'fast', preset: 'short_drama' },
  { icon: '📱', title: '广告营销', desc: '产品展示+卖点解说，高效获客视频', tag: '', action: 'fast', preset: 'fast_preview' },
  { icon: '🎵', title: '音乐MV', desc: '音乐+画面自动匹配，氛围感MV', tag: '', action: 'fast', preset: 'cinematic' },
  { icon: '📚', title: '小说推文', desc: '小说文案一键转视频，批量分发', tag: '', action: 'fast', preset: 'fast_preview' },
]

const tools = [
  { icon: '🎬', title: '短剧 Agent', desc: '上传剧本，AI自动拆解分镜、设计角色、生成完整短剧', path: '/studio' },
  { icon: '👤', title: '角色资产库', desc: '管理角色形象、音色、人设，全剧保持一致不崩脸', path: '/studio/characters' },
  { icon: '🎥', title: '分镜编辑器', desc: '逐镜头编辑提示词、运镜、台词，精细控制每个画面', path: '/studio/episodes' },
]

async function quickCreate() {
  if (!promptInput.value.trim()) { ElMessage.warning('请输入你的创作想法'); return }
  if (!auth.isLoggedIn) { router.push('/login'); return }
  quickLoading.value = true
  try {
    const result = await api.post('/works', {
      title: promptInput.value.substring(0, 30),
      content: promptInput.value,
      style: 'realistic',
      mode: 'fast',
      kling_config: { image_model:'kling-v3', image_resolution:'2k', image_aspect_ratio:'9:16', image_n:1, video_model:'kling-v3-turbo', video_mode:'pro', video_duration:'10', video_sound:'on' }
    })
    ElMessage.success('创作任务已提交，正在后台生成...')
    router.push('/profile')
  } catch(e) {
    if (e.response?.status === 402) { ElMessage.error('积分不足'); router.push('/profile') }
    else ElMessage.error('提交失败')
  }
  quickLoading.value = false
}
</script>

<template>
  <div class="home-page">
    <!-- Hero -->
    <section class="hero">
      <h1 class="hero-title">AI短剧，一句话生成完整视频</h1>
      <p class="hero-sub">输入你的创作想法，AI自动完成剧本、角色、分镜、配音、成片全流程</p>
      <div class="hero-input">
        <el-input v-model="promptInput" size="large" placeholder="输入你的创作想法，例如：都市白领林晨被裁员后捡到一块怀表，时间倒流回三年前..." class="hero-input-field" @keyup.enter="quickCreate">
          <template #append><el-button type="primary" :loading="quickLoading" @click="quickCreate" style="width:120px">🚀 一键生成</el-button></template>
        </el-input>
      </div>
      <p class="hero-hint">支持真人写实 · 二次元 · 3D动画等多种风格</p>
    </section>

    <!-- Scenarios -->
    <section class="section">
      <h2>应用场景</h2>
      <p class="section-sub">多种创作模式，覆盖主流内容赛道</p>
      <div class="scenario-grid">
        <div v-for="s in scenarios" :key="s.title" class="sc-card glass-panel" @click="router.push('/create')">
          <div class="sc-icon">{{ s.icon }}</div>
          <div class="sc-info">
            <h3>{{ s.title }}<span v-if="s.tag" class="sc-tag">{{ s.tag }}</span></h3>
            <p>{{ s.desc }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Tools -->
    <section class="section">
      <h2>专业创作工具</h2>
      <p class="section-sub">从灵感到大片，一站式AI创作工作台</p>
      <div class="tool-grid">
        <div v-for="t in tools" :key="t.title" class="tool-card glass-panel" @click="router.push(t.path)">
          <div class="tool-icon">{{ t.icon }}</div>
          <h3>{{ t.title }}</h3>
          <p>{{ t.desc }}</p>
          <span class="tool-arrow">→</span>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
      <p>◆ AI短剧 · 让每个人都能创作属于自己的故事</p>
    </footer>
  </div>
</template>

<style scoped>
.home-page{max-width:1100px;margin:0 auto;padding:20px}
.hero{text-align:center;padding:60px 20px 40px}
.hero-title{font-size:38px;font-weight:800;color:var(--text-primary);margin-bottom:12px;letter-spacing:-0.02em}
.hero-sub{font-size:16px;color:var(--text-secondary);margin-bottom:28px;max-width:600px;margin-left:auto;margin-right:auto}
.hero-input{max-width:700px;margin:0 auto}
.hero-input-field :deep(.el-input__wrapper){height:56px!important;border-radius:28px!important;padding-left:20px!important;background:var(--bg-elevated)!important;border:1px solid var(--border-accent)!important;box-shadow:0 0 30px var(--accent-dim)!important}
.hero-input-field :deep(.el-input-group__append){background:transparent!important;border:none!important;padding:0!important;border-radius:0 28px 28px 0!important;overflow:hidden}
.hero-input-field :deep(.el-button--primary){height:56px!important;border-radius:0 28px 28px 0!important;font-size:16px!important}
.hero-hint{font-size:13px;color:var(--text-tertiary);margin-top:16px}
.section{margin-top:60px}.section h2{font-size:28px;font-weight:700;color:var(--text-primary);text-align:center;margin-bottom:8px}.section-sub{text-align:center;color:var(--text-secondary);font-size:14px;margin-bottom:32px}
.scenario-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}
.sc-card{padding:24px;cursor:pointer;border-radius:var(--radius-lg);transition:all var(--transition);display:flex;gap:16px;align-items:flex-start}.sc-card:hover{transform:translateY(-2px);border-color:var(--border-accent)!important}
.sc-icon{font-size:32px;flex-shrink:0}.sc-info h3{font-size:15px;color:var(--text-primary);margin-bottom:4px;display:flex;align-items:center;gap:8px}.sc-tag{font-size:10px;padding:1px 6px;border-radius:4px;background:var(--accent-dim);color:var(--accent)}.sc-info p{font-size:12px;color:var(--text-secondary);line-height:1.5}
.tool-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}
.tool-card{padding:28px 24px;cursor:pointer;border-radius:var(--radius-lg);transition:all var(--transition);position:relative}.tool-card:hover{transform:translateY(-2px);border-color:var(--border-accent)!important}.tool-icon{font-size:36px;margin-bottom:16px}.tool-card h3{font-size:18px;color:var(--text-primary);margin-bottom:8px}.tool-card p{font-size:13px;color:var(--text-secondary);line-height:1.6}.tool-arrow{position:absolute;top:20px;right:20px;font-size:18px;color:var(--text-tertiary)}
.footer{text-align:center;padding:40px 20px;color:var(--text-tertiary);font-size:13px;margin-top:60px;border-top:1px solid var(--border-subtle)}
</style>
