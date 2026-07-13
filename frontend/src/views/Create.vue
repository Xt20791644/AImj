<script setup>
import { ref, reactive, onMounted, onUnmounted, watch, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useCreditStore } from '../stores/credit'
import { ElMessage } from 'element-plus'
import api from '../api'

const router = useRouter(); const auth = useAuthStore(); const creditStore = useCreditStore()
const currentMode = ref(''); const activeStep = ref(0)
const steps = [{t:'故事剧本',d:'输入大纲, AI生成剧本可编辑'},{t:'图片生成',d:'配置参数, AI生成场景画面'},{t:'视频合成',d:'生成视频配音, 导出成片'}]

const story = reactive({ title: '', content: '', style: 'realistic' })
const script = ref(''); const scriptEditing = ref(false); const scriptGenerated = ref(false)

// 内嵌选项数据（不依赖后端API）
const imageModels = [
  {value:'kling-v3',label:'Kling V3 (旗舰·推荐)'},{value:'kling-v3-omni',label:'Kling V3 Omni (全能)'},
  {value:'kling-v2-1',label:'Kling V2.1 (稳定)'},{value:'kling-v2-new',label:'Kling V2 New'},
  {value:'kling-v2',label:'Kling V2'},{value:'kling-v1-5',label:'Kling V1.5 (人脸参考)'},
  {value:'kling-v1',label:'Kling V1 (基础)'},{value:'kling-image-o1',label:'Kling Image O1 (专业4K)'},
]
const videoModels = [
  {value:'kling-v3',label:'Kling V3 (旗舰)',sound:true,camera:true,cfg:true,t2v:true},
  {value:'kling-v3-omni',label:'Kling V3 Omni (全能)',sound:true,camera:true,cfg:true,t2v:true},
  {value:'kling-v2-6',label:'Kling V2.6 (推荐)',sound:true,camera:true,cfg:false,t2v:true},
  {value:'kling-video-o1',label:'Kling Video O1 (专业)',sound:true,camera:false,cfg:false,t2v:false},
  {value:'kling-v2-5-turbo',label:'Kling V2.5 Turbo (快速)',sound:false,camera:false,cfg:false,t2v:true},
  {value:'kling-v2-1-master',label:'Kling V2.1 Master',sound:false,camera:false,cfg:false,t2v:false},
  {value:'kling-v2-1',label:'Kling V2.1',sound:false,camera:false,cfg:false,t2v:false},
  {value:'kling-v2-master',label:'Kling V2 Master',sound:false,camera:false,cfg:false,t2v:false},
  {value:'kling-v1-6',label:'Kling V1.6',sound:false,camera:false,cfg:true,t2v:false},
  {value:'kling-v1-5',label:'Kling V1.5',sound:false,camera:false,cfg:true,t2v:false},
  {value:'kling-v1',label:'Kling V1',sound:false,camera:false,cfg:true,t2v:false},
]
const resolutions = [{value:'1k',label:'1K 标清'},{value:'2k',label:'2K 高清'},{value:'4k',label:'4K 超清'}]
const aspectRatios = [
  {value:'9:16',label:'9:16 竖屏(抖音·推荐)'},{value:'16:9',label:'16:9 横屏'},
  {value:'1:1',label:'1:1 方形'},{value:'4:3',label:'4:3 标准'},{value:'3:4',label:'3:4 竖屏'},
  {value:'3:2',label:'3:2 宽屏'},{value:'2:3',label:'2:3 竖长'},{value:'auto',label:'自动'},
]
const videoModes = [{value:'std',label:'标准 720P'},{value:'pro',label:'专业 1080P'},{value:'4k',label:'4K 超清'}]
const durations = [
  {value:'3',label:'3秒'},{value:'4',label:'4秒'},{value:'5',label:'5秒'},{value:'6',label:'6秒'},
  {value:'7',label:'7秒'},{value:'8',label:'8秒'},{value:'9',label:'9秒'},{value:'10',label:'10秒'},
  {value:'11',label:'11秒'},{value:'12',label:'12秒'},{value:'13',label:'13秒'},{value:'14',label:'14秒'},{value:'15',label:'15秒'},
]
const cameraTypes = [
  {value:'simple',label:'自定义运镜'},{value:'down_back',label:'下后拉远'},
  {value:'forward_up',label:'前上推进'},{value:'right_turn_forward',label:'右转前进'},{value:'left_turn_forward',label:'左转前进'},
]
const kling = reactive({
  image_model:'kling-v3', image_resolution:'2k', image_aspect_ratio:'9:16', image_n:1,
  video_model:'kling-v2-6', video_mode:'pro', video_duration:'5', video_aspect_ratio:'9:16', video_sound:'on',
  video_negative_prompt:'', camera_type:'',
})
const generatedImages = ref([])
const analysis = ref(null)
const warnings = ref([])
const loading = ref(false); const recommendLoading = ref(false)

// 当前视频模型是否支持声音
const currentVideoModel = computed(() => videoModels.find(m => m.value === kling.video_model))
watch(() => kling.video_model, (val) => {
  const m = videoModels.find(x => x.value === val)
  if (m) {
    kling.video_sound = m.sound ? 'on' : 'off'
  }
})

// 声音需要 pro 模式，std 不支持声音
watch(() => kling.video_sound, (val) => {
  if (val === 'on' && kling.video_mode === 'std') {
    kling.video_mode = 'pro'
  }
})
const workId = ref(null); let pollTimer = null

onMounted(async()=>{})

// AI 推荐配置
async function aiRecommend() {
  if (!story.content.trim()) { ElMessage.warning('请先输入故事大纲'); return }
  recommendLoading.value = true
  try {
    const result = await api.post('/kling/recommend', { content: story.content, style: story.style, duration_hint: kling.video_duration })
    Object.assign(kling, result.recommended)
    // 强制根据模型声音能力设置
    const vm = videoModels.find(m => m.value === kling.video_model)
    if (vm) kling.video_sound = vm.sound ? 'on' : 'off'
    analysis.value = result.analysis
    warnings.value = result.warnings || []
    if (warnings.value.length) warnings.value.forEach(w => ElMessage.warning(w.replace(/^[^\s]+\s/,'')))
    else ElMessage.success('AI 已根据故事内容推荐最优配置')
  } catch(e) { ElMessage.error('推荐失败') }
  finally { recommendLoading.value = false }
}

// 校验配置
async function validateConfig() {
  if (!story.content.trim()) return
  try {
  const result = await api.post('/kling/validate', { content: story.content, config: { ...kling } })
  warnings.value = result.warnings || []
  } catch(e) {}
}

async function generateScript(){if(!story.title.trim()||!story.content.trim()){ElMessage.warning('请填写故事');return};loading.value=true;await new Promise(r=>setTimeout(r,1200));script.value=`【剧本】《${story.title}》\n\n`+story.content.split('\n').filter(l=>l.trim()).map((l,i)=>`第${i+1}场\n${l.trim()}\n`).join('\n');scriptGenerated.value=true;loading.value=false;ElMessage.success('剧本生成完成')}
async function generateImages(){loading.value=true;await new Promise(r=>setTimeout(r,1500));const n=Math.max(3,Math.min(5,kling.image_n));generatedImages.value=Array.from({length:n},(_,i)=>({id:i+1,label:`场景 ${i+1}`,selected:false}));loading.value=false;ElMessage.success(`生成了 ${n} 张图片`)}
function toggleImage(img){img.selected=!img.selected}
function nextToVideo(){if(!generatedImages.value.filter(i=>i.selected).length){ElMessage.warning('至少选一张');return};activeStep.value=2}
async function finalGenerate(){loading.value=true;try{const result=await api.post('/works',{title:story.title,content:story.content,style:story.style,mode:'fine',script:script.value,kling_config:{...kling}});workId.value=result.id;creditStore.fetchBalance();activeStep.value=3;startPolling();ElMessage.success('已提交')}catch(e){loading.value=false;if(e.response?.status===402){ElMessage.error('积分不足');router.push('/profile')}}}
async function fastCreate(){if(!story.title.trim()||!story.content.trim()){ElMessage.warning('请填写');return};if(!auth.isLoggedIn){router.push('/login');return};loading.value=true;try{const result=await api.post('/works',{title:story.title,content:story.content,style:story.style,mode:'fast',kling_config:{...kling}});workId.value=result.id;creditStore.fetchBalance();activeStep.value=3;startPolling()}catch(e){loading.value=false;if(e.response?.status===402){ElMessage.error('积分不足');router.push('/profile')}}}
function startPolling(){pollTimer=setInterval(async()=>{try{const r=await api.get(`/works/${workId.value}`);if(r.status==='completed'){stopPolling();ElMessage.success('创作完成！');setTimeout(()=>router.push('/works'),1500)}else if(r.status==='failed'){stopPolling();loading.value=false;ElMessage.error('失败：'+(r.status_text||'未知'))}}catch(e){}},3000)}
function stopPolling(){if(pollTimer){clearInterval(pollTimer);pollTimer=null}}
onUnmounted(()=>stopPolling())
</script>

<template>
  <div class="create-page page-enter">
    <div class="page-hero"><h1 class="hero-title">创作工坊</h1><p class="hero-sub">AI驱动的短剧创作引擎 — 选择模式，开始创作</p><div class="tech-line"></div></div>

    <!-- Mode Selection -->
    <div v-if="!currentMode" class="mode-grid">
      <div class="mode-card glass-panel glow" @click="currentMode='fine'">
        <div class="mc-icon">▣</div><h3 class="mc-title">精细模式</h3>
        <p class="mc-desc">逐步引导：AI生成剧本可编辑 → 配置图片参数 → 选择画面 → 合成视频配音</p>
        <div class="mc-footer"><span class="tech-badge">▸ 推荐 · 高质量可控</span></div>
      </div>
      <div class="mode-card glass-panel" @click="currentMode='fast'">
        <div class="mc-icon">▻</div><h3 class="mc-title">快速模式</h3>
        <p class="mc-desc">输入故事 → AI推荐配置 → 调整确认 → 一键出片</p>
        <div class="mc-footer"><span class="tech-badge">▸ 快捷 · 省心省力</span></div>
      </div>
    </div>

    <!-- ==================== FINE MODE ==================== -->
    <template v-if="currentMode==='fine'">
      <div class="steps-track" v-if="activeStep<3">
        <div v-for="(s,i) in steps" :key="i" class="st-node" :class="{done:i<activeStep,on:i===activeStep}">
          <div class="st-bullet">{{ i<activeStep?'✓':i+1 }}</div>
          <div class="st-info"><span class="st-label">{{ s.t }}</span><span class="st-hint">{{ s.d }}</span></div>
          <div v-if="i<2" class="st-connector" :class="{filled:i<activeStep}"></div>
        </div>
      </div>

      <!-- Step 1: Story -->
      <div v-if="activeStep===0" class="step-block glass-panel glow">
        <div class="block-head"><span class="block-num">01</span><h2>故事剧本</h2></div>
        <el-row :gutter="16"><el-col :span="12"><el-form-item label="作品名称"><el-input v-model="story.title" placeholder="给你的短剧起一个名字" size="large"/></el-form-item></el-col><el-col :span="12"><el-form-item label="视觉风格"><el-select v-model="story.style" size="large" style="width:100%"><el-option v-for="s in [{v:'realistic',l:'真人写实'},{v:'anime',l:'日系动画'},{v:'3d',l:'3D动画'}]" :key="s.v" :label="s.l" :value="s.v"/></el-select></el-form-item></el-col></el-row>
        <el-form-item label="故事大纲"><el-input v-model="story.content" type="textarea" :rows="6" placeholder="输入故事大纲，AI将自动生成完整剧本..."/></el-form-item>
        <div class="block-action"><el-button :loading="loading" :disabled="!story.title||!story.content" size="large" @click="generateScript"><span class="btn-icon">⟳</span> AI 生成剧本</el-button></div>
        <div v-if="scriptGenerated" class="script-panel glass-panel">
          <div class="sp-head"><h3>📜 剧本预览</h3><el-button size="small" text @click="scriptEditing=!scriptEditing">{{ scriptEditing?'完成编辑':'在线编辑' }}</el-button></div>
          <el-input v-if="scriptEditing" v-model="script" type="textarea" :rows="10"/>
          <pre v-else class="sp-text">{{ script }}</pre>
          <div class="block-action"><el-button type="primary" size="large" @click="activeStep=1">进入下一步 → 图片生成</el-button></div>
        </div>
      </div>

      <!-- Step 2: Image Config -->
      <div v-if="activeStep===1" class="step-block glass-panel glow">
        <div class="block-head"><span class="block-num">02</span><h2>图片生成</h2><el-button size="small" @click="aiRecommend" :loading="recommendLoading" style="margin-left:auto">🤖 AI 推荐配置</el-button></div>
        <div v-if="!generatedImages.length">
          <el-row :gutter="16">
            <el-col :span="8"><el-form-item label="生成模型"><el-select v-model="kling.image_model" size="large" style="width:100%" @change="validateConfig" ><el-option v-for="m in imageModels" :key="m.value" :label="m.label" :value="m.value"/></el-select></el-form-item></el-col>
            <el-col :span="5"><el-form-item label="输出分辨率"><el-select v-model="kling.image_resolution" size="large" style="width:100%" ><el-option v-for="r in resolutions" :key="r.value" :label="r.label" :value="r.value"/></el-select></el-form-item></el-col>
            <el-col :span="5"><el-form-item label="画面比例"><el-select v-model="kling.image_aspect_ratio" size="large" style="width:100%" ><el-option v-for="r in aspectRatios" :key="r.value" :label="r.label" :value="r.value"/></el-select></el-form-item></el-col>
            <el-col :span="6"><el-form-item label="生成数量"><el-input-number v-model="kling.image_n" :min="3" :max="5" size="large" style="width:100%"/></el-form-item></el-col>
          </el-row>
          <div v-if="warnings.length" class="warn-panel"><span v-for="w in warnings" :key="w.field" class="warn-item">⚠ {{ w.message }}</span></div>
          <div class="block-action"><el-button type="primary" size="large" @click="generateImages" :loading="loading"><span class="btn-icon">▣</span> 开始生成图片</el-button></div>
        </div>
        <div v-else>
          <p class="block-hint">点击选择满意的图片</p>
          <div class="img-grid"><div v-for="img in generatedImages" :key="img.id" class="img-card glass-panel" :class="{sel:img.selected}" @click="toggleImage(img)"><div class="img-placeholder">▣<p>{{ img.label }}</p></div><div v-if="img.selected" class="img-check">✓</div></div></div>
          <div class="block-action"><span class="count-hint">已选 {{ generatedImages.filter(i=>i.selected).length }}/{{ generatedImages.length }}</span><el-button type="primary" size="large" @click="nextToVideo">下一步 → 视频合成</el-button></div>
        </div>
      </div>

      <!-- Step 3: Video Config -->
      <div v-if="activeStep===2" class="step-block glass-panel glow">
        <div class="block-head"><span class="block-num">03</span><h2>视频合成</h2><el-button size="small" @click="aiRecommend" :loading="recommendLoading" style="margin-left:auto">🤖 AI 推荐配置</el-button></div>
        <el-row :gutter="16">
          <el-col :span="8"><el-form-item label="视频模型"><el-select v-model="kling.video_model" size="large" style="width:100%" @change="validateConfig" ><el-option v-for="m in videoModels" :key="m.value" :label="m.label" :value="m.value"><span style="display:flex;align-items:center;gap:8px">{{ m.label }}<span v-if="m.sound" style="color:var(--accent);font-size:12px">🔊 有声</span><span v-else style="color:var(--text-tertiary);font-size:12px">🔇 无声</span></span></el-option></el-select></el-form-item></el-col>
          <el-col :span="5"><el-form-item label="画质模式"><el-select v-model="kling.video_mode" size="large" style="width:100%" ><el-option v-for="m in videoModes" :key="m.value" :label="m.label" :value="m.value"/></el-select></el-form-item></el-col>
          <el-col :span="5"><el-form-item label="视频时长"><el-select v-model="kling.video_duration" size="large" style="width:100%" @change="validateConfig" ><el-option v-for="d in durations" :key="d.value" :label="d.label" :value="d.value"/></el-select></el-form-item></el-col>
          <el-col :span="6"><el-form-item label="画面比例"><el-select v-model="kling.video_aspect_ratio" size="large" style="width:100%" ><el-option v-for="r in aspectRatios" :key="r.value" :label="r.label" :value="r.value"/></el-select></el-form-item></el-col>
        </el-row>
        <el-row :gutter="16">
          <el-col :span="8"><el-form-item label="声音"><el-switch v-model="kling.video_sound" active-value="on" inactive-value="off" active-text="开" inactive-text="关" :disabled="!currentVideoModel?.sound"/></el-form-item></el-col>
          <el-col :span="8"><el-form-item label="运镜"><el-select v-model="kling.camera_type" size="large" style="width:100%" clearable placeholder="无运镜" :disabled="!currentVideoModel?.camera" ><el-option v-for="c in cameraTypes" :key="c.value" :label="c.label" :value="c.value"/></el-select></el-form-item></el-col>
        </el-row>
        <el-form-item label="负向提示词"><el-input v-model="kling.video_negative_prompt" placeholder="排除：画面抖动、变形、闪烁、模糊"/></el-form-item>
        <div v-if="warnings.length" class="warn-panel"><span v-for="w in warnings" :key="w.field" class="warn-item">⚠ {{ w.message }}</span></div>
        <div class="block-action"><span class="count-hint mono">50 积分 · 余额 {{ auth.user?.credits||0 }}</span><el-button type="primary" size="large" @click="finalGenerate" :loading="loading"><span class="btn-icon">▶</span> 开始生成</el-button></div>
      </div>
    </template>

    <!-- ==================== FAST MODE ==================== -->
    <div v-if="currentMode==='fast'" class="step-block glass-panel glow">
      <div class="block-head"><span class="block-num">⚡</span><h2>快速模式</h2><el-button size="small" @click="aiRecommend" :loading="recommendLoading" style="margin-left:auto">🤖 AI 分析推荐</el-button></div>
      <p class="block-hint">输入故事大纲，点击 AI 分析让系统自动推荐最优配置，你也可以手动调整</p>

      <!-- Story -->
      <el-row :gutter="16"><el-col :span="12"><el-form-item label="作品名称"><el-input v-model="story.title" placeholder="给你的短剧起个名字" size="large"/></el-form-item></el-col><el-col :span="12"><el-form-item label="视觉风格"><el-select v-model="story.style" size="large" style="width:100%"><el-option v-for="s in [{v:'realistic',l:'真人写实'},{v:'anime',l:'日系动画'},{v:'3d',l:'3D动画'}]" :key="s.v" :label="s.l" :value="s.v"/></el-select></el-form-item></el-col></el-row>
      <el-form-item label="故事大纲"><el-input v-model="story.content" type="textarea" :rows="6" placeholder="输入故事大纲，AI将自动分析并推荐最优配置..."/></el-form-item>

      <!-- Analysis -->
      <div v-if="analysis" class="analysis-bar glass-panel">
        <span class="mono" style="color:var(--accent)">📊 分析: {{ analysis.char_count }}字 · {{ analysis.scene_count }}场景 · {{ analysis.duration_reason }}</span>
      </div>

      <!-- Full Config Panel -->
      <div class="config-section">
        <div class="config-group"><h4>🖼️ 图片配置</h4>
          <el-row :gutter="12">
            <el-col :span="6"><el-form-item label="模型"><el-select v-model="kling.image_model" size="small" style="width:100%" ><el-option v-for="m in imageModels" :key="m.value" :label="m.label" :value="m.value"/></el-select></el-form-item></el-col>
            <el-col :span="5"><el-form-item label="分辨率"><el-select v-model="kling.image_resolution" size="small" style="width:100%" ><el-option v-for="r in resolutions" :key="r.value" :label="r.label" :value="r.value"/></el-select></el-form-item></el-col>
            <el-col :span="5"><el-form-item label="画面比例"><el-select v-model="kling.image_aspect_ratio" size="small" style="width:100%" ><el-option v-for="r in aspectRatios" :key="r.value" :label="r.label" :value="r.value"/></el-select></el-form-item></el-col>
          </el-row>
        </div>
        <div class="config-group"><h4>🎥 视频配置</h4>
          <el-row :gutter="12">
            <el-col :span="6"><el-form-item label="模型"><el-select v-model="kling.video_model" size="small" style="width:100%" @change="validateConfig" ><el-option v-for="m in videoModels" :key="m.value" :label="m.label" :value="m.value"><span style="display:flex;align-items:center;gap:8px">{{ m.label }}<span v-if="m.sound" style="color:var(--accent);font-size:12px">🔊 有声</span><span v-else style="color:var(--text-tertiary);font-size:12px">🔇 无声</span></span></el-option></el-select></el-form-item></el-col>
            <el-col :span="4"><el-form-item label="画质"><el-select v-model="kling.video_mode" size="small" style="width:100%" ><el-option v-for="m in videoModes" :key="m.value" :label="m.label" :value="m.value"/></el-select></el-form-item></el-col>
            <el-col :span="4"><el-form-item label="时长(秒)"><el-select v-model="kling.video_duration" size="small" style="width:100%" @change="validateConfig" ><el-option v-for="d in durations" :key="d.value" :label="d.label" :value="d.value"/></el-select></el-form-item></el-col>
            <el-col :span="5"><el-form-item label="画面比例"><el-select v-model="kling.video_aspect_ratio" size="small" style="width:100%" ><el-option v-for="r in aspectRatios" :key="r.value" :label="r.label" :value="r.value"/></el-select></el-form-item></el-col>
            <el-col :span="3"><el-form-item label="声音"><el-switch v-model="kling.video_sound" active-value="on" inactive-value="off" size="small" :disabled="!currentVideoModel?.sound"/></el-form-item></el-col>
          </el-row>
        </div>
      </div>

      <!-- Warnings -->
      <div v-if="warnings.length" class="warn-panel"><span v-for="w in warnings" :key="w.field||w" class="warn-item">{{ typeof w === 'string' ? w : '⚠ ' + w.message }}</span></div>

      <div class="block-action"><span class="count-hint mono">50 积分 · 余额 {{ auth.user?.credits||0 }}</span><el-button type="primary" size="large" @click="fastCreate" :loading="loading" :disabled="!story.title||!story.content"><span class="btn-icon">⚡</span> 一键生成短剧</el-button></div>
    </div>

    <!-- Fullscreen Progress Overlay -->
    <div v-if="activeStep===3" class="progress-overlay">
      <div class="progress-modal">
        <div class="pm-spinner"><div class="pm-ring"></div><div class="pm-core">◆</div></div>
        <h2 class="pm-title">创作引擎运行中</h2>
        <p class="pm-hint">AI 正在后台处理，可关闭此页面稍后在作品广场查看</p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.create-page{max-width:1000px;margin:0 auto}
.page-hero{margin-bottom:28px;text-align:center}
.hero-title{font-size:34px;font-weight:800;color:var(--text-primary);letter-spacing:-0.02em;margin-bottom:6px}
.hero-sub{color:var(--text-tertiary);font-size:15px;margin-bottom:20px}
.tech-line{height:1px;background:linear-gradient(90deg,transparent,var(--border-accent),transparent);width:200px;margin:0 auto}
.mode-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:32px}
.mode-card{padding:32px;border-radius:var(--radius-lg);cursor:pointer;transition:all var(--transition);position:relative;overflow:hidden}
.mode-card::before{content:'';position:absolute;top:0;left:0;right:0;height:1px;background:linear-gradient(90deg,transparent,var(--accent),transparent);opacity:0;transition:opacity var(--transition)}
.mode-card:hover::before{opacity:0.5}
.mode-card:hover{transform:translateY(-2px)}
.mode-card.glow{border-color:var(--border-glow)}
.mc-icon{font-size:36px;margin-bottom:12px;color:var(--accent)}
.mc-title{font-size:20px;font-weight:700;color:var(--text-primary);margin-bottom:8px}
.mc-desc{color:var(--text-secondary);font-size:13px;line-height:1.7;margin-bottom:16px}
.mc-footer{margin-top:8px}
.steps-track{display:flex;align-items:flex-start;margin-bottom:28px;padding:0 16px}
.st-node{flex:1;display:flex;align-items:center;gap:12px;padding:14px 16px;border-radius:var(--radius);background:var(--bg-surface);border:1px solid var(--border-default);opacity:0.4;transition:all var(--transition);position:relative}
.st-node.on{opacity:1;border-color:var(--border-accent);box-shadow:var(--shadow-glow)}
.st-node.done{opacity:0.7;border-color:var(--success);background:rgba(16,185,129,0.03)}
.st-bullet{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;background:var(--bg-elevated);color:var(--text-tertiary);flex-shrink:0;border:1px solid var(--border-strong)}
.st-node.on .st-bullet{background:var(--accent-dim);color:var(--accent);border-color:var(--border-accent);box-shadow:0 0 12px var(--accent-dim)}
.st-node.done .st-bullet{background:var(--success-dim);color:var(--success);border-color:rgba(16,185,129,0.3)}
.st-info{display:flex;flex-direction:column}
.st-label{font-size:14px;color:var(--text-primary);font-weight:600}
.st-hint{font-size:11px;color:var(--text-tertiary);margin-top:2px}
.st-connector{position:absolute;right:-20px;top:28px;width:20px;height:1px;background:var(--border-default)}
.st-connector.filled{background:var(--success)}
.step-block{margin-bottom:24px;padding:28px;border-radius:var(--radius-lg)}
.step-block.glow{border-color:var(--border-accent)}
.step-block.glow-strong{border-color:var(--border-glow);box-shadow:0 0 40px var(--accent-glow);animation:pulse-glow 4s ease-in-out infinite}
.block-head{display:flex;align-items:center;gap:12px;margin-bottom:24px}
.block-num{font-family:var(--font-mono);font-size:13px;font-weight:700;color:var(--accent);background:var(--accent-dim);padding:4px 10px;border-radius:4px;letter-spacing:0.05em}
.block-head h2{font-size:20px;font-weight:700;color:var(--text-primary)}
.block-hint{color:var(--text-tertiary);font-size:13px;margin-bottom:16px}
.block-action{display:flex;justify-content:flex-end;align-items:center;gap:16px;margin-top:20px;padding-top:16px;border-top:1px solid var(--border-subtle)}
.btn-icon{font-size:16px;margin-right:4px}
.count-hint{color:var(--text-tertiary);font-size:13px}
.script-panel{margin-top:20px;padding:20px;border-radius:var(--radius)}
.sp-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px}
.sp-head h3{color:var(--accent);font-size:15px;font-weight:600}
.sp-text{color:var(--text-secondary);white-space:pre-wrap;font-family:var(--font-body);line-height:1.9;font-size:14px;background:rgba(255,255,255,0.015);padding:16px;border-radius:var(--radius-sm);border:1px solid var(--border-subtle)}
.img-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:12px}
.img-card{cursor:pointer;overflow:hidden;position:relative;transition:all var(--transition);padding:0}
.img-card:hover{transform:translateY(-2px)}
.img-card.sel{border-color:var(--border-glow)!important;box-shadow:0 0 24px var(--accent-glow)}
.img-placeholder{height:180px;display:flex;flex-direction:column;align-items:center;justify-content:center;background:var(--bg-elevated);font-size:36px;color:var(--text-tertiary)}
.img-placeholder p{font-size:11px;margin-top:8px;color:var(--text-tertiary)}
.img-check{position:absolute;top:10px;right:10px;width:26px;height:26px;border-radius:50%;background:var(--accent);color:#000;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;box-shadow:0 0 12px var(--accent)}
.analysis-bar{padding:10px 16px;margin-bottom:16px;border-radius:var(--radius-sm);font-size:12px}
.config-section{display:flex;flex-direction:column;gap:16px;margin-bottom:16px}
.config-group{background:var(--bg-elevated);padding:16px;border-radius:var(--radius);border:1px solid var(--border-subtle)}
.config-group h4{color:var(--accent);font-size:13px;font-weight:600;margin-bottom:12px;font-family:var(--font-mono);letter-spacing:0.03em;text-transform:uppercase}
.warn-panel{display:flex;flex-direction:column;gap:6px;padding:12px 16px;margin-bottom:16px;background:rgba(245,158,11,0.06);border:1px solid rgba(245,158,11,0.2);border-radius:var(--radius-sm)}
.warn-item{color:var(--warning);font-size:12px;line-height:1.6}

/* Progress Overlay */
.progress-overlay{position:fixed;inset:0;z-index:1000;background:rgba(6,8,13,0.92);backdrop-filter:blur(12px);display:flex;align-items:center;justify-content:center}
.progress-modal{text-align:center;max-width:520px;padding:48px}
.pm-spinner{position:relative;width:80px;height:80px;margin:0 auto 28px}
.pm-ring{position:absolute;inset:0;border:2px solid var(--border-strong);border-top-color:var(--accent);border-radius:50%;animation:spin 1.5s linear infinite}
.pm-core{position:absolute;inset:8px;display:flex;align-items:center;justify-content:center;font-size:24px;color:var(--accent);text-shadow:0 0 16px var(--accent)}
@keyframes spin{to{transform:rotate(360deg)}}
.pm-title{font-size:24px;font-weight:800;color:var(--text-bright);margin-bottom:24px}
.pm-bar{max-width:560px;margin:0 auto 20px}
.pm-bar :deep(.el-progress-bar__outer){height:25px!important;border-radius:20px!important}
.pm-bar :deep(.el-progress-bar__innerText){font-size:13px!important;font-weight:700;color:#fff!important}
.pm-eta{font-size:12px;color:var(--text-tertiary);margin-bottom:20px}
.pm-status{font-size:14px;color:var(--accent);margin-bottom:24px;min-height:20px}
.pm-steps{display:flex;align-items:center;justify-content:center;gap:6px;flex-wrap:wrap;margin-bottom:24px}
.pm-steps span{font-size:11px;color:var(--text-tertiary);transition:all var(--transition)}
.pm-steps span.done{color:var(--success)}
.pm-arrow{color:var(--border-strong)!important;font-size:10px}
.pm-hint{font-size:12px;color:var(--text-tertiary);font-family:var(--font-mono)}
</style>
