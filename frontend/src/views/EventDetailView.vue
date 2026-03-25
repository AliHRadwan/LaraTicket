<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import api from '@/api'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import {
  CalendarDaysIcon,
  MapPinIcon,
  TicketIcon,
  ClockIcon,
  MinusIcon,
  PlusIcon,
  CurrencyDollarIcon,
} from '@heroicons/vue/24/outline'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const toast = useToastStore()

const event = ref(null)
const loading = ref(true)
const purchasing = ref(false)
const quantity = ref(1)

const maxTickets = computed(() => Math.min(event.value?.available_tickets || 0, 10))
const totalPrice = computed(() => (event.value?.price || 0) * quantity.value)
const soldOut = computed(() => (event.value?.available_tickets || 0) <= 0)
const isPast = computed(() => event.value && new Date(event.value.end_datetime) < new Date())

const formattedPrice = computed(() =>
  new Intl.NumberFormat('en-US', { style: 'currency', currency: 'EGP' }).format(event.value?.price || 0),
)

const formattedTotal = computed(() =>
  new Intl.NumberFormat('en-US', { style: 'currency', currency: 'EGP' }).format(totalPrice.value),
)

function formatDate(date) {
  return new Date(date).toLocaleDateString('en-US', {
    weekday: 'long',
    month: 'long',
    day: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  })
}

async function fetchEvent() {
  loading.value = true
  try {
    const { data } = await api.get(`/events/${route.params.id}`)
    event.value = data.event
  } catch (error) {
    if (error.response?.status === 404) {
      router.replace({ name: 'not-found' })
    }
  } finally {
    loading.value = false
  }
}

async function buyTickets() {
  if (!auth.isAuthenticated) {
    router.push({ name: 'login', query: { redirect: route.fullPath } })
    return
  }

  if (!auth.isVerified) {
    toast.error('Please verify your email before purchasing tickets.')
    router.push({ name: 'verify-email' })
    return
  }

  purchasing.value = true
  try {
    const { data } = await api.post('/orders', {
      event_id: event.value.id,
      tickets_count: quantity.value,
    })

    if (data.checkout_url) {
      window.location.href = data.checkout_url
    }
  } catch (error) {
    const msg = error.response?.data?.message || 'Failed to create order. Please try again.'
    toast.error(msg)
  } finally {
    purchasing.value = false
  }
}

onMounted(fetchEvent)
</script>

<template>
  <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
    <LoadingSpinner v-if="loading" />

    <template v-else-if="event">
      <button
        @click="router.back()"
        class="mb-6 text-sm font-medium text-gray-500 hover:text-indigo-600 transition-colors cursor-pointer"
      >
        &larr; Back to events
      </button>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
          <div class="relative rounded-2xl overflow-hidden bg-gradient-to-br from-indigo-500 to-purple-600 aspect-video">
            <img
              v-if="event.image"
              :src="event.image"
              :alt="event.title"
              class="h-full w-full object-cover"
            />
            <div v-else class="h-full w-full flex items-center justify-center">
              <TicketIcon class="h-24 w-24 text-white/30" />
            </div>
          </div>

          <div>
            <h1 class="text-3xl sm:text-4xl font-extrabold text-gray-900">{{ event.title }}</h1>
            <div class="mt-4 flex flex-wrap gap-4 text-gray-500">
              <div class="flex items-center gap-1.5">
                <CalendarDaysIcon class="h-5 w-5" />
                <span>{{ formatDate(event.start_datetime) }}</span>
              </div>
              <div v-if="event.end_datetime" class="flex items-center gap-1.5">
                <ClockIcon class="h-5 w-5" />
                <span>Ends {{ formatDate(event.end_datetime) }}</span>
              </div>
              <div v-if="event.location" class="flex items-center gap-1.5">
                <MapPinIcon class="h-5 w-5" />
                <span>{{ event.location }}</span>
              </div>
            </div>
          </div>

          <div class="prose prose-gray max-w-none">
            <h2 class="text-xl font-semibold text-gray-900">About this event</h2>
            <div class="text-gray-600 leading-relaxed" v-html="event.description"></div>
          </div>
        </div>

        <!-- Sidebar: Purchase Card -->
        <div class="lg:col-span-1">
          <div class="sticky top-24 rounded-2xl border border-gray-200 bg-white shadow-sm p-6 space-y-5">
            <div class="text-center">
              <p class="text-3xl font-extrabold text-indigo-600">{{ formattedPrice }}</p>
              <p class="text-sm text-gray-500 mt-1">per ticket</p>
            </div>

            <div class="flex items-center justify-between text-sm text-gray-600">
              <div class="flex items-center gap-1.5">
                <TicketIcon class="h-4 w-4" />
                <span>Available</span>
              </div>
              <StatusBadge v-if="soldOut" status="cancelled" />
              <span v-else class="font-semibold text-gray-900">{{ event.available_tickets }}</span>
            </div>

            <template v-if="!soldOut && !isPast">
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Quantity</label>
                <div class="flex items-center gap-3">
                  <button
                    @click="quantity = Math.max(1, quantity - 1)"
                    class="h-10 w-10 rounded-lg border border-gray-200 flex items-center justify-center hover:bg-gray-50 transition-colors cursor-pointer"
                    :disabled="quantity <= 1"
                  >
                    <MinusIcon class="h-4 w-4" />
                  </button>
                  <span class="w-12 text-center text-lg font-semibold">{{ quantity }}</span>
                  <button
                    @click="quantity = Math.min(maxTickets, quantity + 1)"
                    class="h-10 w-10 rounded-lg border border-gray-200 flex items-center justify-center hover:bg-gray-50 transition-colors cursor-pointer"
                    :disabled="quantity >= maxTickets"
                  >
                    <PlusIcon class="h-4 w-4" />
                  </button>
                </div>
              </div>

              <div class="flex items-center justify-between py-3 border-t border-gray-100">
                <span class="text-sm text-gray-500">Total</span>
                <span class="text-xl font-bold text-gray-900">{{ formattedTotal }}</span>
              </div>

              <button
                @click="buyTickets"
                :disabled="purchasing"
                class="w-full rounded-xl bg-indigo-600 py-3.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 cursor-pointer"
              >
                <svg v-if="purchasing" class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
                <CurrencyDollarIcon v-else class="h-5 w-5" />
                {{ purchasing ? 'Processing...' : 'Buy Tickets' }}
              </button>

              <p v-if="!auth.isAuthenticated" class="text-center text-xs text-gray-400">
                You'll need to
                <RouterLink :to="{ name: 'login', query: { redirect: route.fullPath } }" class="text-indigo-600 hover:underline">log in</RouterLink>
                to purchase.
              </p>
            </template>

            <div v-else-if="soldOut" class="text-center py-4">
              <p class="text-sm text-red-500 font-medium">This event is sold out.</p>
            </div>

            <div v-else class="text-center py-4">
              <p class="text-sm text-gray-500 font-medium">This event has ended.</p>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
