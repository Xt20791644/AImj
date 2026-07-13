<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { ElMessage } from 'element-plus'

const router = useRouter()
const auth = useAuthStore()

const story = reactive({
  title: '',
  content: '',
  style: 'realistic'
})

const styleOptions = [
  { value: 'realistic', label: '真人写实' },
  { value: 'anime', label: '日系动画' },
  { value: '3d', label: '3D动画' },
  { value: 'cyberpunk', label: '赛博朋克' },
]

const creating = ref(false)
const progress = ref(0)
const statusText = ref('')

const steps = [
  '分析故事结构...',
  '提取角色和场景...',
  '生成分镜脚本...',
  '生成画面素材...',
  '生成配音...',
  '合成导出视频...',
]

async function handleCreate() {
  if (!story.title.trim() || !story.content.trim()) {
    ElMessage.warning('请填写故事标题和内容')
    return
  }
  if (!auth.isLoggedIn) {
    ElMessage.warning('请先登录')
    router.push('/login')
    return
  }

  creating.value = true
  progress.value = 0

  for (let i = 0; i < steps.length; i++) {
    statusText.value = steps[i]
    await new Promise(r => setTimeout(r, 800))
    progress.value = Math.round(((i + 1) / steps.length) * 100)
  }

  creating.value = false
  ElMessage.success('短剧创作完成！')
  router.push('/works')
}
</script>

<template>
  <div class="create-page">
    <h1>🎬 创作工坊</h1>
    <p class="subtitle">输入你的故事，AI 帮你生成完整短剧</p>

    <el-card class="input-card">
      <template v-if="!creating">
        <el-form label-position="top">
          <el-form-item label="故事标题">
            <el-input v-model="story.title" placeholder="给你的短剧起个名字" size="large" />
          </el-form-item>
          <el-form-item label="故事内容">
            <el-input v-model="story.content" type="textarea" :rows="8" placeholder="输入你的故事大纲或完整剧本...&#10;&#10;例如：&#10;都市白领林晨在30岁生日那天被公司裁员，心灰意冷的他在天桥下捡到一块旧怀表。当他拨动表针的那一刻，时间竟倒流回了三年前..." />
          </el-form-item>
          <el-form-item label="画面风格">
            <el-radio-group v-model="story.style">
              <el-radio-button v-for="s in styleOptions" :key="s.value" :value="s.value">{{ s.label }}</el-radio-button>
            </el-radio-group>
          </el-form-item>
          <el-form-item>
            <el-button type="primary" size="large" @click="handleCreate" :disabled="!story.title || !story.content">
              开始创作 🚀
            </el-button>
            <span class="cost-hint">预计消耗 50 积分</span>
          </el-form-item>
        </el-form>
      </template>
      <template v-else>
        <div class="progress-area">
          <el-progress :percentage="progress" :stroke-width="20" :text-inside="true" />
          <p class="status-text">{{ statusText }}</p>
        </div>
      </template>
    </el-card>
  </div>
</template>

<style scoped>
.create-page { max-width: 800px; margin: 0 auto; padding: 40px 20px; }
.create-page h1 { font-size: 32px; margin-bottom: 8px; }
.subtitle { color: #909399; margin-bottom: 32px; }
.input-card { padding: 8px; }
.cost-hint { margin-left: 16px; color: #e6a23c; font-size: 13px; }
.progress-area { padding: 40px 0; text-align: center; }
.status-text { margin-top: 20px; color: #409eff; font-size: 16px; }
</style>
