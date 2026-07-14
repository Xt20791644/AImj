<script setup>
import { ref } from 'vue'
</script>
<template>
  <div><div class="dash-head"><h1>剧集管理</h1><el-button type="primary">+ 添加剧集</el-button></div><div class="tech-line"></div>
    <el-empty description="选择项目后在这里管理剧集和分镜" />
  </div>
</template>
<style scoped>.dash-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px}.dash-head h1{font-size:24px;font-weight:700;color:var(--text-primary)}.tech-line{height:1px;background:linear-gradient(90deg,var(--border-accent),transparent);margin-bottom:24px}</style>
