<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import LmsShell from '@/Components/Lms/LmsShell.vue'
const props = defineProps({ course: Object, quiz: Object, attempts: Array, leaderboard: Array })
const f = useForm({ access_code: '' })
function start() { f.post(`/belajar/quiz/${props.quiz.id}/mulai`) }
const st = { in_progress: 'Berjalan', submitted: 'Dikumpulkan', graded: 'Dinilai' }
</script>

<template>
  <LmsShell :title="quiz.title" :back="`/belajar/mapel/${course.id}`" :backLabel="course.title">
    <p v-if="quiz.description" class="text-sm text-slate-600 whitespace-pre-line mb-3">{{ quiz.description }}</p>
    <ul class="text-sm text-slate-600 mb-4 space-y-0.5">
      <li>Dibuka: {{ quiz.opens_at.replace('T', ' ') }} WIB — ditutup: {{ quiz.closes_at.replace('T', ' ') }} WIB</li>
      <li>{{ quiz.questions_count }} soal<span v-if="quiz.duration_min"> · durasi {{ quiz.duration_min }} menit</span> · maks. {{ quiz.max_attempts }}× percobaan<span v-if="quiz.pass_score"> · nilai lulus {{ quiz.pass_score }}</span></li>
    </ul>
    <div class="bg-white rounded-2xl border border-slate-200 p-4 mb-4">
      <template v-if="quiz.is_open">
        <input v-if="quiz.needs_code" v-model="f.access_code" placeholder="Kode quiz" autocomplete="off" class="block w-full sm:w-64 rounded-xl border border-slate-300 px-3 py-2 text-sm mb-2" />
        <button class="rounded-xl bg-navy text-white text-sm font-semibold px-5 py-2" :disabled="f.processing" @click="start">Mulai / lanjutkan</button>
        <p v-for="(e, k) in f.errors" :key="k" class="text-red-600 text-xs mt-2">{{ e }}</p>
      </template>
      <p v-else class="text-sm text-slate-500">Quiz tidak sedang dibuka.</p>
    </div>
    <h2 class="font-semibold text-navy mb-2">Riwayat</h2>
    <p v-if="!attempts.length" class="text-sm text-slate-500">Belum ada percobaan.</p>
    <Link v-for="a in attempts" :key="a.id" :href="a.status === 'in_progress' ? `/belajar/pengerjaan/${a.id}` : `/belajar/pengerjaan/${a.id}/hasil`" class="flex gap-3 bg-white rounded-xl border border-slate-200 px-3 py-2 mb-2 text-sm">
      <span class="flex-1">Percobaan {{ a.no }}</span><span class="text-slate-500">{{ st[a.status] }}</span><b>{{ a.score ?? '' }}</b>
    </Link>
    <div v-if="leaderboard" class="mt-6">
      <h2 class="font-semibold text-navy mb-2">Papan peringkat</h2>
      <ol class="text-sm bg-white rounded-2xl border border-slate-200 divide-y divide-slate-100">
        <li v-for="(b, i) in leaderboard" :key="i" class="flex px-3 py-1.5"><span class="w-6 text-slate-400">{{ i + 1 }}</span><span class="flex-1">{{ b.name }}</span><b>{{ b.best }}</b></li>
        <li v-if="!leaderboard.length" class="px-3 py-2 text-slate-500">Belum ada.</li>
      </ol>
    </div>
  </LmsShell>
</template>
