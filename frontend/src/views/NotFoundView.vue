<script setup>
import { RouterLink } from 'vue-router'
</script>

<template>
  <div class="min-h-[calc(100vh-12rem)] flex items-center justify-center px-4 py-12">
    <div class="text-center">
      <p class="text-7xl font-extrabold text-indigo-600">404</p>
      <h1 class="mt-4 text-3xl font-bold text-gray-900">Page not found</h1>
      <p class="mt-3 text-gray-500">Sorry, we couldn't find what you're looking for.</p>
      <RouterLink
        to="/"
        class="mt-8 inline-flex rounded-xl bg-indigo-600 px-6 py-3 text-sm font-semibold text-white hover:bg-indigo-700 transition-colors"
      >
        Go back home
      </RouterLink>
    </div>
  </div>
</template>
