<script setup>
import { ref } from 'vue'
const currentWorkId = ref(1)
</script>
<template>
  <div><div class="dash-head"><h1>角色资产库</h1></div><div class="tech-line"></div>
    <el-empty description="选择一个项目后在这里管理角色资产"><el-button type="primary">选择项目</el-button></el-empty>
  </div>
</template>
<style scoped>.dash-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px}.dash-head h1{font-size:24px;font-weight:700;color:var(--text-primary)}.tech-line{height:1px;background:linear-gradient(90deg,var(--border-accent),transparent);margin-bottom:24px}</style>
