<script setup>
import { ref, reactive } from 'vue'
import { ElMessage } from 'element-plus'
import axios from 'axios'
const api = axios.create({ baseURL: '/api' })
const isLogin = ref(true); const loading = ref(false)
const form = reactive({ name:'', email:'', password:'' })

async function submit() {
  loading.value = true
  try {
    const { data } = isLogin.value
      ? await api.post('/auth/login', { email: form.email, password: form.password })
      : await api.post('/auth/register', form)
    localStorage.setItem('token', data.token); localStorage.setItem('user', JSON.stringify(data.user))
    window.location.href = '/'
  } catch(e) { ElMessage.error(e.response?.data?.message || '操作失败') }
  loading.value = false
}

const carouselVideos = [
  { url: '', title: 'AI短剧，让创作更自由', desc: '一句话生成完整短剧视频' },
  { url: '', title: '爆款复刻，热点追踪', desc: '参考热门视频一键生成同款' },
  { url: '', title: '角色资产，永久复用', desc: '全剧角色形象统一不崩塌' },
]
</script>

<template>
  <div class="auth-page">
    <div class="auth-left">
      <div class="carousel">
        <div v-for="(v,i) in carouselVideos" :key="i" class="carousel-item">
          <div class="carousel-video"><span style="font-size:64px">🎬</span></div>
          <h2>{{ v.title }}</h2>
          <p>{{ v.desc }}</p>
        </div>
      </div>
    </div>
    <div class="auth-right">
      <div class="auth-card">
        <h1>◆ AI短剧</h1>
        <p class="auth-sub">{{ isLogin ? '欢迎回来' : '创建你的账号' }}</p>
        <el-form label-position="top" @submit.prevent="submit" style="margin-top:24px">
          <el-form-item v-if="!isLogin" label="用户名"><el-input v-model="form.name" size="large" placeholder="你的用户名"/></el-form-item>
          <el-form-item label="邮箱"><el-input v-model="form.email" size="large" placeholder="your@email.com"/></el-form-item>
          <el-form-item label="密码"><el-input v-model="form.password" type="password" size="large" placeholder="至少6位" show-password/></el-form-item>
          <el-button type="primary" native-type="submit" size="large" :loading="loading" style="width:100%;margin-top:8px">{{ isLogin ? '登录' : '注册' }}</el-button>
        </el-form>
        <div class="auth-switch">{{ isLogin ? '没有账号？' : '已有账号？' }}<a @click="isLogin=!isLogin" style="cursor:pointer;color:var(--accent)">{{ isLogin ? '立即注册' : '去登录' }}</a></div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.auth-page{display:flex;min-height:100vh;background:var(--bg-deep)}
.auth-left{flex:1;display:flex;align-items:center;justify-content:center;padding:40px;background:linear-gradient(135deg,var(--bg-surface),var(--bg-deep))}
.carousel-item{text-align:center;max-width:400px}.carousel-video{width:320px;height:200px;border-radius:16px;background:var(--bg-elevated);display:flex;align-items:center;justify-content:center;margin:0 auto 24px}
.carousel-item h2{font-size:24px;color:var(--text-primary);margin-bottom:8px}.carousel-item p{color:var(--text-secondary)}
.auth-right{width:440px;display:flex;align-items:center;justify-content:center;padding:40px}
.auth-card{width:100%;max-width:360px}.auth-card h1{font-size:28px;color:var(--accent);margin-bottom:4px}.auth-sub{color:var(--text-tertiary)}
.auth-switch{margin-top:20px;text-align:center;color:var(--text-tertiary);font-size:14px}
</style>
