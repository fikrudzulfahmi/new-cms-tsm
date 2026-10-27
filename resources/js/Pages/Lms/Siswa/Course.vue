<script setup>
import { Link } from '@inertiajs/vue3'
import LmsShell from '@/Components/Lms/LmsShell.vue'
defineProps({ course: Object, sections: Array, quizzes: Array, assignments: Array })
const typeLabel = { reading: 'Bacaan', video: 'Video', file: 'Berkas' }
</script>

<template>
  <LmsShell :title="course.title" back="/belajar" backLabel="Mapel saya">
    <p v-if="course.description" class="text-sm text-slate-600 -mt-2 mb-4 whitespace-pre-line">{{ course.description }}</p>
    <p v-if="!sections.length" class="text-sm text-slate-500">Guru belum menayangkan materi.</p>
    <section v-if="assignments?.length" class="bg-white rounded-2xl border border-slate-200 mb-4">
      <h2 class="font-semibold text-navy px-4 py-3 border-b border-slate-100">Tugas</h2>
      <Link v-for="a in assignments" :key="a.id" :href="`/belajar/tugas/${a.id}`" class="flex items-center gap-3 px-4 py-2.5 text-sm border-b border-slate-50 hover:bg-slate-50">
        <span class="flex-1">{{ a.title }}</span>
        <span v-if="a.graded" class="text-xs text-emerald-700">Dinilai</span>
        <span v-else-if="a.done" class="text-xs text-slate-500">Dikumpulkan</span>
        <span v-else class="text-xs" :class="a.past_due ? 'text-red-600' : 'text-slate-400'">{{ a.past_due ? 'Terlambat' : 'Belum' }}</span>
        <span v-if="a.due_at" class="text-[11px] text-slate-400">{{ a.due_at.replace('T', ' ') }}</span>
      </Link>
    </section>
    <section v-if="quizzes.length" class="bg-white rounded-2xl border border-slate-200 mb-4">
      <h2 class="font-semibold text-navy px-4 py-3 border-b border-slate-100">Quiz</h2>
      <Link v-for="z in quizzes" :key="z.id" :href="`/belajar/quiz/${z.id}`" class="flex items-center gap-3 px-4 py-2.5 text-sm border-b border-slate-50 hover:bg-slate-50">
        <span class="flex-1">{{ z.title }}</span>
        <span class="text-xs" :class="z.is_open ? 'text-emerald-700' : 'text-slate-400'">{{ z.is_open ? 'Dibuka' : 'Tutup' }}</span>
      </Link>
    </section>
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
