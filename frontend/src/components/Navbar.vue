<script setup>
import { ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { Bars3Icon, XMarkIcon, TicketIcon, Cog6ToothIcon } from '@heroicons/vue/24/outline'

const auth = useAuthStore()
const mobileOpen = ref(false)
const adminUrl = `${__BACKEND_URL__}/admin`
</script>

<template>
  <nav class="bg-white/80 backdrop-blur-lg border-b border-gray-200 sticky top-0 z-50">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="flex h-16 items-center justify-between">
        <RouterLink to="/" class="flex items-center gap-2 text-indigo-600 font-bold text-xl" @click="mobileOpen = false">
          <TicketIcon class="h-7 w-7" />
          <span>LaraTicket</span>
        </RouterLink>

        <div class="hidden sm:flex items-center gap-6">
          <RouterLink to="/" class="text-sm font-medium text-gray-600 hover:text-indigo-600 transition-colors">Events</RouterLink>
          <template v-if="auth.isAuthenticated">
            <RouterLink to="/orders" class="text-sm font-medium text-gray-600 hover:text-indigo-600 transition-colors">My Orders</RouterLink>
            <a
              v-if="auth.isAdmin"
              :href="adminUrl"
              target="_blank"
              class="flex items-center gap-1 text-sm font-medium text-amber-600 hover:text-amber-700 transition-colors"
            >
              <Cog6ToothIcon class="h-4 w-4" />
              Admin
            </a>
            <div class="flex items-center gap-3 pl-4 border-l border-gray-200">
              <span class="text-sm text-gray-500">{{ auth.user?.name }}</span>
              <button
                @click="auth.logout()"
                class="text-sm font-medium text-red-600 hover:text-red-700 transition-colors cursor-pointer"
              >
                Logout
              </button>
            </div>
          </template>
          <template v-else>
            <RouterLink to="/login" class="text-sm font-medium text-gray-600 hover:text-indigo-600 transition-colors">Login</RouterLink>
            <RouterLink
              to="/register"
              class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 transition-colors"
            >
              Sign Up
            </RouterLink>
          </template>
        </div>

        <button @click="mobileOpen = !mobileOpen" class="sm:hidden p-2 text-gray-600 cursor-pointer">
          <Bars3Icon v-if="!mobileOpen" class="h-6 w-6" />
          <XMarkIcon v-else class="h-6 w-6" />
        </button>
      </div>
    </div>

    <Transition
      enter-from-class="opacity-0 -translate-y-2"
      enter-active-class="transition duration-200"
      leave-to-class="opacity-0 -translate-y-2"
      leave-active-class="transition duration-150"
    >
      <div v-if="mobileOpen" class="sm:hidden border-t border-gray-200 bg-white px-4 pb-4 pt-2 space-y-2">
        <RouterLink to="/" class="block rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50" @click="mobileOpen = false">Events</RouterLink>
        <template v-if="auth.isAuthenticated">
          <RouterLink to="/orders" class="block rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50" @click="mobileOpen = false">My Orders</RouterLink>
          <a
            v-if="auth.isAdmin"
            :href="adminUrl"
            target="_blank"
            class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-amber-600 hover:bg-amber-50"
            @click="mobileOpen = false"
          >
            <Cog6ToothIcon class="h-4 w-4" />
            Admin Dashboard
          </a>
          <button
            @click="auth.logout(); mobileOpen = false"
            class="block w-full text-left rounded-lg px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50 cursor-pointer"
          >
            Logout ({{ auth.user?.name }})
          </button>
        </template>
        <template v-else>
          <RouterLink to="/login" class="block rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50" @click="mobileOpen = false">Login</RouterLink>
          <RouterLink to="/register" class="block rounded-lg px-3 py-2 text-sm font-medium text-white bg-indigo-600 text-center hover:bg-indigo-700" @click="mobileOpen = false">Sign Up</RouterLink>
        </template>
      </div>
    </Transition>
  </nav>
</template>
