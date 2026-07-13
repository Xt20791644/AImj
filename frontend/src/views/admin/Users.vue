<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'

const users = ref([])
const loading = ref(true)

onMounted(async () => {
  // TODO: fetch from API
  await new Promise(r => setTimeout(r, 500))
  users.value = [
    { id: 1, name: '张三', email: 'zhang@test.com', credits: 500, role: 'user', created_at: '2026-07-01' },
    { id: 2, name: '李四', email: 'li@test.com', credits: 1200, role: 'user', created_at: '2026-07-05' },
  ]
  loading.value = false
})

function handleDelete(user) {
  ElMessageBox.confirm(`确认删除用户 ${user.name}？`, '警告', { type: 'warning' })
    .then(() => {
      users.value = users.value.filter(u => u.id !== user.id)
      ElMessage.success('已删除')
    })
    .catch(() => {})
}
</script>

<template>
  <div class="admin-users">
    <h2>用户管理</h2>
    <el-table :data="users" v-loading="loading" style="margin-top:20px" empty-text="暂无用户">
      <el-table-column prop="id" label="ID" width="60" />
      <el-table-column prop="name" label="用户名" />
      <el-table-column prop="email" label="邮箱" />
      <el-table-column prop="credits" label="积分" width="100" />
      <el-table-column prop="role" label="角色" width="100">
        <template #default="{ row }">
          <el-tag :type="row.role === 'admin' ? 'danger' : 'info'" size="small">{{ row.role }}</el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="created_at" label="注册时间" width="120" />
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
.admin-users h2 { font-size: 22px; }
</style>
