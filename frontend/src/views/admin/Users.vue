<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import api from '../../api'

const users = ref([]); const loading = ref(true)
const worksDialog = ref(false); const worksUser = ref({}); const userWorks = ref([])
const adminShowDetail = ref(false); const adminViewing = ref(null)
const passwordDialog = ref(false); const passwordUser = ref({}); const newPassword = ref('')

async function fetchUsers() { loading.value=true;try{const r=await api.get('/admin/users');users.value=r.data||r}catch(e){}loading.value=false}
onMounted(fetchUsers)

async function handleDelete(user){try{await ElMessageBox.confirm(`确认删除「${user.name}」？`,'警告',{type:'warning'});await api.delete(`/admin/users/${user.id}`);users.value=users.value.filter(u=>u.id!==user.id);ElMessage.success('已删除')}catch(e){if(e!=='cancel')ElMessage.error('删除失败')}}
async function openWorks(user){try{const r=await api.get(`/admin/users/${user.id}/works`);worksUser.value=r.user;userWorks.value=r.works||[];worksDialog.value=true}catch(e){ElMessage.error('获取作品失败')}}
function openPassword(user){passwordUser.value=user;newPassword.value='';passwordDialog.value=true}
async function handleResetPassword(){if(!newPassword.value||newPassword.value.length<6){ElMessage.warning('密码至少6位');return};try{const r=await api.post(`/admin/users/${passwordUser.value.id}/password`,{password:newPassword.value});ElMessage.success(r.message||'已重置');passwordDialog.value=false}catch(e){ElMessage.error(e.response?.data?.message||'失败')}}
function formatDate(d){if(!d)return '';const t=new Date(d);return t.toLocaleDateString('zh-CN')+' '+t.toLocaleTimeString('zh-CN',{hour:'2-digit',minute:'2-digit',second:'2-digit'})}
function styleLabel(s){const m={realistic:'真人写实',anime:'日系动画','3d':'3D动画',cyberpunk:'赛博朋克'};return m[s]||s}
function formatMeta(m){if(!m)return'无';try{return typeof m==='string'?JSON.stringify(JSON.parse(m),null,2):JSON.stringify(m,null,2)}catch{return String(m)}}
function openAdminVideo(w){adminViewing.value=w;adminShowDetail.value=true}
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
      <div v-else class="admin-works-grid">
        <div v-for="w in userWorks" :key="w.id" class="aw-card glass-panel" @click="openAdminVideo(w)">
          <div class="aw-cover">
            <img v-if="w.output_cover" :src="w.output_cover" class="aw-img"/>
            <span v-else class="aw-icon">🎬</span>
            <div class="aw-dur" v-if="w.duration">{{ w.duration }}s</div>
          </div>
          <div class="aw-info"><h4>{{ w.title }}</h4><span class="aw-date">{{ formatDate(w.created_at) }}</span></div>
        </div>
      </div>
    </el-dialog>

    <el-dialog v-model="adminShowDetail" :title="adminViewing?.title" width="700px" top="5vh" @close="adminViewing=null">
      <div v-if="adminViewing">
        <video v-if="adminViewing.output_video" :src="adminViewing.output_video" controls style="width:100%;max-height:450px;border-radius:var(--radius)"/>
        <div v-else class="no-video">🎬 视频暂不可用</div>
        <div class="detail-meta" style="margin-top:12px;display:flex;gap:16px;font-size:12px;color:var(--text-tertiary);padding:12px 0;border-bottom:1px solid var(--border-subtle)"><span>风格: {{ styleLabel(adminViewing.style) }}</span><span>时长: {{ adminViewing.duration||0 }}秒</span><span>{{ formatDate(adminViewing.created_at) }}</span></div>
        <div v-if="adminViewing.meta?.script||adminViewing.content" style="margin-top:12px"><span style="font-size:12px;color:var(--accent);font-weight:600">📝 故事/剧本</span><pre style="background:var(--bg-elevated);padding:12px;border-radius:var(--radius-sm);font-size:12px;color:var(--text-secondary);white-space:pre-wrap;max-height:200px;overflow-y:auto;margin-top:6px">{{ adminViewing.meta?.script||adminViewing.content }}</pre></div>
        <div v-if="adminViewing.meta?.kling_config" style="margin-top:12px"><span style="font-size:12px;color:var(--accent);font-weight:600">⚙️ 生成配置</span><pre style="background:var(--bg-elevated);padding:12px;border-radius:var(--radius-sm);font-size:12px;color:var(--text-secondary);white-space:pre-wrap;max-height:200px;overflow-y:auto;margin-top:6px">{{ formatMeta(adminViewing.meta.kling_config) }}</pre></div>
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
.admin-works-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px}
.aw-card{cursor:pointer;overflow:hidden;padding:0;transition:all var(--transition)}
.aw-card:hover{transform:translateY(-2px);border-color:var(--border-accent)!important}
.aw-cover{height:140px;display:flex;align-items:center;justify-content:center;background:var(--bg-elevated);position:relative}
.aw-img{width:100%;height:100%;object-fit:cover}
.aw-icon{font-size:36px;color:var(--text-tertiary)}
.aw-dur{position:absolute;bottom:6px;right:6px;padding:1px 6px;border-radius:4px;background:rgba(0,0,0,0.7);color:#fff;font-size:11px;font-family:var(--font-mono)}
.aw-info{padding:10px 12px}.aw-info h4{font-size:13px;color:var(--text-primary);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.aw-date{font-size:11px;color:var(--text-tertiary)}
.no-video{height:300px;display:flex;align-items:center;justify-content:center;background:var(--bg-elevated);border-radius:var(--radius);color:var(--text-tertiary);font-size:24px}
</style>
