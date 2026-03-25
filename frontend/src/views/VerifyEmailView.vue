<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import {
  EnvelopeIcon,
  CheckCircleIcon,
  ExclamationCircleIcon,
  ShieldCheckIcon,
} from '@heroicons/vue/24/outline'

const route = useRoute()
const auth = useAuthStore()
const toast = useToastStore()

const resending = ref(false)
const status = ref(route.query.status || null)

const alreadyVerified = computed(() => auth.isVerified && !status.value)

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

onMounted(async () => {
  if (status.value === 'success' || status.value === 'already-verified') {
    await auth.fetchUser()
  }
})
</script>

<template>
  <div class="min-h-[calc(100vh-12rem)] flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md text-center">

      <!-- Success: just verified -->
      <template v-if="status === 'success'">
        <div class="mx-auto h-20 w-20 rounded-full bg-green-50 flex items-center justify-center">
          <CheckCircleIcon class="h-12 w-12 text-green-500" />
        </div>
        <h1 class="mt-6 text-3xl font-extrabold text-gray-900">Email Verified!</h1>
        <p class="mt-3 text-gray-500">Your email has been verified successfully. You can now purchase tickets and enjoy events.</p>
        <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
          <RouterLink
            to="/"
            class="rounded-xl bg-indigo-600 px-6 py-3 text-sm font-semibold text-white hover:bg-indigo-700 transition-colors"
          >
            Browse Events
          </RouterLink>
          <RouterLink
            v-if="auth.isAuthenticated"
            to="/orders"
            class="rounded-xl bg-white px-6 py-3 text-sm font-semibold text-gray-700 border border-gray-200 hover:bg-gray-50 transition-colors"
          >
            My Orders
          </RouterLink>
        </div>
      </template>

      <!-- Already verified -->
      <template v-else-if="status === 'already-verified' || alreadyVerified">
        <div class="mx-auto h-20 w-20 rounded-full bg-blue-50 flex items-center justify-center">
          <ShieldCheckIcon class="h-12 w-12 text-blue-500" />
        </div>
        <h1 class="mt-6 text-3xl font-extrabold text-gray-900">Already Verified</h1>
        <p class="mt-3 text-gray-500">Your email address has already been verified. No further action is needed.</p>
        <RouterLink
          to="/"
          class="mt-8 inline-flex rounded-xl bg-indigo-600 px-6 py-3 text-sm font-semibold text-white hover:bg-indigo-700 transition-colors"
        >
          Browse Events
        </RouterLink>
      </template>

      <!-- Invalid / expired link -->
      <template v-else-if="status === 'invalid'">
        <div class="mx-auto h-20 w-20 rounded-full bg-red-50 flex items-center justify-center">
          <ExclamationCircleIcon class="h-12 w-12 text-red-500" />
        </div>
        <h1 class="mt-6 text-3xl font-extrabold text-gray-900">Verification Failed</h1>
        <p class="mt-3 text-gray-500">The verification link is invalid or has expired. Please request a new one below.</p>
        <div class="mt-8 space-y-4">
          <button
            v-if="auth.isAuthenticated"
            @click="resend"
            :disabled="resending"
            class="inline-flex rounded-xl bg-indigo-600 px-6 py-3 text-sm font-semibold text-white hover:bg-indigo-700 transition-colors disabled:opacity-50 cursor-pointer"
          >
            {{ resending ? 'Sending...' : 'Resend Verification Email' }}
          </button>
          <RouterLink
            v-else
            to="/login"
            class="inline-flex rounded-xl bg-indigo-600 px-6 py-3 text-sm font-semibold text-white hover:bg-indigo-700 transition-colors"
          >
            Log in to resend
          </RouterLink>
        </div>
      </template>

      <!-- Default: waiting for verification (no status param) -->
      <template v-else>
        <div class="mx-auto h-20 w-20 rounded-full bg-indigo-50 flex items-center justify-center">
          <EnvelopeIcon class="h-10 w-10 text-indigo-600" />
        </div>
        <h1 class="mt-6 text-3xl font-extrabold text-gray-900">Check your email</h1>
        <p class="mt-3 text-gray-500 max-w-sm mx-auto">
          We've sent a verification link to
          <span v-if="auth.user" class="font-medium text-gray-900">{{ auth.user.email }}</span>.
          Click the link in the email to verify your account.
        </p>
        <div class="mt-8 space-y-4">
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
