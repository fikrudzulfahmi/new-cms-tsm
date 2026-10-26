<script setup>
import { reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import LmsShell from '@/Components/Lms/LmsShell.vue'
import QuestionReview from '@/Components/Lms/QuestionReview.vue'
const props = defineProps({ quiz: Object, attempt: Object, items: Array })
const g = reactive(Object.fromEntries(props.items.map(i => [i.id, { points: i.awarded ?? 0, feedback: i.feedback ?? '' }])))
function grade(i) { router.post(`/ruang-guru/pengerjaan/${props.attempt.id}/soal/${i.id}`, g[i.id], { preserveScroll: true }) }
</script>

<template>
  <LmsShell :title="`${attempt.student} — ${quiz.title}`" :back="`/ruang-guru/quiz/${quiz.id}`" backLabel="Quiz">
    <p class="text-sm mb-3">Nilai: <b>{{ attempt.score ?? '-' }}</b> · pindah tab: {{ attempt.tab_switch_count }}×</p>
    <QuestionReview v-for="(it, n) in items" :key="it.id" :item="it" :index="n">
      <div v-if="it.type === 'uraian' || it.is_correct !== true" class="flex flex-wrap items-center gap-2 mt-3 text-sm">
        <input v-model.number="g[it.id].points" type="number" step="0.5" min="0" :max="it.max" class="w-20 rounded border border-slate-300 px-2 py-1" />
        <input v-model="g[it.id].feedback" maxlength="500" placeholder="Catatan (opsional)" class="flex-1 min-w-[8rem] rounded border border-slate-300 px-2 py-1" />
        <button class="rounded-lg bg-navy text-white px-3 py-1" @click="grade(it)">Beri nilai</button>
      </div>
    </QuestionReview>
  </LmsShell>
</template>
