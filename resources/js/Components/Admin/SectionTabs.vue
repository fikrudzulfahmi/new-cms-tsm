<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

const props = defineProps({
  items: { type: Array, required: true },
})

const page = usePage()
const current = computed(() => (page.url || '').split('?')[0])

const isActive = (href) => current.value === href || current.value.startsWith(href + '/')
</script>

<template>
  <nav class="overflow-x-auto -mx-1 px-1" aria-label="Bagian halaman">
    <div class="inline-flex gap-1 p-1 rounded-full border border-line bg-white min-w-full sm:min-w-0">
      <Link
        v-for="item in items"
        :key="item.href"
        :href="item.href"
        :aria-current="isActive(item.href) ? 'page' : undefined"
        :class="[
          'px-4 py-2 rounded-full text-sm font-semibold whitespace-nowrap transition-colors no-underline',
          isActive(item.href) ? 'bg-navy text-white' : 'text-slate-600 hover:bg-slate-100 hover:text-navy',
        ]"
      >
        {{ item.label }}
      </Link>
    </div>
  </nav>
</template>
