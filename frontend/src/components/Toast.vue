<script setup>
import { useToastStore } from '@/stores/toast'
import {
  CheckCircleIcon,
  ExclamationCircleIcon,
  InformationCircleIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'

const toast = useToastStore()

const icons = {
  success: CheckCircleIcon,
  error: ExclamationCircleIcon,
  info: InformationCircleIcon,
}

const colors = {
  success: 'bg-green-50 text-green-800 border-green-200',
  error: 'bg-red-50 text-red-800 border-red-200',
  info: 'bg-blue-50 text-blue-800 border-blue-200',
}

const iconColors = {
  success: 'text-green-500',
  error: 'text-red-500',
  info: 'text-blue-500',
}
</script>

<template>
  <div class="fixed top-20 right-4 z-[100] flex flex-col gap-2 w-80">
    <TransitionGroup
      enter-from-class="translate-x-full opacity-0"
      enter-active-class="transition duration-300 ease-out"
      leave-to-class="translate-x-full opacity-0"
      leave-active-class="transition duration-200 ease-in"
    >
      <div
        v-for="t in toast.toasts"
        :key="t.id"
        class="rounded-lg border p-4 shadow-lg flex items-start gap-3"
        :class="colors[t.type]"
      >
        <component :is="icons[t.type]" class="h-5 w-5 shrink-0 mt-0.5" :class="iconColors[t.type]" />
        <p class="text-sm flex-1">{{ t.message }}</p>
        <button @click="toast.remove(t.id)" class="shrink-0 cursor-pointer">
          <XMarkIcon class="h-4 w-4 opacity-60 hover:opacity-100" />
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>
