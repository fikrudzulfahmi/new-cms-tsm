<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import SuperappLayout from '@/Layouts/SuperappLayout.vue'

defineProps({ cards: { type: Array, default: () => [] } })
const user = computed(() => usePage().props.auth?.user)
const greeting = (() => {
  const h = new Date().getHours()
  return h < 11 ? 'Selamat pagi' : h < 15 ? 'Selamat siang' : h < 18 ? 'Selamat sore' : 'Selamat malam'
})()
</script>

<template>
  <SuperappLayout variant="portal">
    <title>Portal - TSM Superapp</title>
    <div class="max-w-6xl mx-auto space-y-8">
      <div>
        <span class="block font-mono text-[11px] tracking-[2px] uppercase text-label mb-1">TSM Superapp</span>
        <h1 class="font-display text-[28px] sm:text-[34px] font-bold text-navy tracking-[-0.5px]">{{ greeting }}, {{ user?.name }}</h1>
        <p class="text-slate-500 mt-1">Pilih fitur yang ingin Anda buka.</p>
      </div>

      <div v-if="cards.length" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
        <Link v-for="c in cards" :key="c.key" :href="c.url"
          class="group card p-6 flex flex-col gap-4 hover:-translate-y-0.5 hover:shadow-lg transition">
          <div class="flex items-start justify-between gap-3">
            <div class="w-12 h-12 rounded-2xl bg-navy text-accent flex items-center justify-center">
              <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" :d="c.icon" /></svg>
            </div>
            <span class="font-mono text-[10px] tracking-wider uppercase px-2 py-1 rounded-full bg-amber-100 text-amber-700">Segera</span>
          </div>
          <div>
            <div class="font-display text-xl font-bold text-navy">{{ c.name }}</div>
            <div class="text-xs text-slate-500">{{ c.full }}</div>
          </div>
          <p class="text-sm text-slate-600 flex-1">{{ c.desc }}</p>
          <div class="flex flex-wrap items-center gap-2">
            <span v-for="r in c.roles" :key="r" class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-blue-50 text-blue-800">{{ r }}</span>
            <span class="ml-auto text-sm font-semibold text-label group-hover:translate-x-0.5 transition">Buka →</span>
          </div>
        </Link>
      </div>

      <div v-else class="card p-10 text-center">
        <div class="font-display text-xl font-bold text-navy mb-2">Belum ada fitur untuk akun Anda</div>
        <p class="text-slate-500 text-sm max-w-md mx-auto">Akun Anda belum ditambahkan ke fitur apa pun (LMS, PKL, UKK, atau Perpustakaan Digital). Silakan hubungi admin fitur terkait.</p>
      </div>
    </div>
  </SuperappLayout>
</template>
