<script setup>
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const auth = useAuthStore()
</script>

<template>
  <el-container class="admin-layout">
    <el-aside width="220px" class="sidebar">
      <div class="sidebar-header">
        <h2>⚙️ 管理后台</h2>
      </div>
      <el-menu :default-active="$route.path" router background-color="#304156" text-color="#bfcbd9" active-text-color="#409eff">
        <el-menu-item index="/admin">
          <el-icon><DataAnalysis /></el-icon>
          <span>数据概览</span>
        </el-menu-item>
        <el-menu-item index="/admin/users">
          <el-icon><User /></el-icon>
          <span>用户管理</span>
        </el-menu-item>
        <el-menu-item index="/admin/recharge">
          <el-icon><Coin /></el-icon>
          <span>积分充值</span>
        </el-menu-item>
      </el-menu>
      <div class="sidebar-footer">
        <el-button text @click="router.push('/')">← 返回前台</el-button>
      </div>
    </el-aside>
    <el-main class="admin-main">
      <router-view />
    </el-main>
  </el-container>
</template>

<style scoped>
.admin-layout { min-height: 100vh; }
.sidebar { background: #304156; display: flex; flex-direction: column; }
.sidebar-header { padding: 20px; text-align: center; }
.sidebar-header h2 { color: #fff; font-size: 18px; }
.sidebar .el-menu { border-right: none; flex: 1; }
.sidebar-footer { padding: 16px; border-top: 1px solid rgba(255,255,255,0.1); }
.admin-main { background: #f0f2f5; padding: 24px; }
</style>
