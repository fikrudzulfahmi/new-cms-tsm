<script setup>
import { Link } from '@inertiajs/vue3'
import LmsShell from '@/Components/Lms/LmsShell.vue'
defineProps({ courses: Array })
</script>

<template>
  <LmsShell title="Mapel Saya">
    <p v-if="!courses.length" class="text-slate-500 text-sm bg-white rounded-2xl border p-6">Belum ada mapel untuk kelas Anda pada tahun ajaran ini.</p>
    <div class="grid sm:grid-cols-2 gap-4">
      <Link v-for="c in courses" :key="c.id" :href="`/belajar/mapel/${c.id}`" class="block bg-white rounded-2xl border border-slate-200 p-5 hover:border-navy transition">
        <div class="font-semibold text-navy">{{ c.title }}</div>
        <div class="text-xs text-slate-500 mt-1">{{ c.teacher }}</div>
        <div class="mt-3 h-2 bg-slate-100 rounded-full overflow-hidden"><div class="h-full bg-accent" :style="{ width: (c.total ? (c.done / c.total) * 100 : 0) + '%' }"></div></div>
        <div class="text-xs text-slate-500 mt-1">{{ c.done }}/{{ c.total }} materi selesai</div>
      </Link>
    </div>
  </LmsShell>
</template>
