import axios from 'axios'
import { ElMessage } from 'element-plus'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE || '/api',
  timeout: 300000,
  headers: { 'Content-Type': 'application/json' }
})

// Request interceptor - attach token
api.interceptors.request.use(config => {
  const token = localStorage.getItem('token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// Response interceptor - handle errors
api.interceptors.response.use(
  response => response.data,
  error => {
    const msg = error.response?.data?.message || error.message || '请求失败'
    if (error.response?.status === 401) {
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      window.location.href = '/login'
    }
    ElMessage.error(msg)
    return Promise.reject(error)
  }
)

export default api
