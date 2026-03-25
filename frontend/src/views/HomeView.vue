<script setup>
import { ref, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/api'
import EventCard from '@/components/EventCard.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import { MagnifyingGlassIcon, FunnelIcon } from '@heroicons/vue/24/outline'

const route = useRoute()
const router = useRouter()

const events = ref([])
const loading = ref(true)
const pagination = ref({})
const search = ref(route.query.search || '')
const sortBy = ref(route.query.sort || '')

let debounceTimer = null

async function fetchEvents(page = 1) {
  loading.value = true
  try {
    const params = { page, per_page: 12 }
    if (search.value) params.search = search.value
    if (sortBy.value) params.sort = sortBy.value

    const { data } = await api.get('/events', { params })
    events.value = data.data
    pagination.value = {
      currentPage: data.current_page,
      lastPage: data.last_page,
      total: data.total,
    }
  } catch {
    events.value = []
  } finally {
    loading.value = false
  }
}

function onSearch() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    router.replace({ query: { ...route.query, search: search.value || undefined } })
    fetchEvents(1)
  }, 400)
}

function changePage(page) {
  fetchEvents(page)
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

watch(sortBy, (val) => {
  router.replace({ query: { ...route.query, sort: val || undefined } })
  fetchEvents(1)
})

onMounted(() => fetchEvents(route.query.page || 1))
</script>

<template>
  <div>
    <!-- Hero -->
    <section class="relative bg-gradient-to-br from-indigo-600 via-indigo-700 to-purple-800 overflow-hidden">
      <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg%20width%3D%2260%22%20height%3D%2260%22%20viewBox%3D%220%200%2060%2060%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Cg%20fill%3D%22none%22%20fill-rule%3D%22evenodd%22%3E%3Cg%20fill%3D%22%23fff%22%20fill-opacity%3D%220.05%22%3E%3Cpath%20d%3D%22M36%2034v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6%2034v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6%204V0H4v4H0v2h4v4h2V6h4V4H6z%22%2F%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E')] opacity-30"></div>
      <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-20 sm:py-28 text-center">
        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-white tracking-tight">
          Discover Amazing Events
        </h1>
        <p class="mt-4 text-lg sm:text-xl text-indigo-100 max-w-2xl mx-auto">
          Find and book tickets for concerts, conferences, workshops, and more.
        </p>
        <div class="mt-8 flex flex-col sm:flex-row gap-3 max-w-xl mx-auto">
          <div class="relative flex-1">
            <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
            <input
              v-model="search"
              @input="onSearch"
              type="text"
              placeholder="Search events..."
              class="w-full rounded-xl bg-white/95 backdrop-blur pl-10 pr-4 py-3 text-gray-900 placeholder-gray-400 shadow-lg focus:outline-none focus:ring-2 focus:ring-white/50"
            />
          </div>
          <div class="relative">
            <FunnelIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400 pointer-events-none" />
            <select
              v-model="sortBy"
              class="appearance-none w-full sm:w-auto rounded-xl bg-white/95 backdrop-blur pl-10 pr-10 py-3 text-gray-900 shadow-lg focus:outline-none focus:ring-2 focus:ring-white/50 cursor-pointer"
            >
              <option value="">Most Recent</option>
              <option value="start_date">Event Date</option>
              <option value="price">Price</option>
              <option value="title">Name</option>
            </select>
          </div>
        </div>
      </div>
    </section>

    <!-- Events Grid -->
    <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12">
      <LoadingSpinner v-if="loading" />

      <template v-else-if="events.length">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
          <EventCard v-for="event in events" :key="event.id" :event="event" />
        </div>

        <!-- Pagination -->
        <nav v-if="pagination.lastPage > 1" class="mt-12 flex justify-center gap-2">
          <button
            v-for="page in pagination.lastPage"
            :key="page"
            @click="changePage(page)"
            class="h-10 min-w-10 rounded-lg px-3 text-sm font-medium transition-colors cursor-pointer"
            :class="page === pagination.currentPage
              ? 'bg-indigo-600 text-white shadow-sm'
              : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50'"
          >
            {{ page }}
          </button>
        </nav>
      </template>

      <div v-else class="text-center py-20">
        <p class="text-gray-400 text-lg">No events found.</p>
        <button
          v-if="search"
          @click="search = ''; fetchEvents(1)"
          class="mt-4 text-indigo-600 font-medium hover:text-indigo-700 cursor-pointer"
        >
          Clear search
        </button>
      </div>
    </section>
  </div>
</template>
