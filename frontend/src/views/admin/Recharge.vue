<script setup>
import { ref, reactive } from 'vue'
import { ElMessage } from 'element-plus'
import api from '../../api'

const loading = ref(false)
const form = reactive({ user_id: null, amount: null, note: '' })

async function handleRecharge() {
  if (!form.user_id || !form.amount || form.amount < 1) {
    ElMessage.warning('请填写用户ID和积分数量'); return
  }
  loading.value = true
  try {
    const result = await api.post(`/admin/users/${form.user_id}/recharge`, { amount: form.amount, note: form.note })
    ElMessage.success(result.message || '充值成功')
    form.amount = null; form.note = ''
  } catch (e) {
    ElMessage.error(e.response?.data?.message || '充值失败')
  }
  loading.value = false
}
</script>

<template>
  <div>
    <div class="page-head"><h1>积分充值</h1><p class="head-sub">管理员手动为用户充值积分</p></div>
    <div class="recharge-card glass-panel">
      <el-form label-position="top" @submit.prevent="handleRecharge">
        <el-form-item label="用户 ID"><el-input-number v-model="form.user_id" :min="1" placeholder="输入用户ID" style="width:100%" size="large"/></el-form-item>
        <el-form-item label="充值积分数量"><el-input-number v-model="form.amount" :min="1" :max="100000" placeholder="输入积分数量" style="width:100%" size="large"/></el-form-item>
        <el-form-item label="备注"><el-input v-model="form.note" placeholder="充值原因（选填）" size="large"/></el-form-item>
        <el-form-item><el-button type="primary" native-type="submit" :loading="loading" size="large" style="width:100%">确认充值</el-button></el-form-item>
      </el-form>
    </div>
  </div>
</template>

<style scoped>
.page-head{margin-bottom:24px}.page-head h1{font-size:24px;font-weight:700;color:var(--text-primary)}.head-sub{color:var(--text-tertiary);font-size:13px;margin-top:4px}
.recharge-card{max-width:480px;padding:24px;border-radius:var(--radius-lg)}
</style>
