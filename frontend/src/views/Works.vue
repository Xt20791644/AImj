<script setup>
import { onMounted } from 'vue'
import { useWorkStore } from '../stores/work'

const workStore = useWorkStore()

onMounted(() => {
  workStore.fetchWorks()
})
</script>

<template>
  <div class="works-page">
    <h1>🎭 作品广场</h1>
    <p class="subtitle">浏览社区精彩短剧作品</p>
    <el-empty v-if="!workStore.loading && workStore.works.length === 0" description="还没有作品，快去创作第一个吧！" />
    <el-row :gutter="20" v-loading="workStore.loading">
      <el-col :span="8" v-for="work in workStore.works" :key="work.id">
        <el-card :body-style="{ padding: '0' }" class="work-card" shadow="hover">
          <div class="work-cover">
            <el-icon :size="48"><VideoCamera /></el-icon>
          </div>
          <div class="work-info">
            <h3>{{ work.title }}</h3>
            <div class="work-meta">
              <span>{{ work.created_at }}</span>
              <span>{{ work.views || 0 }} 次播放</span>
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>
  </div>
</template>

<style scoped>
.works-page { max-width: 1200px; margin: 0 auto; padding: 40px 20px; }
.works-page h1 { font-size: 32px; margin-bottom: 8px; }
.subtitle { color: #909399; margin-bottom: 32px; }
.work-card { cursor: pointer; border-radius: 12px; overflow: hidden; }
.work-cover { background: linear-gradient(135deg, #667eea, #764ba2); height: 180px; display: flex; align-items: center; justify-content: center; color: #fff; }
.work-info { padding: 16px; }
.work-info h3 { font-size: 16px; margin-bottom: 8px; }
.work-meta { display: flex; justify-content: space-between; color: #909399; font-size: 12px; }
</style>
