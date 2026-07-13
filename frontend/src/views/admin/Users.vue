<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import api from '../../api'

const users = ref([]); const loading = ref(true)
const worksDialog = ref(false); const worksUser = ref({}); const userWorks = ref([])
const passwordDialog = ref(false); const passwordUser = ref({}); const newPassword = ref('')

async function fetchUsers() { loading.value=true;try{const r=await api.get('/admin/users');users.value=r.data||r}catch(e){}loading.value=false}
onMounted(fetchUsers)

async function handleDelete(user){try{await ElMessageBox.confirm(`确认删除「${user.name}」？`,'警告',{type:'warning'});await api.delete(`/admin/users/${user.id}`);users.value=users.value.filter(u=>u.id!==user.id);ElMessage.success('已删除')}catch(e){if(e!=='cancel')ElMessage.error('删除失败')}}
async function openWorks(user){try{const r=await api.get(`/admin/users/${user.id}/works`);worksUser.value=r.user;userWorks.value=r.works||[];worksDialog.value=true}catch(e){ElMessage.error('获取作品失败')}}
function openPassword(user){passwordUser.value=user;newPassword.value='';passwordDialog.value=true}
async function handleResetPassword(){if(!newPassword.value||newPassword.value.length<6){ElMessage.warning('密码至少6位');return};try{const r=await api.post(`/admin/users/${passwordUser.value.id}/password`,{password:newPassword.value});ElMessage.success(r.message||'已重置');passwordDialog.value=false}catch(e){ElMessage.error(e.response?.data?.message||'失败')}}
function formatDate(d){if(!d)return '';const t=new Date(d);return t.toLocaleDateString('zh-CN')+' '+t.toLocaleTimeString('zh-CN',{hour:'2-digit',minute:'2-digit',second:'2-digit'})}
function formatMeta(m){if(!m)return'无';try{return typeof m==='string'?JSON.stringify(JSON.parse(m),null,2):JSON.stringify(m,null,2)}catch{return String(m)}}
</script>

<template>
  <div>
    <div class="page-head"><h1>用户管理</h1><p class="head-sub">共 {{ users.length }} 个用户</p></div>
    <el-table :data="users" v-loading="loading" empty-text="暂无数据">
      <el-table-column prop="id" label="ID" width="60"/><el-table-column prop="name" label="用户名"/><el-table-column prop="email" label="邮箱"/>
      <el-table-column prop="credits" label="积分" width="100"/>
      <el-table-column prop="role" label="角色" width="100"><template #default="{row}"><el-tag :type="row.role==='admin'?'danger':'primary'" size="small">{{ row.role }}</el-tag></template></el-table-column>
      <el-table-column prop="created_at" label="注册时间" width="120"><template #default="{row}">{{ formatDate(row.created_at) }}</template></el-table-column>
      <el-table-column label="操作" width="280"><template #default="{row}">
        <el-button size="small" @click="openWorks(row)">作品</el-button>
        <el-button size="small" @click="openPassword(row)">改密</el-button>
        <el-button size="small" type="warning" @click="$router.push(`/admin/recharge?user=${row.id}`)">充值</el-button>
        <el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button>
      </template></el-table-column>
    </el-table>

    <el-dialog v-model="worksDialog" :title="`${worksUser.name} 的作品`" width="800px" top="5vh">
      <el-empty v-if="userWorks.length===0" description="暂无作品"/>
      <div v-else class="works-list">
        <div v-for="w in userWorks" :key="w.id" class="work-item glass-panel">
          <div class="wi-head"><h4>{{ w.title }}</h4><el-tag :type="w.status==='completed'?'success':w.status==='failed'?'danger':'warning'" size="small">{{ w.status }}</el-tag></div>
          <div class="wi-meta mono"><span>风格:{{ w.style }}</span><span>进度:{{ w.progress }}%</span><span>时长:{{ w.duration||0 }}秒</span><span>{{ formatDate(w.created_at) }}</span></div>
          <div class="wi-section" v-if="w.meta?.script||w.content"><span class="wi-label">📝 故事/剧本</span><pre class="wi-text">{{ w.meta?.script||w.content }}</pre></div>
          <div class="wi-section" v-if="w.meta?.kling_config"><span class="wi-label">⚙️ 生成配置</span><pre class="wi-text">{{ formatMeta(w.meta.kling_config) }}</pre></div>
          <div class="wi-timeline" v-if="w.timelines?.length"><span class="wi-label">📊 生成过程</span>
            <div v-for="t in w.timelines" :key="t.step" class="tl-row"><span class="tl-step mono">{{ t.step }}</span><el-tag :type="t.status==='completed'?'success':t.status==='failed'?'danger':'info'" size="small">{{ t.status }}</el-tag><span class="tl-msg">{{ t.message }}</span></div>
          </div>
        </div>
      </div>
    </el-dialog>

    <el-dialog v-model="passwordDialog" title="重置密码" width="400px">
      <el-form label-position="top" @submit.prevent="handleResetPassword">
        <el-form-item :label="`为 ${passwordUser.name} 设置新密码`"><el-input v-model="newPassword" type="password" placeholder="至少6位" size="large" show-password/></el-form-item>
        <el-form-item><el-button type="primary" native-type="submit" size="large" style="width:100%">确认重置</el-button></el-form-item>
      </el-form>
    </el-dialog>
  </div>
</template>

<style scoped>
.page-head{margin-bottom:24px}.page-head h1{font-size:24px;font-weight:700;color:var(--text-primary)}.head-sub{color:var(--text-tertiary);font-size:13px;margin-top:4px}
.works-list{display:flex;flex-direction:column;gap:16px}.work-item{padding:20px;border-radius:var(--radius)}
.wi-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px}.wi-head h4{color:var(--text-primary);font-size:16px}
.wi-meta{display:flex;gap:16px;flex-wrap:wrap;font-size:12px;color:var(--text-tertiary);margin-bottom:12px;padding-bottom:12px;border-bottom:1px solid var(--border-subtle)}
.wi-section{margin-top:12px}.wi-label{display:block;font-size:12px;color:var(--accent);font-weight:600;margin-bottom:6px}
.wi-text{background:var(--bg-elevated);padding:12px;border-radius:var(--radius-sm);font-size:12px;color:var(--text-secondary);white-space:pre-wrap;max-height:200px;overflow-y:auto}
.wi-timeline{margin-top:12px}.tl-row{display:flex;align-items:center;gap:10px;padding:6px 0;font-size:12px;border-bottom:1px solid var(--border-subtle)}.tl-step{color:var(--accent);min-width:80px}.tl-msg{color:var(--text-secondary);flex:1}
</style>
