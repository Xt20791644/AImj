<script setup>
import { ref, onMounted } from 'vue'
import api from '../../api'

const stats = ref({ users: 0, works: 0, revenue: 0, credits: 0 })
const loading = ref(true)

onMounted(async () => {
  try { const { data } = await api.get('/admin/stats'); stats.value = data } catch (e) {}
  loading.value = false
})
</script>

<template>
  <div v-loading="loading">
    <div class="page-head"><h1>数据概览</h1><p class="head-sub">系统核心指标</p></div>
    <el-row :gutter="16">
      <el-col :span="6" v-for="card in [
        {k:'users',label:'用户总数',icon:'👥',c:'var(--accent)',bg:'var(--accent-dim)'},
        {k:'works',label:'作品总数',icon:'🎬',c:'var(--success)',bg:'var(--success-dim)'},
        {k:'revenue',label:'充值总额',icon:'💰',c:'var(--warning)',bg:'var(--warning-dim)'},
        {k:'credits',label:'剩余积分',icon:'💎',c:'var(--accent2)',bg:'var(--accent2-dim)'},
      ]" :key="card.k">
        <div class="stat-card glass-panel">
          <div class="sc-icon" :style="{background:card.bg,color:card.c}">{{ card.icon }}</div>
          <div class="sc-info"><span class="sc-label">{{ card.label }}</span><span class="sc-value" :style="{color:card.c}">{{ card.k==='revenue'?'¥':'' }}{{ stats[card.k]||0 }}</span></div>
        </div>
      </el-col>
    </el-row>
  </div>
</template>

<style scoped>
.page-head{margin-bottom:24px}.page-head h1{font-size:24px;font-weight:700;color:var(--text-primary)}.head-sub{color:var(--text-tertiary);font-size:13px;margin-top:4px}
.stat-card{display:flex;align-items:center;gap:16px;padding:24px;border-radius:var(--radius)}
.sc-icon{width:52px;height:52px;border-radius:var(--radius-sm);display:flex;align-items:center;justify-content:center;font-size:24px;flex-shrink:0}
.sc-label{font-size:12px;color:var(--text-tertiary)}.sc-value{font-size:30px;font-weight:800;font-family:var(--font-mono);display:block;margin-top:4px}
</style>
