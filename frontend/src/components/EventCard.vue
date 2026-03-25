<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import {
  CalendarDaysIcon,
  MapPinIcon,
  TicketIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  event: { type: Object, required: true },
})

const formattedDate = computed(() => {
  return new Date(props.event.start_datetime).toLocaleDateString('en-US', {
    weekday: 'short',
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  })
})

const soldOut = computed(() => props.event.available_tickets <= 0)

const formattedPrice = computed(() => {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'EGP' }).format(props.event.price)
})
</script>

<template>
  <RouterLink
    :to="{ name: 'event-detail', params: { id: event.slug || event.id } }"
    class="group flex flex-col bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-lg hover:-translate-y-1 transition-all duration-300"
  >
    <div class="relative h-48 overflow-hidden bg-gradient-to-br from-indigo-500 to-purple-600">
      <img
        v-if="event.image"
        :src="event.image"
        :alt="event.title"
        class="h-full w-full object-cover group-hover:scale-105 transition-transform duration-500"
      />
      <div v-else class="h-full w-full flex items-center justify-center">
        <TicketIcon class="h-16 w-16 text-white/40" />
      </div>
      <div
        v-if="soldOut"
        class="absolute top-3 right-3 bg-red-500 text-white text-xs font-bold px-3 py-1 rounded-full"
      >
        Sold Out
      </div>
      <div
        v-else
        class="absolute top-3 right-3 bg-white/90 backdrop-blur text-indigo-700 text-xs font-bold px-3 py-1 rounded-full"
      >
        {{ event.available_tickets }} left
      </div>
    </div>

    <div class="flex flex-col flex-1 p-5">
      <h3 class="font-semibold text-gray-900 text-lg leading-tight group-hover:text-indigo-600 transition-colors line-clamp-2">
        {{ event.title }}
      </h3>

      <div class="mt-3 space-y-1.5 text-sm text-gray-500">
        <div class="flex items-center gap-1.5">
          <CalendarDaysIcon class="h-4 w-4 shrink-0" />
          <span>{{ formattedDate }}</span>
        </div>
        <div v-if="event.location" class="flex items-center gap-1.5">
          <MapPinIcon class="h-4 w-4 shrink-0" />
          <span class="truncate">{{ event.location }}</span>
        </div>
      </div>

      <div class="mt-auto pt-4 flex items-center justify-between border-t border-gray-50">
        <span class="text-lg font-bold text-indigo-600">{{ formattedPrice }}</span>
        <span class="text-sm font-medium text-indigo-600 group-hover:translate-x-1 transition-transform">&rarr;</span>
      </div>
    </div>
  </RouterLink>
</template>
