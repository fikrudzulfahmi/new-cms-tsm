<script setup>
import { Link } from '@inertiajs/vue3'
import LmsShell from '@/Components/Lms/LmsShell.vue'
defineProps({ courses: Array })
const palette = ['from-navy to-navy-panel', 'from-label to-navy-panel', 'from-navy-panel to-label']
</script>

<template>
  <LmsShell title="Mapel Saya">
    <p v-if="!courses.length" class="rounded-2xl border border-dashed border-line bg-white p-8 text-center text-sm text-slate-500">Belum ada mapel untuk kelas Anda pada tahun ajaran ini.</p>
    <div class="grid gap-4 sm:grid-cols-2">
      <Link v-for="(c, n) in courses" :key="c.id" :href="`/belajar/mapel/${c.id}`" class="group overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-line transition hover:-translate-y-0.5 hover:shadow-md hover:ring-accent">
        <div :class="['flex items-center gap-3 bg-gradient-to-r px-5 py-4 text-white', palette[n % 3]]">
          <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/15 font-display text-lg font-bold text-accent">{{ c.title?.[0] }}</span>
          <div class="min-w-0"><div class="truncate font-semibold">{{ c.title }}</div><div class="truncate text-xs text-on-navy-muted">{{ c.teacher }}</div></div>
        </div>
        <div class="px-5 py-4">
          <div class="mb-1.5 flex justify-between text-xs text-slate-500"><span>Progres materi</span><span class="font-semibold text-navy">{{ c.done }}/{{ c.total }}</span></div>
          <div class="h-2 overflow-hidden rounded-full bg-ice"><div class="h-full rounded-full bg-gradient-to-r from-accent to-label transition-all" :style="{ width: (c.total ? (c.done / c.total) * 100 : 0) + '%' }"></div></div>
        </div>
      </Link>
    </div>
  </LmsShell>
</template>
