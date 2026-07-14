<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import api from '../../api'
const router = useRouter()
const works = ref([]); const selectedWorkId = ref(null)
const episodes = ref([]); const loading = ref(false)
const addDialog = ref(false); const newEp = ref({title:'',episode_number:1})
onMounted(async()=>{try{const r=await api.get('/works',{params:{my:1}});works.value=r.data||r}catch(e){}})
async function selectWork(id){selectedWorkId.value=id;loading.value=true;try{const r=await api.get(`/studio/works/${id}/episodes`);episodes.value=Array.isArray(r)?r:r.data||[]}catch(e){}loading.value=false}
function openAdd(){newEp.value={title:'',episode_number:(episodes.value.length||0)+1};addDialog.value=true}
async function addEpisode(){try{await api.post(`/studio/works/${selectedWorkId.value}/episodes`,newEp.value);addDialog.value=false;selectWork(selectedWorkId.value);ElMessage.success('已添加')}catch(e){ElMessage.error('添加失败')}}
</script>
<template>
  <div>
    <div class="dash-head"><h1>剧集管理</h1></div><div class="tech-line"></div>
    <el-select v-model="selectedWorkId" placeholder="选择项目" size="large" style="width:300px;margin-bottom:20px" @change="selectWork"><el-option v-for="w in works" :key="w.id" :label="w.title" :value="w.id"/></el-select>
    <div v-if="!selectedWorkId" class="empty-hint"><h3>请先选择一个项目</h3></div>
    <div v-else v-loading="loading">
      <el-button type="primary" @click="openAdd" style="margin-bottom:16px">+ 添加剧集</el-button>
      <div v-if="episodes.length===0" class="empty-hint"><p>暂无剧集</p></div>
      <div v-else class="ep-list"><div v-for="ep in episodes" :key="ep.id" class="ep-card glass-panel" @click="router.push(`/studio/episodes/${ep.id}`)"><div class="ep-num">EP{{ ep.episode_number }}</div><div class="ep-info"><h4>{{ ep.title }}</h4><div class="ep-meta"><span>{{ ep.status==='completed'?'✅完成':ep.status==='draft'?'📝草稿':'⏳制作中' }}</span><span>{{ ep.duration||0 }}秒</span></div></div><div class="ep-arrow">→</div></div></div>
    </div>
    <el-dialog v-model="addDialog" title="添加剧集" width="400px"><el-form label-position="top"><el-form-item label="集数"><el-input-number v-model="newEp.episode_number" :min="1" size="large" style="width:100%"/></el-form-item><el-form-item label="标题"><el-input v-model="newEp.title" size="large" placeholder="第1集·逆袭的开始"/></el-form-item></el-form><template #footer><el-button @click="addDialog=false">取消</el-button><el-button type="primary" @click="addEpisode">确认</el-button></template></el-dialog>
  </div>
</template>
<style scoped>.dash-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px}.dash-head h1{font-size:24px;font-weight:700;color:var(--text-primary)}.tech-line{height:1px;background:linear-gradient(90deg,var(--border-accent),transparent);margin-bottom:24px}.empty-hint{text-align:center;padding:40px 0;color:var(--text-tertiary)}.empty-hint h3{color:var(--text-primary)}.ep-list{display:flex;flex-direction:column;gap:8px}.ep-card{display:flex;align-items:center;gap:16px;padding:16px 20px;cursor:pointer;transition:all var(--transition);border-radius:var(--radius)}.ep-card:hover{border-color:var(--border-accent)!important;transform:translateX(4px)}.ep-num{font-family:var(--font-mono);font-size:14px;font-weight:700;color:var(--accent);min-width:50px}.ep-info{flex:1}.ep-info h4{font-size:15px;color:var(--text-primary);margin-bottom:4px}.ep-meta{display:flex;gap:12px;font-size:11px}.ep-arrow{color:var(--text-tertiary);font-size:18px}</style>
