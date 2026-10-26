<script setup>
import { Link } from '@inertiajs/vue3'
import LmsShell from '@/Components/Lms/LmsShell.vue'
defineProps({ course: Object, sections: Array })
const typeLabel = { reading: 'Bacaan', video: 'Video', file: 'Berkas' }
</script>

<template>
  <LmsShell :title="course.title" back="/belajar" backLabel="Mapel saya">
    <p v-if="course.description" class="text-sm text-slate-600 -mt-2 mb-4 whitespace-pre-line">{{ course.description }}</p>
    <p v-if="!sections.length" class="text-sm text-slate-500">Guru belum menayangkan materi.</p>
    <section v-for="s in sections" :key="s.id" class="bg-white rounded-2xl border border-slate-200 mb-4">
      <h2 class="font-semibold text-navy px-4 py-3 border-b border-slate-100">{{ s.title }}</h2>
      <Link v-for="l in s.lessons" :key="l.id" :href="`/belajar/materi/${l.id}`" class="flex items-center gap-3 px-4 py-2.5 text-sm border-b border-slate-50 hover:bg-slate-50">
        <span class="w-5 h-5 rounded-full border flex items-center justify-center text-[11px]" :class="l.done ? 'bg-accent border-accent text-navy' : 'border-slate-300 text-transparent'">&#10003;</span>
        <span class="flex-1">{{ l.title }}</span>
        <span class="text-[10px] uppercase bg-slate-100 rounded px-1.5 py-0.5">{{ typeLabel[l.type] }}</span>
        <span v-if="l.est_minutes" class="text-xs text-slate-400">{{ l.est_minutes }} mnt</span>
      </Link>
    </section>
  </LmsShell>
</template>
