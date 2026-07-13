<script setup>
import { ref, onMounted } from 'vue'

const stats = ref({ users: 0, works: 0, revenue: 0, credits: 0 })
const loading = ref(true)

onMounted(async () => {
  // TODO: fetch from API
  await new Promise(r => setTimeout(r, 500))
  stats.value = { users: 128, works: 56, revenue: 12800, credits: 35600 }
  loading.value = false
})
</script>

<template>
  <div class="admin-dashboard" v-loading="loading">
    <h2>数据概览</h2>
    <el-row :gutter="20" style="margin-top:20px">
      <el-col :span="6" v-for="item in [
        { label: '用户总数', value: stats.users, icon: 'User', color: '#409eff' },
        { label: '作品总数', value: stats.works, icon: 'VideoCamera', color: '#67c23a' },
        { label: '营收 (元)', value: '¥' + stats.revenue, icon: 'Coin', color: '#e6a23c' },
        { label: '剩余积分', value: stats.credits, icon: 'Star', color: '#f56c6c' },
      ]" :key="item.label">
        <el-card shadow="hover">
          <div class="stat-card">
            <el-icon :size="32" :color="item.color"><component :is="item.icon" /></el-icon>
            <div>
              <p class="stat-label">{{ item.label }}</p>
              <p class="stat-value">{{ item.value }}</p>
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>
  </div>
</template>

<style scoped>
.admin-dashboard h2 { font-size: 22px; }
.stat-card { display: flex; align-items: center; gap: 16px; }
.stat-label { font-size: 13px; color: #909399; }
.stat-value { font-size: 24px; font-weight: 700; color: #303133; margin-top: 4px; }
</style>
