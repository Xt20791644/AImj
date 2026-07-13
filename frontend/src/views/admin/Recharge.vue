<script setup>
import { ref, reactive } from 'vue'
import { ElMessage } from 'element-plus'

const loading = ref(false)
const form = reactive({
  user_id: null,
  amount: null,
  note: ''
})

async function handleRecharge() {
  if (!form.user_id || !form.amount || form.amount < 1) {
    ElMessage.warning('请填写用户ID和充值积分数量')
    return
  }
  loading.value = true
  // TODO: call API
  await new Promise(r => setTimeout(r, 800))
  ElMessage.success(`已为用户 ${form.user_id} 充值 ${form.amount} 积分`)
  form.amount = null
  form.note = ''
  loading.value = false
}
</script>

<template>
  <div class="admin-recharge">
    <h2>积分充值</h2>
    <el-card style="max-width:500px;margin-top:20px">
      <el-form label-position="top" @submit.prevent="handleRecharge">
        <el-form-item label="用户 ID">
          <el-input-number v-model="form.user_id" :min="1" placeholder="输入用户ID" style="width:100%" />
        </el-form-item>
        <el-form-item label="充值积分数量">
          <el-input-number v-model="form.amount" :min="1" :max="100000" placeholder="输入积分数量" style="width:100%" />
        </el-form-item>
        <el-form-item label="备注">
          <el-input v-model="form.note" placeholder="充值原因（选填）" />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" native-type="submit" :loading="loading" size="large" style="width:100%">
            确认充值
          </el-button>
        </el-form-item>
      </el-form>
    </el-card>
  </div>
</template>

<style scoped>
.admin-recharge h2 { font-size: 22px; }
</style>
