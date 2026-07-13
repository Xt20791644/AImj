<script setup>
import { useRouter } from 'vue-router'
const router = useRouter()
</script>

<template>
  <div class="home-page">
    <section class="hero">
      <h1>用 AI 创作你的短剧</h1>
      <p class="subtitle">输入一个故事，AI 自动帮你拆解分镜、生成画面、配音合成，一站式完成短剧制作</p>
      <div class="hero-actions">
        <el-button type="primary" size="large" @click="router.push('/create')">🎬 开始创作</el-button>
        <el-button size="large" @click="router.push('/works')">浏览作品广场</el-button>
      </div>
    </section>

    <section class="features">
      <h2>创作流程</h2>
      <el-row :gutter="24">
        <el-col :span="6" v-for="item in steps" :key="item.title">
          <div class="feature-card">
            <div class="feature-icon">{{ item.icon }}</div>
            <h3>{{ item.title }}</h3>
            <p>{{ item.desc }}</p>
          </div>
        </el-col>
      </el-row>
    </section>

    <section class="showcase">
      <h2>热门作品</h2>
      <el-empty description="暂无作品，快去创作第一个吧！" />
    </section>
  </div>
</template>

<script>
export default {
  data() {
    return {
      steps: [
        { icon: '📝', title: '输入故事', desc: '在对话框中输入故事大纲或完整剧本' },
        { icon: '✂️', title: 'AI 拆解', desc: '自动切分剧集、提取角色、生成分镜' },
        { icon: '🎨', title: '智能生成', desc: 'AI 生成角色形象、场景画面、视频片段' },
        { icon: '🎬', title: '合成导出', desc: '自动配音配乐、合成完整短剧视频' },
      ]
    }
  }
}
</script>

<style scoped>
.hero { text-align: center; padding: 80px 20px 60px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; border-radius: 12px; margin-bottom: 48px; }
.hero h1 { font-size: 42px; margin-bottom: 16px; }
.subtitle { font-size: 18px; opacity: 0.9; max-width: 600px; margin: 0 auto 32px; }
.hero-actions { display: flex; gap: 16px; justify-content: center; }
.features { text-align: center; margin-bottom: 48px; }
.features h2 { font-size: 28px; margin-bottom: 32px; }
.feature-card { background: #fff; border-radius: 12px; padding: 32px 20px; height: 100%; transition: transform 0.2s; }
.feature-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,0.1); }
.feature-icon { font-size: 40px; margin-bottom: 16px; }
.feature-card h3 { font-size: 18px; margin-bottom: 8px; color: #303133; }
.feature-card p { color: #909399; font-size: 14px; line-height: 1.6; }
.showcase { text-align: center; padding: 40px 0; }
.showcase h2 { font-size: 28px; margin-bottom: 32px; }
</style>
