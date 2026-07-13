import api from './index'

export function getWorks(page = 1) {
  return api.get('/works', { params: { page } })
}

export function getWork(id) {
  return api.get(`/works/${id}`)
}

export function createWork(title, content, style) {
  return api.post('/works', { title, content, style })
}

export function deleteWork(id) {
  return api.delete(`/works/${id}`)
}

export function getWorkTimeline(id) {
  return api.get(`/works/${id}/timeline`)
}
