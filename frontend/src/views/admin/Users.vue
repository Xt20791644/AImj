<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import api from '../../api'

const users = ref([])
const loading = ref(true)

async function fetchUsers() {
  loading.value = true
  try { const result = await api.get('/admin/users'); users.value = result.data || result } catch (e) {}
  loading.value = false
}
onMounted(fetchUsers)

async function handleDelete(user) {
  try {
    await ElMessageBox.confirm(`确认删除用户「${user.name}」？`, '警告', { type: 'warning', confirmButtonText:'删除', cancelButtonText:'取消' })
    await api.delete(`/admin/users/${user.id}`)
    users.value = users.value.filter(u => u.id !== user.id)
    ElMessage.success('已删除')
  } catch (e) { if (e !== 'cancel') ElMessage.error('删除失败') }
}

function formatDate(d) { return d ? d.split('T')[0] : '' }
</script>

<template>
  <div>
    <div class="page-head"><h1>用户管理</h1><p class="head-sub">共 {{ users.length }} 个用户</p></div>
    <el-table :data="users" v-loading="loading" empty-text="暂无用户数据">
      <el-table-column prop="id" label="ID" width="60" />
      <el-table-column prop="name" label="用户名" />
      <el-table-column prop="email" label="邮箱" />
      <el-table-column prop="credits" label="积分" width="100" />
      <el-table-column prop="role" label="角色" width="100">
        <template #default="{ row }"><el-tag :type="row.role==='admin'?'danger':'primary'" size="small">{{ row.role }}</el-tag></template>
      </el-table-column>
      <el-table-column prop="created_at" label="注册时间" width="120"><template #default="{row}">{{ formatDate(row.created_at) }}</template></el-table-column>
      <el-table-column label="操作" width="180">
        <template #default="{ row }">
          <el-button size="small" @click="$router.push(`/admin/recharge?user=${row.id}`)">充值</el-button>
          <el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button>
        </template>
      </el-table-column>
    </el-table>
  </div>
</template>

<style scoped>
.page-head{margin-bottom:24px}.page-head h1{font-size:24px;font-weight:700;color:var(--text-primary)}.head-sub{color:var(--text-tertiary);font-size:13px;margin-top:4px}
</style>
