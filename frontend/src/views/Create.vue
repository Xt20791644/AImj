<script setup>
import { ref, reactive, onMounted, onUnmounted } from 'vue'
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

const options = ref(null); const presets = ref({})
const kling = reactive({ preset:'short_drama', image_model:'kling-v3', image_resolution:'2k', image_aspect_ratio:'9:16', image_n:3, video_model:'kling-v2-6', video_mode:'pro', video_duration:'5', video_sound:'off', video_negative_prompt:'' })
const generatedImages = ref([])
const loading = ref(false); const workId = ref(null); let pollTimer = null

onMounted(async()=>{try{const{data}=await api.get('/kling/options');options.value=data;presets.value=data.presets||{}}catch(e){}})
function applyPreset(k){const p=presets.value[k];if(!p)return;Object.assign(kling,{preset:k,image_model:p.image_model,image_resolution:p.image_resolution,image_aspect_ratio:p.image_aspect_ratio,video_model:p.video_model,video_mode:p.video_mode,video_duration:p.video_duration,video_sound:p.video_sound||'off'})}

async function generateScript(){if(!story.title.trim()||!story.content.trim()){ElMessage.warning('请填写故事');return};loading.value=true;await new Promise(r=>setTimeout(r,1200));script.value=`【剧本】《${story.title}》\n\n`+story.content.split('\n').filter(l=>l.trim()).map((l,i)=>`第${i+1}场\n${l.trim()}\n`).join('\n');scriptGenerated.value=true;loading.value=false;ElMessage.success('剧本生成完成')}
async function generateImages(){loading.value=true;await new Promise(r=>setTimeout(r,1500));const n=Math.max(3,Math.min(5,kling.image_n));generatedImages.value=Array.from({length:n},(_,i)=>({id:i+1,label:`场景 ${i+1}`,selected:false}));loading.value=false;ElMessage.success(`生成了 ${n} 张图片`)}
function toggleImage(img){img.selected=!img.selected}
function nextToVideo(){if(!generatedImages.value.filter(i=>i.selected).length){ElMessage.warning('至少选一张');return};activeStep.value=2}
async function finalGenerate(){loading.value=true;try{const result=await api.post('/works',{title:story.title,content:story.content,style:story.style,mode:'fine',script:script.value,kling_config:{...kling}});workId.value=result.id;creditStore.fetchBalance();activeStep.value=3;startPolling();ElMessage.success('已提交')}catch(e){loading.value=false;if(e.response?.status===402){ElMessage.error('积分不足');router.push('/profile')}}}
async function fastCreate(){if(!story.title.trim()||!story.content.trim()){ElMessage.warning('请填写');return};if(!auth.isLoggedIn){router.push('/login');return};loading.value=true;try{const result=await api.post('/works',{title:story.title,content:story.content,style:story.style,mode:'fast',kling_config:{...kling}});workId.value=result.id;creditStore.fetchBalance();activeStep.value=3;startPolling()}catch(e){loading.value=false;if(e.response?.status===402){ElMessage.error('积分不足');router.push('/profile')}}}
function startPolling(){pollTimer=setInterval(async()=>{try{const r=await api.get(`/works/${workId.value}`);if(r.status==='completed'){stopPolling();ElMessage.success('完成！');setTimeout(()=>router.push('/works'),1000)}else if(r.status==='failed'){stopPolling();loading.value=false;ElMessage.error('失败')}}catch(e){}},2000)}
function stopPolling(){if(pollTimer){clearInterval(pollTimer);pollTimer=null}}
onUnmounted(()=>stopPolling())
</script>

<template>
  <div class="create-page page-enter">
    <div class="page-hero">
      <h1 class="hero-title">创作工坊</h1>
      <p class="hero-sub">AI驱动的短剧创作引擎 — 选择模式，开始创作</p>
      <div class="tech-line"></div>
    </div>

    <!-- Mode Selection -->
    <div v-if="!currentMode" class="mode-grid">
      <div class="mode-card glass-panel glow" @click="currentMode='fine'">
        <div class="mc-icon">▣</div><h3 class="mc-title">精细模式</h3>
        <p class="mc-desc">逐步引导创作流程：AI生成剧本可在线编辑 → 配置图片参数 → 选择满意的画面 → 合成视频配音导出</p>
        <div class="mc-footer"><span class="tech-badge">▸ 推荐 · 高质量可控</span></div>
      </div>
      <div class="mode-card glass-panel" @click="currentMode='fast'">
        <div class="mc-icon">▻</div><h3 class="mc-title">快速模式</h3>
        <p class="mc-desc">输入故事大纲，AI接管全部流程：自动拆解分镜 → 生成画面 → 视频合成 → 配音导出，一键出片</p>
        <div class="mc-footer"><span class="tech-badge">▸ 快捷 · 省心省力</span></div>
      </div>
    </div>

    <!-- Fine Mode -->
    <template v-if="currentMode==='fine'">
      <!-- Steps -->
      <div class="steps-track" v-if="activeStep<3">
        <div v-for="(s,i) in steps" :key="i" class="st-node" :class="{done:i<activeStep,on:i===activeStep}">
          <div class="st-bullet">{{ i<activeStep?'✓':i+1 }}</div>
          <div class="st-info"><span class="st-label">{{ s.t }}</span><span class="st-hint">{{ s.d }}</span></div>
          <div v-if="i<2" class="st-connector" :class="{filled:i<activeStep}"></div>
        </div>
      </div>

      <!-- Step 1 -->
      <div v-if="activeStep===0" class="step-block glass-panel glow">
        <div class="block-head"><span class="block-num">01</span><h2>故事剧本</h2></div>
        <el-row :gutter="16"><el-col :span="12"><el-form-item label="作品名称"><el-input v-model="story.title" placeholder="给你的短剧起一个名字" size="large"/></el-form-item></el-col><el-col :span="12"><el-form-item label="视觉风格"><el-select v-model="story.style" size="large" style="width:100%"><el-option v-for="s in [{v:'realistic',l:'真人写实'},{v:'anime',l:'日系动画'},{v:'3d',l:'3D动画'}]" :key="s.v" :label="s.l" :value="s.v"/></el-select></el-form-item></el-col></el-row>
        <el-form-item label="故事大纲"><el-input v-model="story.content" type="textarea" :rows="6" placeholder="输入你的故事大纲，AI将自动生成完整剧本...&#10;&#10;例：都市白领林晨在30岁生日那天被公司裁员，心灰意冷的他在天桥下捡到一块旧怀表。当他拨动表针的那一刻，时间竟倒流回了三年前..."/></el-form-item>
        <div class="block-action"><el-button :loading="loading" :disabled="!story.title||!story.content" size="large" @click="generateScript"><span class="btn-icon">⟳</span> AI 生成剧本</el-button></div>

        <div v-if="scriptGenerated" class="script-panel glass-panel">
          <div class="sp-head"><h3>📜 剧本预览</h3><el-button size="small" text @click="scriptEditing=!scriptEditing">{{ scriptEditing?'完成编辑':'在线编辑' }}</el-button></div>
          <el-input v-if="scriptEditing" v-model="script" type="textarea" :rows="10"/>
          <pre v-else class="sp-text">{{ script }}</pre>
          <div class="block-action"><el-button type="primary" size="large" @click="activeStep=1">进入下一步 → 图片生成</el-button></div>
        </div>
      </div>

      <!-- Step 2 -->
      <div v-if="activeStep===1" class="step-block glass-panel glow">
        <div class="block-head"><span class="block-num">02</span><h2>图片生成</h2></div>
        <div v-if="!generatedImages.length">
          <el-row :gutter="16"><el-col :span="8"><el-form-item label="生成模型"><el-select v-model="kling.image_model" size="large" style="width:100%" v-if="options"><el-option v-for="(m,k) in options.image_models" :key="k" :label="m.name" :value="k"/></el-select></el-form-item></el-col><el-col :span="5"><el-form-item label="输出分辨率"><el-select v-model="kling.image_resolution" size="large" style="width:100%" v-if="options"><el-option v-for="(l,v) in options.image_resolutions" :key="v" :label="l" :value="v"/></el-select></el-form-item></el-col><el-col :span="5"><el-form-item label="画面比例"><el-select v-model="kling.image_aspect_ratio" size="large" style="width:100%" v-if="options"><el-option v-for="(l,v) in options.image_aspect_ratios" :key="v" :label="l" :value="v"/></el-select></el-form-item></el-col><el-col :span="6"><el-form-item label="生成数量"><el-input-number v-model="kling.image_n" :min="3" :max="5" size="large" style="width:100%"/></el-form-item></el-col></el-row>
          <div class="block-action"><el-button type="primary" size="large" @click="generateImages" :loading="loading"><span class="btn-icon">▣</span> 开始生成图片</el-button></div>
        </div>
        <div v-else>
          <p class="block-hint">点击选择满意的图片，至少选择一张继续</p>
          <div class="img-grid">
            <div v-for="img in generatedImages" :key="img.id" class="img-card glass-panel" :class="{sel:img.selected}" @click="toggleImage(img)">
              <div class="img-placeholder">▣<p>{{ img.label }}</p></div>
              <div v-if="img.selected" class="img-check">✓</div>
            </div>
          </div>
          <div class="block-action"><span class="count-hint">已选 {{ generatedImages.filter(i=>i.selected).length }} / {{ generatedImages.length }}</span><el-button type="primary" size="large" @click="nextToVideo">进入下一步 → 视频合成</el-button></div>
        </div>
      </div>

      <!-- Step 3 -->
      <div v-if="activeStep===2" class="step-block glass-panel glow">
        <div class="block-head"><span class="block-num">03</span><h2>视频合成</h2></div>
        <el-row :gutter="16"><el-col :span="8"><el-form-item label="视频模型"><el-select v-model="kling.video_model" size="large" style="width:100%" v-if="options"><el-option v-for="(m,k) in options.video_models" :key="k" :label="m.name" :value="k"/></el-select></el-form-item></el-col><el-col :span="5"><el-form-item label="画质模式"><el-select v-model="kling.video_mode" size="large" style="width:100%" v-if="options"><el-option v-for="(l,v) in options.video_modes" :key="v" :label="l" :value="v"/></el-select></el-form-item></el-col><el-col :span="5"><el-form-item label="视频时长"><el-select v-model="kling.video_duration" size="large" style="width:100%" v-if="options"><el-option v-for="(l,v) in options.video_durations" :key="v" :label="l" :value="v"/></el-select></el-form-item></el-col><el-col :span="6"><el-form-item label="声音"><el-switch v-model="kling.video_sound" active-value="on" inactive-value="off" active-text="开" inactive-text="关"/></el-form-item></el-col></el-row>
        <el-form-item label="负向提示词（排除不需要的元素）"><el-input v-model="kling.video_negative_prompt" placeholder="画面抖动、变形、闪烁、模糊"/></el-form-item>
        <div class="block-action"><span class="count-hint mono">消耗 50 积分 · 余额 {{ auth.user?.credits||0 }}</span><el-button type="primary" size="large" @click="finalGenerate" :loading="loading"><span class="btn-icon">▶</span> 开始生成短剧</el-button></div>
      </div>
    </template>

    <!-- Fast Mode -->
    <div v-if="currentMode==='fast'" class="step-block glass-panel glow">
      <div class="block-head"><span class="block-num">⚡</span><h2>快速模式 · 一键生成</h2></div>
      <p class="block-hint">输入故事大纲，AI自动完成全流程，无需手动干预</p>
      <el-row :gutter="16"><el-col :span="12"><el-form-item label="作品名称"><el-input v-model="story.title" placeholder="给你的短剧起一个名字" size="large"/></el-form-item></el-col><el-col :span="12"><el-form-item label="质量预设"><el-select v-model="kling.preset" size="large" style="width:100%" @change="applyPreset"><el-option v-for="(p,k) in presets" :key="k" :label="p.name" :value="k"/></el-select></el-form-item></el-col></el-row>
      <el-form-item label="故事大纲"><el-input v-model="story.content" type="textarea" :rows="8" placeholder="输入故事大纲，剩下的全部交给AI处理..."/></el-form-item>
      <div class="block-action"><span class="count-hint mono">50 积分 · 余额 {{ auth.user?.credits||0 }}</span><el-button type="primary" size="large" @click="fastCreate" :loading="loading" :disabled="!story.title||!story.content"><span class="btn-icon">⚡</span> 一键生成短剧</el-button></div>
    </div>

    <!-- Progress -->
    <div v-if="activeStep===3" class="step-block glass-panel glow-strong" style="text-align:center;padding:48px">
      <h2 style="color:var(--accent);font-size:22px;margin-bottom:8px">创作引擎运行中</h2>
      <p style="color:var(--text-tertiary);margin-bottom:28px;font-size:14px">AI正在处理你的短剧，请稍候片刻...</p>
      <el-progress :percentage="50" :stroke-width="16" :indeterminate="true"/>
      <p style="color:var(--text-tertiary);margin-top:20px;font-size:12px;font-family:var(--font-mono)">STATUS: PROCESSING · 可离开页面稍后查看</p>
    </div>
  </div>
</template>

<style scoped>
.create-page{max-width:960px;margin:0 auto}
.page-hero{margin-bottom:32px;text-align:center}
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
.st-connector{flex:1;height:1px;background:var(--border-default);margin:0 -8px;position:absolute;right:-20px;top:28px;width:20px}
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
</style>
