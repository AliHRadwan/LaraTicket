<script setup>
import { TicketIcon } from '@heroicons/vue/24/outline'
</script>

<template>
  <footer class="bg-gray-900 text-gray-400 mt-auto">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12">
      <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="flex items-center gap-2 text-white font-semibold">
          <TicketIcon class="h-6 w-6 text-indigo-400" />
          <span>LaraTicket</span>
        </div>
        <p class="text-sm">
          &copy; {{ new Date().getFullYear() }} LaraTicket. All rights reserved.
        </p>
      </div>
    </div>
  </footer>
</template>
