import axios from 'axios'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import router from '@/router'

const api = axios.create({
  baseURL: '/api',
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
})

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

api.interceptors.response.use(
  (response) => response,
  (error) => {
    const toast = useToastStore()

    if (error.response?.status === 401) {
      const auth = useAuthStore()
      auth.logout()
      router.push({ name: 'login', query: { redirect: router.currentRoute.value.fullPath } })
      toast.error('Session expired. Please log in again.')
    }

    if (error.response?.status === 429) {
      toast.error('Too many requests. Please slow down.')
    }

    return Promise.reject(error)
  },
)

export default api
