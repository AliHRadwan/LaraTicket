<script setup>
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import api from '@/api'
import StatusBadge from '@/components/StatusBadge.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import { TicketIcon, CalendarDaysIcon } from '@heroicons/vue/24/outline'

const orders = ref([])
const loading = ref(true)
const pagination = ref({})

async function fetchOrders(page = 1) {
  loading.value = true
  try {
    const { data } = await api.get('/orders', { params: { page, per_page: 10 } })
    orders.value = data.data
    pagination.value = {
      currentPage: data.current_page,
      lastPage: data.last_page,
      total: data.total,
    }
  } catch {
    orders.value = []
  } finally {
    loading.value = false
  }
}

function formatDate(date) {
  return new Date(date).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  })
}

function formatPrice(amount) {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'EGP' }).format(amount)
}

function changePage(page) {
  fetchOrders(page)
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

onMounted(() => fetchOrders())
</script>

<template>
  <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-3xl font-extrabold text-gray-900">My Orders</h1>
    <p class="mt-1 text-gray-500">View your ticket purchase history</p>

    <LoadingSpinner v-if="loading" />

    <template v-else-if="orders.length">
      <div class="mt-8 space-y-4">
        <div
          v-for="order in orders"
          :key="order.id"
          class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 sm:p-6 hover:shadow-md transition-shadow"
        >
          <div class="flex flex-col sm:flex-row sm:items-center gap-4">
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-3">
                <h3 class="font-semibold text-gray-900 truncate">
                  {{ order.event?.title || `Order #${order.id}` }}
                </h3>
                <StatusBadge :status="order.status" />
              </div>
              <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm text-gray-500">
                <div class="flex items-center gap-1">
                  <TicketIcon class="h-4 w-4" />
                  <span>{{ order.tickets_count }} ticket{{ order.tickets_count > 1 ? 's' : '' }}</span>
                </div>
                <div class="flex items-center gap-1">
                  <CalendarDaysIcon class="h-4 w-4" />
                  <span>{{ formatDate(order.created_at) }}</span>
                </div>
              </div>
            </div>
            <div class="text-right shrink-0">
              <p class="text-lg font-bold text-gray-900">{{ formatPrice(order.total_price) }}</p>
              <p class="text-xs text-gray-400">Order #{{ order.id }}</p>
            </div>
          </div>
        </div>
      </div>

      <nav v-if="pagination.lastPage > 1" class="mt-8 flex justify-center gap-2">
        <button
          v-for="page in pagination.lastPage"
          :key="page"
          @click="changePage(page)"
          class="h-10 min-w-10 rounded-lg px-3 text-sm font-medium transition-colors cursor-pointer"
          :class="page === pagination.currentPage
            ? 'bg-indigo-600 text-white'
            : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50'"
        >
          {{ page }}
        </button>
      </nav>
    </template>

    <div v-else class="text-center py-20">
      <TicketIcon class="h-16 w-16 text-gray-300 mx-auto" />
      <h2 class="mt-4 text-xl font-semibold text-gray-900">No orders yet</h2>
      <p class="mt-2 text-gray-500">Your ticket purchases will appear here.</p>
      <RouterLink
        to="/"
        class="mt-6 inline-flex rounded-xl bg-indigo-600 px-6 py-3 text-sm font-semibold text-white hover:bg-indigo-700 transition-colors"
      >
        Browse Events
      </RouterLink>
    </div>
  </div>
</template>
