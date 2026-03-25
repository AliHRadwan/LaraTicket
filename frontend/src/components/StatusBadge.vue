<script setup>
import { computed } from 'vue'

const props = defineProps({
  status: { type: String, required: true },
})

const config = computed(() => {
  const map = {
    pending: { label: 'Pending', classes: 'bg-yellow-50 text-yellow-700 ring-yellow-600/20' },
    completed: { label: 'Completed', classes: 'bg-green-50 text-green-700 ring-green-600/20' },
    cancelled: { label: 'Cancelled', classes: 'bg-red-50 text-red-700 ring-red-600/20' },
    refunded: { label: 'Refunded', classes: 'bg-gray-50 text-gray-700 ring-gray-600/20' },
    paid: { label: 'Paid', classes: 'bg-green-50 text-green-700 ring-green-600/20' },
    failed: { label: 'Failed', classes: 'bg-red-50 text-red-700 ring-red-600/20' },
  }
  return map[props.status?.toLowerCase()] || { label: props.status, classes: 'bg-gray-50 text-gray-700 ring-gray-600/20' }
})
</script>

<template>
  <span
    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset capitalize"
    :class="config.classes"
  >
    {{ config.label }}
  </span>
</template>
