import { defineStore } from 'pinia'
import { ref } from 'vue'

let nextId = 0

export const useToastStore = defineStore('toast', () => {
  const toasts = ref([])

  function add(message, type = 'info', duration = 4000) {
    const id = nextId++
    toasts.value.push({ id, message, type })
    setTimeout(() => remove(id), duration)
  }

  function remove(id) {
    toasts.value = toasts.value.filter((t) => t.id !== id)
  }

  function success(message) {
    add(message, 'success')
  }

  function error(message) {
    add(message, 'error', 6000)
  }

  function info(message) {
    add(message, 'info')
  }

  return { toasts, add, remove, success, error, info }
})
