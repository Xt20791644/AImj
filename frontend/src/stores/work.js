import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../api'

export const useWorkStore = defineStore('work', () => {
  const works = ref([])
  const currentWork = ref(null)
  const loading = ref(false)

  async function fetchWorks(page = 1) {
    loading.value = true
    try {
      const { data } = await api.get('/works', { params: { page } })
      works.value = data.data
      return data
    } finally {
      loading.value = false
    }
  }

  async function fetchWork(id) {
    const { data } = await api.get(`/works/${id}`)
    currentWork.value = data
    return data
  }

  async function createWork(storyTitle, storyContent, style) {
    const { data } = await api.post('/works', { title: storyTitle, content: storyContent, style })
    return data
  }

  async function deleteWork(id) {
    await api.delete(`/works/${id}`)
    works.value = works.value.filter(w => w.id !== id)
  }

  return { works, currentWork, loading, fetchWorks, fetchWork, createWork, deleteWork }
})
