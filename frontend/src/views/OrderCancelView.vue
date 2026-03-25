<script setup>
import { RouterLink } from 'vue-router'
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline'
</script>

<template>
  <div class="min-h-[calc(100vh-12rem)] flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md text-center">
      <div class="mx-auto h-20 w-20 rounded-full bg-yellow-50 flex items-center justify-center">
        <ExclamationTriangleIcon class="h-12 w-12 text-yellow-500" />
      </div>
      <h1 class="mt-6 text-3xl font-extrabold text-gray-900">Payment Cancelled</h1>
      <p class="mt-3 text-gray-500">
        Your payment was cancelled. No charges were made. You can try again or browse other events.
      </p>
      <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
        <RouterLink
          to="/"
          class="rounded-xl bg-indigo-600 px-6 py-3 text-sm font-semibold text-white hover:bg-indigo-700 transition-colors"
        >
          Browse Events
        </RouterLink>
        <RouterLink
          to="/orders"
          class="rounded-xl bg-white px-6 py-3 text-sm font-semibold text-gray-700 border border-gray-200 hover:bg-gray-50 transition-colors"
        >
          View My Orders
        </RouterLink>
      </div>
    </div>
  </div>
</template>
