<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import api from '@/api'
import { EnvelopeIcon, CheckCircleIcon, ExclamationCircleIcon } from '@heroicons/vue/24/outline'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const toast = useToastStore()

const resending = ref(false)
const verifying = ref(false)
const verified = ref(false)
const verifyError = ref(false)

const alreadyVerified = computed(() => auth.isVerified)

async function verifyFromUrl() {
  const { id, hash, expires, signature } = route.query
  if (!id || !hash) return

  verifying.value = true
  try {
    await api.get(`/auth/email/verify/${id}/${hash}`, {
      params: { expires, signature },
    })
    verified.value = true
    await auth.fetchUser()
    toast.success('Email verified successfully!')
    setTimeout(() => router.push('/'), 2000)
  } catch {
    verifyError.value = true
    toast.error('Verification link is invalid or expired.')
  } finally {
    verifying.value = false
  }
}

async function resend() {
  resending.value = true
  try {
    await auth.resendVerification()
    toast.success('Verification email sent! Check your inbox.')
  } catch {
    toast.error('Failed to resend. Please try again later.')
  } finally {
    resending.value = false
  }
}

onMounted(() => {
  if (route.query.id && route.query.hash) {
    verifyFromUrl()
  }
})
</script>

<template>
  <div class="min-h-[calc(100vh-12rem)] flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md text-center">
      <!-- Already Verified -->
      <template v-if="alreadyVerified || verified">
        <CheckCircleIcon class="h-16 w-16 text-green-500 mx-auto" />
        <h1 class="mt-4 text-2xl font-bold text-gray-900">Email Verified!</h1>
        <p class="mt-2 text-gray-500">Your email has been verified. You're all set.</p>
        <RouterLink
          to="/"
          class="mt-6 inline-flex rounded-xl bg-indigo-600 px-6 py-3 text-sm font-semibold text-white hover:bg-indigo-700 transition-colors"
        >
          Browse Events
        </RouterLink>
      </template>

      <!-- Verifying from URL -->
      <template v-else-if="verifying">
        <div class="flex items-center justify-center py-12">
          <svg class="animate-spin h-10 w-10 text-indigo-600" viewBox="0 0 24 24" fill="none">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
          </svg>
        </div>
        <h1 class="mt-4 text-2xl font-bold text-gray-900">Verifying your email...</h1>
      </template>

      <!-- Verification Error -->
      <template v-else-if="verifyError">
        <ExclamationCircleIcon class="h-16 w-16 text-red-500 mx-auto" />
        <h1 class="mt-4 text-2xl font-bold text-gray-900">Verification Failed</h1>
        <p class="mt-2 text-gray-500">The link is invalid or has expired. Request a new one below.</p>
        <button
          v-if="auth.isAuthenticated"
          @click="resend"
          :disabled="resending"
          class="mt-6 inline-flex rounded-xl bg-indigo-600 px-6 py-3 text-sm font-semibold text-white hover:bg-indigo-700 transition-colors disabled:opacity-50 cursor-pointer"
        >
          {{ resending ? 'Sending...' : 'Resend Verification Email' }}
        </button>
      </template>

      <!-- Waiting for Verification -->
      <template v-else>
        <div class="mx-auto h-20 w-20 rounded-full bg-indigo-50 flex items-center justify-center">
          <EnvelopeIcon class="h-10 w-10 text-indigo-600" />
        </div>
        <h1 class="mt-6 text-2xl font-bold text-gray-900">Check your email</h1>
        <p class="mt-2 text-gray-500 max-w-sm mx-auto">
          We've sent a verification link to
          <span v-if="auth.user" class="font-medium text-gray-900">{{ auth.user.email }}</span>.
          Click the link to verify your account.
        </p>
        <div class="mt-8 space-y-3">
          <button
            v-if="auth.isAuthenticated"
            @click="resend"
            :disabled="resending"
            class="inline-flex rounded-xl bg-indigo-600 px-6 py-3 text-sm font-semibold text-white hover:bg-indigo-700 transition-colors disabled:opacity-50 cursor-pointer"
          >
            {{ resending ? 'Sending...' : 'Resend Verification Email' }}
          </button>
          <p class="text-xs text-gray-400">Didn't receive it? Check your spam folder or click resend.</p>
        </div>
      </template>
    </div>
  </div>
</template>
