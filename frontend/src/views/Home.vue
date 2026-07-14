<script setup>
import { ref, reactive, computed } from 'vue'
import { ElMessage } from 'element-plus'
import axios from 'axios'
const api = axios.create({ baseURL: '/api', timeout: 30000 })

const imageModels = [{value:'kling-v3',label:'Kling Image 3.0 (推荐)'},{value:'kling-v3-omni',label:'Kling Image 3.0 Omni (4K)'},{value:'kling-v2-1',label:'Kling Image 2.1'},{value:'kling-v2',label:'Kling Image 2.0'},{value:'kling-v1',label:'Kling Image 1.0 (入门)'}]
const videoModels = [{value:'kling-v3-turbo',label:'Kling 3.0 Turbo (快速·有声·推荐)'},{value:'kling-v3',label:'Kling 3.0 (旗舰·4K)'},{value:'kling-v3-omni',label:'Kling 3.0 Omni (视频编辑·4K)'},{value:'kling-o1',label:'Kling O1'},{value:'kling-v2-6',label:'Kling 2.6 (经典)'}]
const ratios = [{value:'16:9',label:'16:9 横屏'},{value:'9:16',label:'9:16 竖屏(抖音)'},{value:'1:1',label:'1:1 方形'}]
const durations = [5,6,7,8,9,10,11,12,13,14,15]
const adStyles = ['霸总','重生','机甲','绿茶','职场','家庭','逆袭','甜宠','虐恋','穿越','修仙','武侠','悬疑','恐怖','喜剧','青春','校园','古风','都市','奇幻','系统','末日','种田','宫斗','复仇']

const config = reactive({ image_model:'kling-v3', video_model:'kling-v3-turbo', aspect_ratio:'9:16', duration:12, image_n:1 })
const story = ref(''); const refImages = ref([]); const refPreviews = ref([])
const recommendLoading = ref(false); const scenario = ref('')
const remakeUrl = ref(''); const adForm = reactive({ name:'', points:'', style:'', images:[] })
const imageCost = computed(() => { const r={'kling-v3':8,'kling-v3-omni':8,'kling-v2-1':4,'kling-v2':4,'kling-v1':2}; return (r[config.image_model]||8)*config.image_n })
const videoCost = computed(() => { const r={std:1,pro:2,'4k':5}; return (r['pro']||2)*config.duration })
const totalCost = computed(() => imageCost.value + videoCost.value)

function handleImageUpload(files) { Array.from(files).forEach(f=>{const r=new FileReader();r.onload=e=>{refPreviews.value.push(e.target.result);refImages.value.push(e.target.result)};r.readAsDataURL(f)}) }
function removeImage(i) { refPreviews.value.splice(i,1); refImages.value.splice(i,1) }
function selectScenario(type) { scenario.value = scenario.value===type ? '' : type }

async function aiRecommend() {
  if (!story.value.trim()) { ElMessage.warning('请先输入故事'); return }
  recommendLoading.value = true
  try { const { data } = await api.post('/kling/recommend', { content: story.value }); if (data.recommended) { config.image_model=data.recommended.image_model||config.image_model; config.video_model=data.recommended.video_model||config.video_model; config.duration=parseInt(data.recommended.video_duration)||config.duration; config.aspect_ratio=data.recommended.video_aspect_ratio||config.aspect_ratio } ElMessage.success('AI已分析并推荐配置'); if (data.warnings?.length) data.warnings.forEach(w=>ElMessage.warning(w)) } catch(e) { ElMessage.error('推荐失败') }
  recommendLoading.value = false
}
</script>

<template>
  <div class="home">
    <h2>快速创作</h2><p class="sub">输入你的创作想法，AI帮你完成从灵感到成片</p>
    <div class="input-card glass-panel">
      <div class="input-toolbar"><label class="tool-btn"><input type="file" accept="image/*" multiple hidden @change="e=>{handleImageUpload(e.target.files);e.target.value=''}"/>📎 上传图片</label></div>
      <el-input v-model="story" type="textarea" :rows="6" placeholder="输入你的故事大纲或创作想法...&#10;&#10;例如：都市白领林晨被裁员后捡到一块旧怀表，时间倒流回三年前..." class="story-input"/>
      <div v-if="refPreviews.length" class="ref-row"><div v-for="(p,i) in refPreviews" :key="i" class="ref-thumb"><img :src="p"/><span class="ref-del" @click="removeImage(i)">✕</span></div></div>
    </div>
    <div class="config-row">
      <el-select v-model="config.image_model" size="large" style="width:200px"><el-option v-for="m in imageModels" :key="m.value" :label="m.label" :value="m.value"/></el-select>
      <el-select v-model="config.video_model" size="large" style="width:220px"><el-option v-for="m in videoModels" :key="m.value" :label="m.label" :value="m.value"/></el-select>
      <el-select v-model="config.aspect_ratio" size="large" style="width:160px"><el-option v-for="r in ratios" :key="r.value" :label="r.label" :value="r.value"/></el-select>
      <el-select v-model="config.duration" size="large" style="width:110px"><el-option v-for="d in durations" :key="d" :label="d+'秒'" :value="d"/></el-select>
      <el-button size="large" :loading="recommendLoading" @click="aiRecommend">🤖 AI推荐分析</el-button>
    </div>
    <div class="scenario-bar"><span class="sc-label">应用场景：</span><span class="sc-chip" :class="{on:scenario==='studio'}" @click="selectScenario('studio')">🎬 短剧Studio</span><span class="sc-chip" :class="{on:scenario==='remake'}" @click="selectScenario('remake')">🔥 爆款复刻</span><span class="sc-chip" :class="{on:scenario==='ad'}" @click="selectScenario('ad')">📢 剧情广告</span></div>
    <div v-if="scenario==='remake'" class="sc-panel glass-panel"><h3>🔥 爆款复刻</h3><el-input v-model="remakeUrl" placeholder="粘贴抖音/快手/小红书视频链接..." size="large"/><p class="sc-hint">或本地上传参考视频</p><input type="file" accept="video/*"/></div>
    <div v-if="scenario==='ad'" class="sc-panel glass-panel">
      <h3>📢 剧情广告</h3>
      <el-row :gutter="12"><el-col :span="12"><el-input v-model="adForm.name" placeholder="产品名称" size="large"/></el-col><el-col :span="12"><el-input v-model="adForm.points" placeholder="产品卖点" size="large"/></el-col></el-row>
      <div style="margin-top:12px"><input type="file" accept="image/*" multiple/><span style="color:var(--text-tertiary);font-size:12px;margin-left:8px">至少1张，最多10张</span></div>
      <div style="margin-top:12px"><span style="color:var(--text-secondary);font-size:13px">剧情风格：</span><div class="style-grid"><span v-for="s in adStyles" :key="s" class="style-chip" :class="{on:adForm.style===s}" @click="adForm.style=s">{{ s }}</span></div></div>
    </div>
    <div class="submit-bar"><span class="cost-text">预计 {{ totalCost }} 积分</span><el-button type="primary" size="large" @click="$message.success('创作任务已提交')">🚀 开始创作</el-button></div>
  </div>
</template>

<style scoped>
.home{max-width:900px;margin:0 auto;padding:24px 0}.home h2{font-size:28px;font-weight:800;color:var(--text-primary)}.sub{color:var(--text-secondary);font-size:14px;margin:6px 0 24px}
.input-card{padding:16px;margin-bottom:16px}.input-toolbar{display:flex;gap:8px;margin-bottom:8px}.tool-btn{cursor:pointer;padding:6px 14px;border-radius:6px;font-size:14px;background:var(--bg-elevated);border:1px solid var(--border-strong);color:var(--text-secondary)}.tool-btn:hover{color:var(--accent);border-color:var(--border-accent)}
.story-input :deep(textarea){background:transparent!important;border:none!important;font-size:15px!important;line-height:1.8!important;resize:none!important}
.ref-row{display:flex;gap:8px;flex-wrap:wrap;margin-top:8px}.ref-thumb{position:relative;width:80px;height:80px;border-radius:6px;overflow:hidden;border:1px solid var(--border-strong)}.ref-thumb img{width:100%;height:100%;object-fit:cover}.ref-del{position:absolute;top:2px;right:2px;width:18px;height:18px;border-radius:50%;background:rgba(0,0,0,0.7);color:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:10px}
.config-row{display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin-bottom:16px}
.scenario-bar{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:16px}.sc-label{font-size:13px;color:var(--text-tertiary)}.sc-chip{padding:6px 14px;border-radius:20px;font-size:13px;cursor:pointer;border:1px solid var(--border-strong);color:var(--text-secondary);transition:all var(--transition)}.sc-chip:hover,.sc-chip.on{border-color:var(--accent);color:var(--accent);background:var(--accent-dim)}
.sc-panel{padding:20px;margin-bottom:16px}.sc-panel h3{font-size:16px;color:var(--text-primary);margin-bottom:12px}.sc-hint{font-size:12px;color:var(--text-tertiary);margin:8px 0}
.style-grid{display:flex;flex-wrap:wrap;gap:6px;margin-top:8px}.style-chip{padding:4px 12px;border-radius:14px;font-size:12px;cursor:pointer;border:1px solid var(--border-strong);color:var(--text-tertiary)}.style-chip.on{border-color:var(--accent);color:var(--accent);background:var(--accent-dim)}
.submit-bar{display:flex;justify-content:flex-end;align-items:center;gap:16px;padding-top:20px;border-top:1px solid var(--border-subtle)}.cost-text{font-size:14px;color:var(--warning);font-family:var(--font-mono)}
</style>
