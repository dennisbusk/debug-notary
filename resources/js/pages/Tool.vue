<template>
  <div class="h-full flex flex-col no-scrollbar" style="height: calc(100vh - 140px);">
    <Head title="Debug Notary" />

    <div class="flex-1 overflow-hidden rounded-lg shadow-lg bg-white dark:bg-gray-800 h-full border border-gray-200 dark:border-gray-700 no-scrollbar">
      <iframe
        :src="dashboardUrl"
        class="w-full h-full border-none no-scrollbar"
        frameborder="0"
        scrolling="auto"
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

<style scoped>
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
