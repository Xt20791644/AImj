<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import api from '../../api'

const works = ref([]); const selectedWorkId = ref(null)
const characters = ref([]); const loading = ref(false)
const dialog = ref(false); const form = ref({ role_type:'supporting' })

onMounted(async () => {
  try { const r = await api.get('/works', { params: { my: 1 } }); works.value = r.data || r } catch(e) {}
})

async function selectWork(id) {
  selectedWorkId.value = id; loading.value = true
  try { const r = await api.get(`/studio/works/${id}/characters`); characters.value = Array.isArray(r) ? r : r.data || [] } catch(e) {}
  loading.value = false
}
function openDialog(c = null) { form.value = c ? { ...c } : { role_type:'supporting' }; dialog.value = true }
async function saveCharacter() {
  try {
    if (form.value.id) await api.put(`/studio/works/${selectedWorkId.value}/characters/${form.value.id}`, form.value)
    else await api.post(`/studio/works/${selectedWorkId.value}/characters`, form.value)
    dialog.value = false; selectWork(selectedWorkId.value); ElMessage.success('已保存')
  } catch(e) { ElMessage.error('保存失败') }
}
async function deleteCharacter(c) {
  try { await ElMessageBox.confirm(`删除「${c.name}」？`, '确认', { type:'warning' }); await api.delete(`/studio/works/${selectedWorkId.value}/characters/${c.id}`); selectWork(selectedWorkId.value); ElMessage.success('已删除') } catch(e) { if (e !== 'cancel') ElMessage.error('删除失败') }
}
</script>

<template>
  <div>
    <div class="dash-head"><h1>角色资产库</h1></div><div class="tech-line"></div>
    <el-select v-model="selectedWorkId" placeholder="选择项目" size="large" style="width:300px;margin-bottom:20px" @change="selectWork"><el-option v-for="w in works" :key="w.id" :label="w.title" :value="w.id"/></el-select>
    <div v-if="!selectedWorkId" class="empty-hint"><h3>请先选择一个项目</h3></div>
    <div v-else v-loading="loading">
      <el-button type="primary" @click="openDialog()" style="margin-bottom:16px">+ 添加角色</el-button>
      <div v-if="characters.length===0" class="empty-hint"><p>暂无角色，点击添加</p></div>
      <div v-else class="char-grid">
        <div v-for="c in characters" :key="c.id" class="char-card glass-panel">
          <div class="cc-avatar"><img v-if="c.image_url" :src="c.image_url" class="cc-img"/><span v-else class="cc-placeholder">{{ c.name?.[0] }}</span></div>
          <div class="cc-info"><h4>{{ c.name }}</h4><p class="cc-role">{{ c.role_type }}</p><p class="cc-desc">{{ (c.description||'暂无描述').substring(0,60) }}</p></div>
          <div class="cc-actions"><el-button size="small" @click="openDialog(c)">编辑</el-button><el-button size="small" type="danger" @click="deleteCharacter(c)">删除</el-button></div>
        </div>
      </div>
    </div>
    <el-dialog v-model="dialog" :title="form.id?'编辑角色':'添加角色'" width="500px">
      <el-form label-position="top">
        <el-row :gutter="12"><el-col :span="12"><el-form-item label="名称"><el-input v-model="form.name" size="large"/></el-form-item></el-col><el-col :span="12"><el-form-item label="类型"><el-select v-model="form.role_type" size="large" style="width:100%"><el-option label="主角" value="protagonist"/><el-option label="反派" value="antagonist"/><el-option label="配角" value="supporting"/><el-option label="客串" value="cameo"/></el-select></el-form-item></el-col></el-row>
        <el-row :gutter="12"><el-col :span="12"><el-form-item label="性别"><el-select v-model="form.gender" size="large" style="width:100%"><el-option label="男" value="男"/><el-option label="女" value="女"/></el-select></el-form-item></el-col><el-col :span="12"><el-form-item label="年龄"><el-input-number v-model="form.age" :min="1" :max="100" size="large" style="width:100%"/></el-form-item></el-col></el-row>
        <el-form-item label="人设"><el-input v-model="form.description" type="textarea" :rows="2"/></el-form-item>
        <el-form-item label="外貌"><el-input v-model="form.appearance" type="textarea" :rows="2"/></el-form-item>
        <el-form-item label="性格"><el-input v-model="form.personality" type="textarea" :rows="2"/></el-form-item>
        <el-form-item label="音色"><el-input v-model="form.voice_tone" placeholder="温柔年轻女声"/></el-form-item>
      </el-form>
      <template #footer><el-button @click="dialog=false">取消</el-button><el-button type="primary" @click="saveCharacter">保存</el-button></template>
    </el-dialog>
  </div>
</template>

<style scoped>
.dash-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px}.dash-head h1{font-size:24px;font-weight:700;color:var(--text-primary)}.tech-line{height:1px;background:linear-gradient(90deg,var(--border-accent),transparent);margin-bottom:24px}
.empty-hint{text-align:center;padding:40px 0;color:var(--text-tertiary)}.empty-hint h3{color:var(--text-primary)}
.char-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px}
.char-card{padding:20px;display:flex;align-items:center;gap:16px;border-radius:var(--radius)}
.cc-avatar{width:64px;height:64px;border-radius:50%;overflow:hidden;flex-shrink:0;display:flex;align-items:center;justify-content:center;background:var(--bg-elevated)}.cc-img{width:100%;height:100%;object-fit:cover}.cc-placeholder{font-size:24px;font-weight:700;color:var(--accent)}
.cc-info{flex:1;min-width:0}.cc-info h4{font-size:15px;color:var(--text-primary);margin-bottom:2px}.cc-role{font-size:11px;color:var(--accent);margin-bottom:4px}.cc-desc{font-size:12px;color:var(--text-tertiary);overflow:hidden;white-space:nowrap;text-overflow:ellipsis}
.cc-actions{display:flex;flex-direction:column;gap:6px;flex-shrink:0}
</style>
