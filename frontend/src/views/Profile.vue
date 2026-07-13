<script setup>
import { onMounted, ref } from 'vue'
import { useAuthStore } from '../stores/auth'
import { useCreditStore } from '../stores/credit'
import { useRouter } from 'vue-router'

const auth = useAuthStore()
const creditStore = useCreditStore()
const router = useRouter()

const rechargeDialog = ref(false)
const rechargeAmount = ref(null)

onMounted(() => {
  if (!auth.isLoggedIn) {
    router.push('/login')
    return
  }
  creditStore.fetchBalance()
  creditStore.fetchTransactions()
})

function handleRecharge() {
  rechargeDialog.value = true
}

async function confirmRecharge() {
  if (!rechargeAmount.value || rechargeAmount.value < 1) return
  await creditStore.recharge(rechargeAmount.value)
  rechargeDialog.value = false
  creditStore.fetchBalance()
}
</script>

<template>
  <div class="profile-page">
    <h1>👤 个人中心</h1>

    <el-row :gutter="20">
      <el-col :span="8">
        <el-card>
          <template #header>账户信息</template>
          <div style="text-align:center;padding:16px 0">
            <el-avatar :size="80" style="background:#409eff;font-size:32px">{{ auth.user?.name?.[0] }}</el-avatar>
            <h3 style="margin-top:12px">{{ auth.user?.name }}</h3>
            <p style="color:#909399">{{ auth.user?.email }}</p>
          </div>
        </el-card>
      </el-col>
      <el-col :span="16">
        <el-card>
          <template #header>
            <div style="display:flex;justify-content:space-between;align-items:center">
              <span>我的积分</span>
              <el-button type="warning" size="small" @click="handleRecharge">充值</el-button>
            </div>
          </template>
          <div class="balance-display">
            <span class="balance-num">{{ creditStore.balance }}</span>
            <span class="balance-unit">积分</span>
          </div>
        </el-card>
        <el-card style="margin-top:20px">
          <template #header>消费记录</template>
          <el-table :data="creditStore.transactions" v-loading="creditStore.loading" empty-text="暂无记录">
            <el-table-column prop="created_at" label="时间" width="180" />
            <el-table-column prop="type" label="类型">
              <template #default="{ row }">
                <el-tag :type="row.amount > 0 ? 'success' : 'danger'" size="small">
                  {{ row.amount > 0 ? '充值' : '消费' }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column prop="amount" label="积分变动" />
            <el-table-column prop="balance" label="余额" />
            <el-table-column prop="description" label="说明" />
          </el-table>
        </el-card>
      </el-col>
    </el-row>

    <el-dialog v-model="rechargeDialog" title="积分充值" width="400px">
      <el-form label-position="top">
        <el-form-item label="充值积分数量">
          <el-input-number v-model="rechargeAmount" :min="1" :max="100000" size="large" style="width:100%" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="rechargeDialog = false">取消</el-button>
        <el-button type="primary" @click="confirmRecharge">确认充值</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<style scoped>
.profile-page { max-width: 1000px; margin: 0 auto; padding: 40px 20px; }
.profile-page h1 { font-size: 32px; margin-bottom: 24px; }
.balance-display { text-align: center; padding: 24px 0; }
.balance-num { font-size: 48px; font-weight: 700; color: #e6a23c; }
.balance-unit { font-size: 20px; color: #909399; margin-left: 8px; }
</style>
