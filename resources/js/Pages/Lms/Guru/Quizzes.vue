<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import LmsShell from '@/Components/Lms/LmsShell.vue'
const props = defineProps({ course: Object, quizzes: Array })
const f = useForm({ title: '', opens_at: '', closes_at: '', duration_min: 30, max_attempts: 1, show_result: 'after_submit', shuffle_questions: true, shuffle_options: true, is_published: false })
function create() { f.post(`/ruang-guru/mapel/${props.course.id}/quiz`) }
</script>

<template>
  <LmsShell :title="`Quiz — ${course.title}`" :back="`/ruang-guru/mapel/${course.id}`" backLabel="Mapel">
    <Link :href="`/ruang-guru/mapel/${course.id}/soal`" class="inline-block rounded-xl border border-slate-300 text-sm px-4 py-2 mb-4">Bank soal</Link>
    <div class="bg-white rounded-2xl border border-slate-200 p-4 mb-5 grid sm:grid-cols-2 gap-2">
      <input v-model="f.title" maxlength="150" placeholder="Judul quiz baru" class="sm:col-span-2 rounded-xl border border-slate-300 px-3 py-2 text-sm" />
      <label class="text-xs text-slate-500">Buka (WIB)<input v-model="f.opens_at" type="datetime-local" class="block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" /></label>
      <label class="text-xs text-slate-500">Tutup (WIB)<input v-model="f.closes_at" type="datetime-local" class="block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" /></label>
      <label class="text-xs text-slate-500">Durasi (menit)<input v-model="f.duration_min" type="number" min="1" class="block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" /></label>
      <label class="text-xs text-slate-500">Maks. percobaan<input v-model="f.max_attempts" type="number" min="1" max="10" class="block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" /></label>
      <p v-for="(e, k) in f.errors" :key="k" class="text-red-600 text-xs sm:col-span-2">{{ e }}</p>
      <button class="sm:col-span-2 rounded-xl bg-navy text-white text-sm font-semibold px-4 py-2" :disabled="f.processing" @click="create">Buat quiz</button>
    </div>
    <p v-if="!quizzes.length" class="text-sm text-slate-500">Belum ada quiz.</p>
    <Link v-for="q in quizzes" :key="q.id" :href="`/ruang-guru/quiz/${q.id}`" class="block bg-white rounded-2xl border border-slate-200 p-3 mb-2 hover:bg-slate-50">
      <div class="flex items-center gap-2"><b class="text-navy flex-1">{{ q.title }}</b>
        <span class="text-[10px] uppercase rounded px-1.5 py-0.5" :class="q.is_published ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100'">{{ q.is_published ? 'Tayang' : 'Draf' }}</span></div>
      <p class="text-xs text-slate-500">{{ q.opens_at.replace('T', ' ') }} &rarr; {{ q.closes_at.replace('T', ' ') }} · {{ q.questions_count }} soal · {{ q.attempts_count }} pengerjaan</p>
    </Link>
  </LmsShell>
</template>
