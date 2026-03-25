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

    const requestToken = token.value

    try {
      const { data } = await api.get('/auth/user')
      if (token.value !== requestToken) {
        return
      }
      user.value = data.user
    } catch {
      if (token.value === requestToken) {
        clearAuth()
      }
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

  async function logout() {
    const staleToken = token.value || localStorage.getItem('token')
    clearAuth()

    if (!staleToken) {
      return
    }

    try {
      await api.post('/auth/logout', null, {
        headers: { Authorization: `Bearer ${staleToken}` },
      })
    } catch {
      // Client session is already cleared; ignore network or 401
    }
  }

  function clearSession() {
    clearAuth()
  }

  async function resendVerification() {
    return api.post('/auth/email/resend')
  }

  function setAuth(newToken, newUser) {
    token.value = newToken
    user.value = newUser
    checked.value = true
    localStorage.setItem('token', newToken)
  }

  function clearAuth() {
    token.value = null
    user.value = null
    checked.value = false
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
    clearSession,
    resendVerification,
  }
})
