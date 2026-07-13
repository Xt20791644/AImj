<script setup>
import { ref, reactive, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useCreditStore } from '../stores/credit'
import { ElMessage } from 'element-plus'
import api from '../api'

const router = useRouter()
const auth = useAuthStore()
const creditStore = useCreditStore()

const currentMode = ref('')
const activeStep = ref(0)
const steps = [
  { title: '故事输入', desc: '输入大纲，AI生成剧本' },
  { title: '图片生成', desc: '配置参数，AI生成画面' },
  { title: '视频合成', desc: '生成视频+配音，导出成片' },
]

const story = reactive({ title: '', content: '', style: 'realistic' })
const script = ref('')
const scriptEditing = ref(false)
const scriptGenerated = ref(false)

const options = ref(null)
const presets = ref({})
const kling = reactive({
  preset: 'short_drama', image_model: 'kling-v3', image_resolution: '2k', image_aspect_ratio: '9:16', image_n: 3,
  video_model: 'kling-v2-6', video_mode: 'pro', video_duration: '5', video_sound: 'off',
  video_negative_prompt: '',
})
const generatedImages = ref([])

const loading = ref(false)
const workId = ref(null)
let pollTimer = null

onMounted(async () => {
  try { const { data } = await api.get('/kling/options'); options.value = data; presets.value = data.presets||{} } catch(e){}
})
function applyPreset(k) {
  const p = presets.value[k]; if (!p) return
  Object.assign(kling, {preset:k, image_model:p.image_model, image_resolution:p.image_resolution, image_aspect_ratio:p.image_aspect_ratio, video_model:p.video_model, video_mode:p.video_mode, video_duration:p.video_duration, video_sound:p.video_sound||'off'})
}

// Fine mode
async function generateScript() {
  if (!story.title.trim()||!story.content.trim()) { ElMessage.warning('请填写故事'); return }
  loading.value = true
  await new Promise(r=>setTimeout(r,1200))
  script.value = `【剧本】《${story.title}》\n\n`+story.content.split('\n').filter(l=>l.trim()).map((l,i)=>`第${i+1}场\n${l.trim()}\n`).join('\n')
  scriptGenerated.value = true; loading.value = false; ElMessage.success('剧本生成完成')
}
async function generateImages() {
  loading.value = true
  await new Promise(r=>setTimeout(r,1500))
  const n = Math.max(3, Math.min(5, kling.image_n))
  generatedImages.value = Array.from({length:n},(_,i)=>({id:i+1,label:`场景 ${i+1}`,selected:false}))
  loading.value = false; ElMessage.success(`生成了 ${n} 张图片`)
}
function toggleImage(img) { img.selected = !img.selected }
function nextToVideo() {
  if (!generatedImages.value.filter(i=>i.selected).length) { ElMessage.warning('至少选一张'); return }
  activeStep.value = 2
}
async function finalGenerate() {
  loading.value = true
  try {
    const result = await api.post('/works', { title:story.title, content:story.content, style:story.style, mode:'fine', script:script.value, kling_config:{...kling} })
    workId.value = result.id; creditStore.fetchBalance(); activeStep.value=3; startPolling(); ElMessage.success('已提交')
  } catch(e) { loading.value=false; if(e.response?.status===402){ElMessage.error('积分不足');router.push('/profile')} }
}

// Fast mode
async function fastCreate() {
  if (!story.title.trim()||!story.content.trim()) { ElMessage.warning('请填写'); return }
  if (!auth.isLoggedIn) { router.push('/login'); return }
  loading.value = true
  try {
    const result = await api.post('/works', { title:story.title, content:story.content, style:story.style, mode:'fast', kling_config:{...kling} })
    workId.value = result.id; creditStore.fetchBalance(); activeStep.value=3; startPolling()
  } catch(e) { loading.value=false; if(e.response?.status===402){ElMessage.error('积分不足');router.push('/profile')} }
}

function startPolling() {
  pollTimer = setInterval(async()=>{
    try{ const r=await api.get(`/works/${workId.value}`); if(r.status==='completed'){stopPolling();ElMessage.success('完成！');setTimeout(()=>router.push('/works'),1000)}else if(r.status==='failed'){stopPolling();loading.value=false;ElMessage.error('失败')}}catch(e){}
  },2000)
}
function stopPolling() { if(pollTimer){clearInterval(pollTimer);pollTimer=null} }
onUnmounted(()=>stopPolling())
</script>

<template>
  <div class="create-page">
    <h1 class="page-title">🎬 创作工坊</h1>
    <p class="page-sub">选择模式，AI 把你的故事变成短剧</p>

    <!-- Mode Selection -->
    <div v-if="!currentMode" class="mode-grid">
      <div class="mode-card glass-card glow" @click="currentMode='fine'">
        <div class="mode-icon">🎯</div><h3>精细模式</h3>
        <p>逐步引导：AI生成剧本可编辑 → 配置参数 → 选择图片 → 生成视频配音</p>
        <span class="mode-tag">推荐 · 可控</span>
      </div>
      <div class="mode-card glass-card" @click="currentMode='fast'">
        <div class="mode-icon">⚡</div><h3>快速模式</h3>
        <p>一键生成：输入故事大纲，AI 自动完成全部流程直接出片</p>
        <span class="mode-tag fast">快捷 · 省心</span>
      </div>
    </div>

    <!-- Fine Mode -->
    <template v-if="currentMode==='fine'">
      <div class="steps-bar" v-if="activeStep<3">
        <div v-for="(s,i) in steps" :key="i" class="step-item" :class="{done:i<activeStep,active:i===activeStep}">
          <div class="step-num">{{ i<activeStep?'✓':i+1 }}</div>
          <div class="step-info"><span class="step-title">{{ s.title }}</span><span class="step-desc">{{ s.desc }}</span></div>
        </div>
      </div>

      <!-- Step 1 -->
      <div v-if="activeStep===0" class="panel glass-card">
        <h2>📝 Step 1 — 故事输入</h2>
        <el-row :gutter="16">
          <el-col :span="12"><el-form-item label="标题"><el-input v-model="story.title" size="large" /></el-form-item></el-col>
          <el-col :span="12"><el-form-item label="风格"><el-select v-model="story.style" size="large" style="width:100%"><el-option v-for="s in [{v:'realistic',l:'真人写实'},{v:'anime',l:'日系动画'},{v:'3d',l:'3D动画'}]" :key="s.v" :label="s.l" :value="s.v"/></el-select></el-form-item></el-col>
        </el-row>
        <el-form-item label="故事大纲"><el-input v-model="story.content" type="textarea" :rows="6" placeholder="输入故事大纲，AI将自动生成剧本..." /></el-form-item>
        <div style="text-align:right"><el-button :loading="loading" :disabled="!story.title||!story.content" size="large" @click="generateScript">🤖 AI 生成剧本</el-button></div>

        <div v-if="scriptGenerated" class="script-box glass-card" style="margin-top:16px;padding:20px">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
            <h3 style="color:var(--accent)">📜 生成的剧本</h3>
            <el-button size="small" @click="scriptEditing=!scriptEditing">{{ scriptEditing?'完成':'编辑' }}</el-button>
          </div>
          <el-input v-if="scriptEditing" v-model="script" type="textarea" :rows="10" />
          <pre v-else style="color:var(--text);white-space:pre-wrap;font-family:var(--font);line-height:1.8">{{ script }}</pre>
          <div style="text-align:right;margin-top:16px"><el-button type="primary" size="large" @click="activeStep=1">下一步 → 图片生成</el-button></div>
        </div>
      </div>

      <!-- Step 2 -->
      <div v-if="activeStep===1" class="panel glass-card">
        <h2>🖼️ Step 2 — 图片生成</h2>
        <div v-if="!generatedImages.length">
          <el-row :gutter="16">
            <el-col :span="8"><el-form-item label="图片模型"><el-select v-model="kling.image_model" size="large" style="width:100%" v-if="options"><el-option v-for="(m,k) in options.image_models" :key="k" :label="m.name" :value="k"/></el-select></el-form-item></el-col>
            <el-col :span="5"><el-form-item label="分辨率"><el-select v-model="kling.image_resolution" size="large" style="width:100%" v-if="options"><el-option v-for="(l,v) in options.image_resolutions" :key="v" :label="l" :value="v"/></el-select></el-form-item></el-col>
            <el-col :span="5"><el-form-item label="宽高比"><el-select v-model="kling.image_aspect_ratio" size="large" style="width:100%" v-if="options"><el-option v-for="(l,v) in options.image_aspect_ratios" :key="v" :label="l" :value="v"/></el-select></el-form-item></el-col>
            <el-col :span="6"><el-form-item label="数量(3-5)"><el-input-number v-model="kling.image_n" :min="3" :max="5" size="large" style="width:100%"/></el-form-item></el-col>
          </el-row>
          <div style="text-align:right"><el-button type="primary" size="large" @click="generateImages" :loading="loading">🎨 生成图片</el-button></div>
        </div>
        <div v-else>
          <p style="color:var(--text-dim);margin-bottom:12px">点击选择满意的图片</p>
          <div class="img-grid">
            <div v-for="img in generatedImages" :key="img.id" class="img-card glass-card" :class="{sel:img.selected}" @click="toggleImage(img)">
              <div class="img-ph"><span>🖼️</span><p>{{ img.label }}</p></div>
              <div v-if="img.selected" class="check">✓</div>
            </div>
          </div>
          <div style="text-align:right;margin-top:16px">
            <span style="color:var(--text-dim);margin-right:16px">已选 {{ generatedImages.filter(i=>i.selected).length }} 张</span>
            <el-button type="primary" size="large" @click="nextToVideo">下一步 → 视频合成</el-button>
          </div>
        </div>
      </div>

      <!-- Step 3 -->
      <div v-if="activeStep===2" class="panel glass-card">
        <h2>🎥 Step 3 — 视频合成</h2>
        <el-row :gutter="16">
          <el-col :span="8"><el-form-item label="视频模型"><el-select v-model="kling.video_model" size="large" style="width:100%" v-if="options"><el-option v-for="(m,k) in options.video_models" :key="k" :label="m.name" :value="k"/></el-select></el-form-item></el-col>
          <el-col :span="5"><el-form-item label="画质"><el-select v-model="kling.video_mode" size="large" style="width:100%" v-if="options"><el-option v-for="(l,v) in options.video_modes" :key="v" :label="l" :value="v"/></el-select></el-form-item></el-col>
          <el-col :span="5"><el-form-item label="时长"><el-select v-model="kling.video_duration" size="large" style="width:100%" v-if="options"><el-option v-for="(l,v) in options.video_durations" :key="v" :label="l" :value="v"/></el-select></el-form-item></el-col>
          <el-col :span="6"><el-form-item label="声音"><el-switch v-model="kling.video_sound" active-value="on" inactive-value="off" active-text="开" inactive-text="关"/></el-form-item></el-col>
        </el-row>
        <el-form-item label="负向提示词"><el-input v-model="kling.video_negative_prompt" placeholder="画面抖动、变形、闪烁"/></el-form-item>
        <div style="text-align:right;margin-top:16px">
          <span style="color:var(--warning);margin-right:16px">50 积分 | 余额 {{ auth.user?.credits||0 }}</span>
          <el-button type="primary" size="large" @click="finalGenerate" :loading="loading">🚀 开始生成</el-button>
        </div>
      </div>
    </template>

    <!-- Fast Mode -->
    <div v-if="currentMode==='fast'" class="panel glass-card">
      <h2>⚡ 快速模式 — 一键生成</h2>
      <p style="color:var(--text-dim);margin-bottom:20px">输入故事，AI 自动完成全流程</p>
      <el-row :gutter="16">
        <el-col :span="12"><el-form-item label="标题"><el-input v-model="story.title" size="large"/></el-form-item></el-col>
        <el-col :span="12"><el-form-item label="预设"><el-select v-model="kling.preset" size="large" style="width:100%" @change="applyPreset"><el-option v-for="(p,k) in presets" :key="k" :label="p.name" :value="k"/></el-select></el-form-item></el-col>
      </el-row>
      <el-form-item label="故事大纲"><el-input v-model="story.content" type="textarea" :rows="8" placeholder="输入故事大纲，剩下的交给AI..."/></el-form-item>
      <div style="text-align:right">
        <span style="color:var(--warning);margin-right:16px">50 积分 | 余额 {{ auth.user?.credits||0 }}</span>
        <el-button type="primary" size="large" @click="fastCreate" :loading="loading" :disabled="!story.title||!story.content">⚡ 一键生成</el-button>
      </div>
    </div>

    <!-- Progress -->
    <div v-if="activeStep===3" class="panel glass-card glow" style="text-align:center;padding:40px">
      <h2 style="color:var(--accent)">🚀 创作进行中</h2>
      <p style="color:var(--text-dim);margin:16px 0 24px">AI 正在处理你的短剧...</p>
      <el-progress :percentage="50" :stroke-width="16" :indeterminate="true"/>
      <p style="color:var(--text-dim);margin-top:16px;font-size:13px">可离开页面稍后查看</p>
    </div>
  </div>
</template>

<style scoped>
.create-page { max-width:900px; margin:0 auto; }
.page-title { font-size:32px; margin-bottom:4px; color:var(--text-bright); }
.page-sub { color:var(--text-dim); margin-bottom:32px; }
.mode-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:32px; }
.mode-card { padding:32px; cursor:pointer; transition:all .3s; }
.mode-card:hover { transform:translateY(-2px); }
.mode-card .mode-icon { font-size:40px; margin-bottom:12px; }
.mode-card h3 { font-size:20px; color:var(--text-bright); margin-bottom:8px; }
.mode-card p { color:var(--text-dim); font-size:14px; line-height:1.6; }
.mode-tag { display:inline-block; margin-top:12px; padding:4px 10px; border-radius:4px; font-size:12px; background:rgba(0,229,255,0.15); color:var(--accent); border:1px solid rgba(0,229,255,0.2); }
.mode-tag.fast { background:rgba(179,71,234,0.15); color:var(--accent2); border-color:rgba(179,71,234,0.2); }
.steps-bar { display:flex; gap:0; margin-bottom:24px; }
.step-item { flex:1; display:flex; align-items:center; gap:12px; padding:12px 16px; border-radius:var(--radius); background:var(--bg-card); border:1px solid var(--border); opacity:0.4; transition:all .3s; }
.step-item.active { opacity:1; border-color:var(--accent); box-shadow:0 0 12px rgba(0,229,255,0.15); }
.step-item.done { opacity:0.8; border-color:var(--success); }
.step-num { width:28px; height:28px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:700; background:var(--bg-input); color:var(--text-dim); flex-shrink:0; }
.step-item.active .step-num { background:var(--accent); color:#000; }
.step-item.done .step-num { background:var(--success); color:#000; }
.step-title { font-size:14px; color:var(--text-bright); font-weight:600; }
.step-desc { font-size:12px; color:var(--text-dim); }
.panel { padding:24px; margin-bottom:20px; }
.panel h2 { font-size:20px; color:var(--text-bright); margin-bottom:20px; }
.script-box pre { background:var(--bg-input); padding:16px; border-radius:8px; }
.img-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(140px,1fr)); gap:12px; }
.img-card { cursor:pointer; overflow:hidden; position:relative; transition:all .2s; }
.img-card.sel { border-color:var(--accent)!important; box-shadow:0 0 16px rgba(0,229,255,0.3)!important; }
.img-ph { height:160px; display:flex; flex-direction:column; align-items:center; justify-content:center; background:var(--bg-input); }
.img-ph p { color:var(--text-dim); font-size:12px; margin-top:8px; }
.check { position:absolute; top:8px; right:8px; width:24px; height:24px; border-radius:50%; background:var(--accent); color:#000; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; }
</style>
