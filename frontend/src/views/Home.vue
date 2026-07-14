<script setup>
import { ref, reactive, computed } from 'vue'
import { ElMessage } from 'element-plus'
import axios from 'axios'
const api = axios.create({ baseURL: '/api', timeout: 30000 })

const videoModels = [{value:'kling-v3-turbo',label:'Kling 3.0 Turbo (快速·有声·推荐)'},{value:'kling-v3',label:'Kling 3.0 (旗舰·4K)'},{value:'kling-v3-omni',label:'Kling 3.0 Omni (视频编辑·4K)'},{value:'kling-o1',label:'Kling O1'},{value:'kling-v2-6',label:'Kling 2.6 (经典)'}]
const ratios = [{value:'16:9',label:'16:9 横屏'},{value:'9:16',label:'9:16 竖屏(抖音)'},{value:'1:1',label:'1:1 方形'}]
const durations = [5,6,7,8,9,10,11,12,13,14,15]
const adStyles = ['霸总','重生','机甲','绿茶','职场','家庭','逆袭','甜宠','虐恋','穿越','修仙','武侠','悬疑','恐怖','喜剧','青春','校园','古风','都市','奇幻','系统','末日','种田','宫斗','复仇']

const config = reactive({ image_model:'kling-v3-omni', image_resolution:'2k', video_model:'kling-v3-turbo', aspect_ratio:'9:16', duration:12, image_n:1 })
const story = ref(''); const refImages = ref([]); const refPreviews = ref([])
const recommendLoading = ref(false); const scenario = ref('')
const remakeUrl = ref(''); const adForm = reactive({ name:'', points:'', style:'', images:[] })
const showRemake = ref(false); const showAd = ref(false)
const imageCost = computed(() => 8 * config.image_n)
const videoCost = computed(() => { const r={std:1,pro:2,'4k':5}; return (r['pro']||2)*config.duration })
const totalCost = computed(() => imageCost.value + videoCost.value)

function handleImageUpload(files) { Array.from(files).slice(0,5-refImages.value.length).forEach(f=>{const r=new FileReader();r.onload=e=>{refPreviews.value.push(e.target.result);refImages.value.push(e.target.result)};r.readAsDataURL(f)}) }
function removeImage(i) { refPreviews.value.splice(i,1); refImages.value.splice(i,1) }

function openScenario(type) {
  if (type==='remake') showRemake.value=true
  if (type==='ad') showAd.value=true
}
function confirmRemake() { if(!remakeUrl.value.trim()){ElMessage.warning('请输入视频链接');return}; story.value=`【爆款复刻】参考视频：${remakeUrl.value}`; showRemake.value=false; ElMessage.success('已填入创作框') }
function confirmAd() { if(!adForm.name.trim()){ElMessage.warning('请填写产品名称');return}; story.value=`【剧情广告】产品：${adForm.name}，卖点：${adForm.points}，风格：${adForm.style||'随机'}`; showAd.value=false; ElMessage.success('已填入创作框') }

async function aiRecommend() {
  if (!story.value.trim()) { ElMessage.warning('请先输入故事'); return }
  recommendLoading.value = true
  try { const { data } = await api.post('/kling/recommend', { content: story.value }); if (data.recommended) { config.video_model=data.recommended.video_model||config.video_model; config.duration=parseInt(data.recommended.video_duration)||config.duration; config.aspect_ratio=data.recommended.video_aspect_ratio||config.aspect_ratio } ElMessage.success('AI已分析并推荐配置') } catch(e) { ElMessage.error('推荐失败') }
  recommendLoading.value = false
}
</script>

<template>
  <div class="home">
    <h2>快速创作</h2><p class="sub">输入你的创作想法，AI帮你完成从灵感到成片</p>

    <!-- Input -->
    <div class="input-card glass-panel">
      <el-input v-model="story" type="textarea" :rows="6" placeholder="输入你的故事大纲或创作想法...&#10;&#10;例如：都市白领林晨被裁员后捡到一块旧怀表，时间倒流回三年前..." class="story-input"/>
    </div>

    <!-- Upload bar -->
    <div class="upload-bar">
      <label class="upload-btn"><input type="file" accept="image/*" multiple hidden @change="e=>{handleImageUpload(e.target.files);e.target.value=''}"/>📎 上传参考图</label>
      <span class="upload-hint" v-if="refImages.length">已上传 {{ refImages.length }}/5 张</span>
      <span class="upload-hint" v-else>最多5张</span>
    </div>
    <div v-if="refPreviews.length" class="ref-row"><div v-for="(p,i) in refPreviews" :key="i" class="ref-thumb"><img :src="p"/><span class="ref-del" @click="removeImage(i)">✕</span></div></div>

    <!-- Config -->
    <div class="config-row">
      <el-select v-model="config.video_model" size="large" style="width:240px"><el-option v-for="m in videoModels" :key="m.value" :label="m.label" :value="m.value"/></el-select>
      <el-select v-model="config.aspect_ratio" size="large" style="width:170px"><el-option v-for="r in ratios" :key="r.value" :label="r.label" :value="r.value"/></el-select>
      <el-select v-model="config.duration" size="large" style="width:120px"><el-option v-for="d in durations" :key="d" :label="d+'秒'" :value="d"/></el-select>
      <el-button size="large" :loading="recommendLoading" @click="aiRecommend">🤖 AI推荐分析</el-button>
    </div>

    <!-- Scenarios -->
    <div class="scenario-bar"><span class="sc-label">应用场景：</span><span class="sc-chip" @click="$message.info('开发中')">🎬 短剧Studio</span><span class="sc-chip" :class="{on:showRemake}" @click="openScenario('remake')">🔥 爆款复刻</span><span class="sc-chip" :class="{on:showAd}" @click="openScenario('ad')">📢 剧情广告</span></div>

    <!-- Submit -->
    <div class="submit-bar"><span class="cost-text">预计 {{ totalCost }} 积分</span><el-button type="primary" size="large" @click="$message.success('创作任务已提交')">🚀 开始创作</el-button></div>

    <!-- Remake Overlay -->
    <div v-if="showRemake" class="overlay" @click.self="showRemake=false"><div class="overlay-card glass-panel"><h3>🔥 爆款复刻</h3><p class="sc-hint" style="margin-bottom:12px">粘贴视频链接或上传本地视频作为参考</p><el-input v-model="remakeUrl" placeholder="抖音/快手/小红书视频链接..." size="large"/><div style="text-align:center;color:var(--text-tertiary);margin:12px 0;font-size:13px">— 或者 —</div><input type="file" accept="video/*"/><div class="overlay-actions"><el-button @click="showRemake=false">取消</el-button><el-button type="primary" @click="confirmRemake">确定</el-button></div></div></div>

    <!-- Ad Overlay -->
    <div v-if="showAd" class="overlay" @click.self="showAd=false"><div class="overlay-card glass-panel"><h3>📢 剧情广告</h3>
      <el-row :gutter="12"><el-col :span="12"><el-input v-model="adForm.name" placeholder="产品名称" size="large"/></el-col><el-col :span="12"><el-input v-model="adForm.points" placeholder="产品卖点" size="large"/></el-col></el-row>
      <div style="margin-top:12px"><input type="file" accept="image/*" multiple/><span style="color:var(--text-tertiary);font-size:12px;margin-left:8px">1-10张产品图</span></div>
      <div style="margin-top:12px"><span style="color:var(--text-secondary);font-size:13px">剧情风格：</span><div class="style-grid"><span v-for="s in adStyles" :key="s" class="style-chip" :class="{on:adForm.style===s}" @click="adForm.style=s">{{ s }}</span></div></div>
      <div class="overlay-actions"><el-button @click="showAd=false">取消</el-button><el-button type="primary" @click="confirmAd">确定</el-button></div>
    </div></div>
  </div>
</template>

<style scoped>
.home{max-width:900px;margin:0 auto;padding:24px 0}.home h2{font-size:28px;font-weight:800;color:var(--text-primary)}.sub{color:var(--text-secondary);font-size:14px;margin:6px 0 20px}
.upload-bar{display:flex;align-items:center;gap:12px;margin-bottom:12px}.upload-btn{cursor:pointer;padding:8px 18px;border-radius:8px;font-size:14px;background:var(--bg-elevated);border:1px solid var(--border-strong);color:var(--text-secondary);transition:all var(--transition)}.upload-btn:hover{color:var(--accent);border-color:var(--border-accent)}.upload-hint{font-size:12px;color:var(--text-tertiary)}
.ref-row{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:8px}.ref-thumb{position:relative;width:80px;height:80px;border-radius:6px;overflow:hidden;border:1px solid var(--border-strong)}.ref-thumb img{width:100%;height:100%;object-fit:cover}.ref-del{position:absolute;top:2px;right:2px;width:18px;height:18px;border-radius:50%;background:rgba(0,0,0,0.7);color:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:10px}
.input-card{padding:12px 16px;margin-bottom:16px}.story-input :deep(textarea){background:transparent!important;border:none!important;font-size:15px!important;line-height:1.8!important;resize:none!important}
.config-row{display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin-bottom:16px}
.scenario-bar{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:20px}.sc-label{font-size:13px;color:var(--text-tertiary)}.sc-chip{padding:6px 14px;border-radius:20px;font-size:13px;cursor:pointer;border:1px solid var(--border-strong);color:var(--text-secondary);transition:all var(--transition)}.sc-chip:hover,.sc-chip.on{border-color:var(--accent);color:var(--accent);background:var(--accent-dim)}
.sc-hint{font-size:12px;color:var(--text-tertiary);margin:8px 0}
.style-grid{display:flex;flex-wrap:wrap;gap:6px;margin-top:8px}.style-chip{padding:4px 12px;border-radius:14px;font-size:12px;cursor:pointer;border:1px solid var(--border-strong);color:var(--text-tertiary)}.style-chip.on{border-color:var(--accent);color:var(--accent);background:var(--accent-dim)}
.submit-bar{display:flex;justify-content:flex-end;align-items:center;gap:16px;padding-top:20px;border-top:1px solid var(--border-subtle)}.cost-text{font-size:14px;color:var(--warning);font-family:var(--font-mono)}
.overlay{position:fixed;inset:0;z-index:1000;background:rgba(6,8,13,0.85);backdrop-filter:blur(8px);display:flex;align-items:center;justify-content:center}.overlay-card{width:560px;max-width:90vw;padding:28px}.overlay-card h3{font-size:20px;color:var(--text-primary);margin-bottom:20px}.overlay-actions{display:flex;justify-content:flex-end;gap:12px;margin-top:20px;padding-top:16px;border-top:1px solid var(--border-subtle)}
</style>
