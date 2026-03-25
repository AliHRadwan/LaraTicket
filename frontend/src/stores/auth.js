import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/api'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const token = ref(localStorage.getItem('token'))
  const checked = ref(false)

  const isAuthenticated = computed(() => !!user.value)
  const isVerified = computed(() => !!user.value?.email_verified_at)
  const isAdmin = computed(() => !!user.value?.is_admin)

  async function fetchUser() {
    if (!token.value) {
      checked.value = true
      return
    }

    try {
      const { data } = await api.get('/auth/user')
      user.value = data.user
    } catch {
      clearAuth()
    } finally {
      checked.value = true
    }
  }

  async function login(credentials) {
    const { data } = await api.post('/auth/login', credentials)
    setAuth(data.token, data.user)
    return data
  }

  async function register(payload) {
    const { data } = await api.post('/auth/register', payload)
    setAuth(data.token, data.user)
    return data
  }

  function logout() {
    api.post('/auth/logout').catch(() => {})
    clearAuth()
  }

  async function resendVerification() {
    return api.post('/auth/email/resend')
  }

  function setAuth(newToken, newUser) {
    token.value = newToken
    user.value = newUser
    localStorage.setItem('token', newToken)
  }

  function clearAuth() {
    token.value = null
    user.value = null
    localStorage.removeItem('token')
  }

  return {
    user,
    token,
    checked,
    isAuthenticated,
    isVerified,
    isAdmin,
    fetchUser,
    login,
    register,
    logout,
    resendVerification,
  }
})
