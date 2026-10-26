<script setup>
import LmsShell from '@/Components/Lms/LmsShell.vue'
import QuestionReview from '@/Components/Lms/QuestionReview.vue'
defineProps({ quiz: Object, attempt: Object, can_see: Boolean, items: Array })
</script>

<template>
  <LmsShell :title="quiz.title" :back="`/belajar/quiz/${quiz.id}`" backLabel="Kembali ke quiz">
    <div class="bg-white rounded-2xl border border-slate-200 p-5 mb-4 text-center">
      <template v-if="can_see">
        <p class="text-sm text-slate-500">Nilai</p>
        <p class="text-4xl font-bold text-navy">{{ attempt.score }}</p>
        <p v-if="quiz.pass_score != null" class="text-sm mt-1" :class="attempt.score >= quiz.pass_score ? 'text-emerald-700' : 'text-red-600'">{{ attempt.score >= quiz.pass_score ? 'Lulus' : 'Belum lulus' }}</p>
        <p v-if="attempt.status === 'submitted'" class="text-xs text-amber-700 mt-1">Soal uraian belum dinilai; nilai dapat berubah.</p>
      </template>
      <p v-else class="text-sm text-slate-600">Jawaban sudah dikumpulkan. Hasil belum ditampilkan oleh guru.</p>
    </div>
    <QuestionReview v-for="(it, n) in items" :key="it.id" :item="it" :index="n" />
  </LmsShell>
</template>
