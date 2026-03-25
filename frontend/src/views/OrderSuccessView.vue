<script setup>
import { RouterLink, useRoute } from 'vue-router'
import { CheckCircleIcon } from '@heroicons/vue/24/outline'

const route = useRoute()
const orderId = route.params.id
</script>

<template>
  <div class="min-h-[calc(100vh-12rem)] flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md text-center">
      <div class="mx-auto h-20 w-20 rounded-full bg-green-50 flex items-center justify-center">
        <CheckCircleIcon class="h-12 w-12 text-green-500" />
      </div>
      <h1 class="mt-6 text-3xl font-extrabold text-gray-900">Payment Successful!</h1>
      <p class="mt-3 text-gray-500">
        Your tickets have been booked successfully.
        <span v-if="orderId">Order <span class="font-semibold text-gray-700">#{{ orderId }}</span> is confirmed.</span>
      </p>
      <p class="mt-2 text-sm text-gray-400">You'll receive a confirmation email shortly.</p>
      <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
        <RouterLink
          to="/orders"
          class="rounded-xl bg-indigo-600 px-6 py-3 text-sm font-semibold text-white hover:bg-indigo-700 transition-colors"
        >
          View My Orders
        </RouterLink>
        <RouterLink
          to="/"
          class="rounded-xl bg-white px-6 py-3 text-sm font-semibold text-gray-700 border border-gray-200 hover:bg-gray-50 transition-colors"
        >
          Browse More Events
        </RouterLink>
      </div>
    </div>
  </div>
</template>
