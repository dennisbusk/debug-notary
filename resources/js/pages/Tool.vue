<template>
  <div class="h-full flex flex-col" style="height: calc(100vh - 140px);">
    <Head title="Debug Notary" />

    <div class="flex-1 overflow-hidden rounded-lg shadow-lg bg-white dark:bg-gray-800 h-full border border-gray-200 dark:border-gray-700">
      <iframe
        :src="dashboardUrl"
        class="w-full h-full border-none"
        frameborder="0"
      ></iframe>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const dashboardUrl = computed(() => {
  const prefix = Nova.config('debugNotaryPrefix') || 'laravel-debug-notary'
  return '/' + prefix.replace(/^\/+/, '')
})
</script>
