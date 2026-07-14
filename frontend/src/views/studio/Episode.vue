<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import api from '../../api'

const route = useRoute(); const router = useRouter()
const episode = ref(null); const storyboards = ref([]); const loading = ref(false)
const editDialog = ref(false); const editingShot = ref({})
const shotTypes = ['wide','medium','close-up','extreme-close-up']
const cameraMoves = ['','simple','down_back','forward_up','right_turn_forward','left_turn_forward']

onMounted(async () => {
  const epId = route.params.id; loading.value = true
  try { const r = await api.get(`/studio/works/0/episodes/${epId}`); episode.value = r; storyboards.value = r.storyboards || [] } catch(e) {}
  loading.value = false
})

function openShotEditor(s = null) {
  editingShot.value = s ? { ...s } : { shot_type:'medium', duration:5, scene_number:1, shot_number:(storyboards.value.length||0)+1 }
  editDialog.value = true
}
async function saveShot() {
  try {
    if (editingShot.value.id) await api.put(`/studio/episodes/${route.params.id}/storyboards/${editingShot.value.id}`, editingShot.value)
    else await api.post(`/studio/episodes/${route.params.id}/storyboards`, editingShot.value)
    editDialog.value = false; location.reload(); ElMessage.success('已保存')
  } catch(e) { ElMessage.error('保存失败') }
}
async function deleteShot(s) {
  try { await api.delete(`/studio/episodes/${route.params.id}/storyboards/${s.id}`); storyboards.value = storyboards.value.filter(x => x.id !== s.id); ElMessage.success('已删除') } catch(e) { ElMessage.error('删除失败') }
}
</script>

<template>
  <div v-loading="loading">
    <div class="dash-head">
      <div><el-button text @click="router.push('/studio/episodes')">← 返回剧集</el-button><h1 style="margin-top:8px">{{ episode?.title || '分镜编辑' }}</h1></div>
      <el-button type="primary" @click="openShotEditor()">+ 添加镜头</el-button>
    </div><div class="tech-line"></div>

    <div v-if="storyboards.length===0" class="empty-hint"><p>暂无分镜，点击"添加镜头"开始</p></div>
    <div v-else class="sb-list">
      <div v-for="s in storyboards" :key="s.id" class="sb-card glass-panel">
        <div class="sb-header"><span class="sb-num mono">S{{ s.scene_number }}·SH{{ s.shot_number }}</span><span class="sb-type">{{ s.shot_type }}</span><span class="sb-dur">{{ s.duration }}s</span><span v-if="s.camera_movement" class="sb-cam">{{ s.camera_movement }}</span></div>
        <div class="sb-body"><div class="sb-preview" v-if="s.output_image"><img :src="s.output_image" class="sb-img"/></div><div class="sb-preview" v-else><span class="sb-placeholder">🎬</span></div><div class="sb-content"><p class="sb-prompt">{{ (s.prompt||'无提示词').substring(0,200) }}</p><p class="sb-dialogue" v-if="s.dialogue">💬 {{ (s.dialogue||'').substring(0,100) }}</p></div></div>
        <div class="sb-actions"><el-button size="small" @click="openShotEditor(s)">编辑</el-button><el-button size="small" type="danger" @click="deleteShot(s)">删除</el-button></div>
      </div>
    </div>

    <el-dialog v-model="editDialog" :title="editingShot.id?'编辑镜头':'添加镜头'" width="600px">
      <el-form label-position="top">
        <el-row :gutter="12"><el-col :span="6"><el-form-item label="场景号"><el-input-number v-model="editingShot.scene_number" :min="1" size="large" style="width:100%"/></el-form-item></el-col><el-col :span="6"><el-form-item label="镜头号"><el-input-number v-model="editingShot.shot_number" :min="1" size="large" style="width:100%"/></el-form-item></el-col><el-col :span="6"><el-form-item label="景别"><el-select v-model="editingShot.shot_type" size="large" style="width:100%"><el-option v-for="t in shotTypes" :key="t" :label="t" :value="t"/></el-select></el-form-item></el-col><el-col :span="6"><el-form-item label="时长(秒)"><el-input-number v-model="editingShot.duration" :min="1" :max="15" size="large" style="width:100%"/></el-form-item></el-col></el-row>
        <el-form-item label="运镜"><el-select v-model="editingShot.camera_movement" size="large" style="width:100%" clearable><el-option v-for="c in cameraMoves" :key="c" :label="c||'无'" :value="c||null"/></el-select></el-form-item>
        <el-form-item label="提示词"><el-input v-model="editingShot.prompt" type="textarea" :rows="4"/></el-form-item>
        <el-form-item label="台词"><el-input v-model="editingShot.dialogue" type="textarea" :rows="2"/></el-form-item>
        <el-form-item label="场景描述"><el-input v-model="editingShot.description" type="textarea" :rows="2"/></el-form-item>
      </el-form>
      <template #footer><el-button @click="editDialog=false">取消</el-button><el-button type="primary" @click="saveShot">保存</el-button></template>
    </el-dialog>
  </div>
</template>
<style scoped>.dash-head{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:16px}.dash-head h1{font-size:24px;font-weight:700;color:var(--text-primary)}.tech-line{height:1px;background:linear-gradient(90deg,var(--border-accent),transparent);margin-bottom:24px}.empty-hint{text-align:center;padding:40px 0;color:var(--text-tertiary)}.sb-list{display:flex;flex-direction:column;gap:12px}.sb-card{padding:16px 20px;border-radius:var(--radius)}.sb-header{display:flex;gap:12px;align-items:center;margin-bottom:10px;flex-wrap:wrap}.sb-num{font-size:12px;color:var(--accent);font-weight:700}.sb-type{font-size:11px;color:var(--text-tertiary);padding:2px 8px;border-radius:4px;background:var(--bg-elevated)}.sb-dur{font-size:11px;color:var(--text-tertiary)}.sb-cam{font-size:11px;color:var(--accent2)}.sb-body{display:flex;gap:12px;margin-bottom:8px}.sb-preview{width:120px;height:80px;border-radius:var(--radius-sm);overflow:hidden;flex-shrink:0;display:flex;align-items:center;justify-content:center;background:var(--bg-elevated);border:1px solid var(--border-subtle)}.sb-img{width:100%;height:100%;object-fit:cover}.sb-placeholder{font-size:28px;color:var(--text-tertiary)}.sb-content{flex:1;min-width:0}.sb-prompt{font-size:12px;color:var(--text-secondary);line-height:1.6}.sb-dialogue{font-size:11px;color:var(--accent);margin-top:4px}.sb-actions{display:flex;gap:8px;justify-content:flex-end}</style>
