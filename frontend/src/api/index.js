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
  if (config.headers.has('Authorization')) {
    return config
  }
  const token = localStorage.getItem('token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

let isHandling401 = false

api.interceptors.response.use(
  (response) => response,
  (error) => {
    const status = error.response?.status
    const reqUrl = String(error.config?.url ?? '')
    // Logout may 401 after the client already cleared storage and registered a new user;
    // never run global session teardown for that response.
    const skipGlobal401 = reqUrl.includes('auth/logout')

    if (status === 401 && !skipGlobal401 && !isHandling401) {
      isHandling401 = true

      const auth = useAuthStore()
      auth.clearSession()

      const currentRoute = router.currentRoute.value
      if (currentRoute.name !== 'login' && currentRoute.meta.auth) {
        router.push({ name: 'login', query: { redirect: currentRoute.fullPath } })
        const toast = useToastStore()
        toast.error('Session expired. Please log in again.')
      }

      setTimeout(() => { isHandling401 = false }, 2000)
    }

    if (error.response?.status === 429) {
      const toast = useToastStore()
      toast.error('Too many requests. Please slow down.')
    }

    return Promise.reject(error)
  },
)

export default api
