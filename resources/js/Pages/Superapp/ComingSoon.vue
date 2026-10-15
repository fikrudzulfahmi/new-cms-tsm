<script setup>
import { Link } from '@inertiajs/vue3'
import SuperappLayout from '@/Layouts/SuperappLayout.vue'

defineProps({
  title: String,
  desc: String,
  plan: { type: Array, default: () => [] },
  layout: { type: String, default: 'portal' },
  feature: String,
  adminUrl: String,
})
</script>

<template>
  <SuperappLayout :variant="layout">
    <title>{{ title }} - TSM Superapp</title>
    <div class="max-w-4xl mx-auto space-y-6">
      <Link v-if="layout === 'portal'" href="/portal" class="text-sm text-slate-500 hover:text-label">← Kembali ke Portal</Link>

      <div class="relative overflow-hidden rounded-[28px] grid-dark border border-accent/25 p-8 sm:p-12 text-center">
        <svg class="mx-auto mb-6 text-accent animate-[spin_12s_linear_infinite]" width="72" height="72" viewBox="0 0 40 40" fill="none" aria-hidden="true">
          <circle cx="20" cy="20" r="17" stroke="currentColor" stroke-width="2" />
          <circle cx="20" cy="20" r="6" fill="currentColor" />
          <path d="M20 3v8M20 29v8M3 20h8M29 20h8M8 8l5.5 5.5M26.5 26.5L32 32M32 8l-5.5 5.5M13.5 26.5L8 32" stroke="currentColor" stroke-width="1.6" />
        </svg>
        <span class="inline-block font-mono text-[11px] tracking-[2px] uppercase text-accent border border-accent/40 rounded-full px-3 py-1 mb-4">Segera Hadir</span>
        <h1 class="font-display text-3xl sm:text-4xl font-extrabold text-white tracking-[-1px] mb-3">{{ title }}</h1>
        <p class="text-on-navy-muted max-w-xl mx-auto">{{ desc }}</p>
        <p class="text-on-navy-subtle text-sm mt-4">Fitur ini sedang dalam pengembangan.</p>
        <Link v-if="adminUrl" :href="adminUrl" class="inline-flex mt-6 px-5 py-2.5 rounded-full bg-accent text-navy text-sm font-bold">Buka halaman admin fitur</Link>
      </div>

      <div v-if="plan.length" class="card p-6">
        <h2 class="font-bold text-slate-800 mb-1">Rencana fitur</h2>
        <p class="text-xs text-slate-500 mb-5">Gambaran yang akan tersedia di sini.</p>
        <div class="grid gap-4" :class="{ 'md:grid-cols-2': plan.length === 2, 'md:grid-cols-3': plan.length >= 3 }">
          <div v-for="p in plan" :key="p.label" class="rounded-2xl bg-slate-50 p-5">
            <div class="font-mono text-[11px] tracking-[2px] uppercase text-label mb-3">{{ p.label }}</div>
            <ul class="space-y-2">
              <li v-for="it in p.items" :key="it" class="flex gap-2 text-sm text-slate-600">
                <span class="text-accent-strong mt-0.5">●</span><span>{{ it }}</span>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </SuperappLayout>
</template>
