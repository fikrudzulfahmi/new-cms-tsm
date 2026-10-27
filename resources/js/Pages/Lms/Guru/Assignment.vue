<script setup>
import { confirmDialog } from '@/dialog'
import { computed, reactive } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import LmsShell from '@/Components/Lms/LmsShell.vue'
const props = defineProps({ course: Object, assignment: Object, criteria: Array, students: Array })
const base = `/ruang-guru/tugas/${props.assignment.id}`
const pick = reactive({ id: null })
const current = computed(() => props.students.find(s => s.submission?.id === pick.id))
const g = useForm({ score: null, feedback: '', rubric_scores: {} })
function openGrade(s) {
  pick.id = s.submission.id
  g.clearErrors()
  g.score = s.submission.score
  g.feedback = s.submission.feedback || ''
  g.rubric_scores = { ...(s.submission.rubric_scores || {}) }
}
function saveGrade() {
  g.post(`/ruang-guru/pengumpulan/${pick.id}/nilai`, { preserveScroll: true, onSuccess: () => (pick.id = null) })
}
const weighted = computed(() => {
  if (!props.criteria.length) return null
  let num = 0, den = 0
  for (const c of props.criteria) {
    const v = Number(g.rubric_scores[c.id])
    if (g.rubric_scores[c.id] === undefined || g.rubric_scores[c.id] === null || g.rubric_scores[c.id] === '' || Number.isNaN(v)) continue
    num += v * Math.max(1, c.weight); den += Math.max(1, c.weight)
  }
  return den ? Math.round(num / den * 100) / 100 : null
})
function useWeighted() { if (weighted.value !== null) g.score = weighted.value }
async function delFile(s) { if (await confirmDialog('Hapus lampiran siswa ini? Nilai dan feedback tetap tersimpan.')) router.delete(`/ruang-guru/pengumpulan/${s.submission.id}/berkas`, { preserveScroll: true }) }
async function delAll() { if (await confirmDialog('Hapus semua lampiran tugas ini? Nilai tetap tersimpan.')) router.delete(`${base}/semua-berkas`, { preserveScroll: true }) }
</script>

<template>
  <LmsShell :title="assignment.title" :back="`/ruang-guru/mapel/${course.id}/tugas`" backLabel="Daftar tugas">
    <div class="bg-white rounded-2xl border border-slate-200 p-4 mb-4">
      <p class="text-xs text-slate-500">
        {{ assignment.due_at ? 'Batas ' + assignment.due_at.replace('T', ' ') : 'Tanpa batas akhir' }} · maks {{ assignment.max_score }}
        · {{ assignment.is_published ? 'Tayang' : 'Draf' }} <span v-if="!assignment.allow_late">· tidak menerima terlambat</span>
      </p>
      <p v-if="assignment.instructions" class="text-sm whitespace-pre-line mt-2">{{ assignment.instructions }}</p>
      <p v-if="assignment.attachment_name" class="text-xs mt-2 text-slate-500">Lampiran soal:
        <a :href="`${base}/lampiran`" class="text-navy underline">{{ assignment.attachment_name }}</a></p>
      <p v-if="criteria.length" class="text-xs mt-2 text-slate-500">Rubrik: {{ criteria.map(c => `${c.name} (×${c.weight})`).join(', ') }}</p>
      <button class="mt-3 text-xs text-red-600" @click="delAll">Hapus semua lampiran siswa</button>
    </div>

    <div v-if="current" class="bg-white rounded-2xl border border-slate-200 p-4 mb-4 space-y-2">
      <div class="flex items-center justify-between">
        <h2 class="font-semibold text-navy">{{ students.find(s => s.submission?.id === pick.id)?.name }}</h2>
        <button class="text-xs text-slate-500" @click="pick.id = null">Tutup</button>
      </div>
      <div class="text-sm bg-slate-50 rounded-xl p-3">
        <p v-if="current.submission.type === 'text'" class="whitespace-pre-line">{{ current.submission.text }}</p>
        <a v-else-if="current.submission.type === 'link'" :href="current.submission.link" target="_blank" rel="noopener noreferrer" class="text-navy underline break-all">{{ current.submission.link }}</a>
        <span v-else>
          <a :href="`/ruang-guru/berkas-pengumpulan/${current.submission.file_id}`" class="text-navy underline">{{ current.submission.file_name }}</a>
          <button class="text-xs text-red-600 ml-2" @click="delFile(current)">Hapus berkas</button>
        </span>
        <p class="text-[11px] text-slate-500 mt-1">{{ current.submission.submitted_at?.replace('T', ' ') }} <span v-if="current.submission.is_late" class="text-amber-700">· terlambat</span></p>
      </div>

      <div v-if="criteria.length" class="grid sm:grid-cols-2 gap-2">
        <label v-for="c in criteria" :key="c.id" class="text-xs text-slate-500">{{ c.name }} (bobot {{ c.weight }})
          <input v-model="g.rubric_scores[c.id]" type="number" min="0" :max="assignment.max_score" class="block w-full rounded-xl border border-slate-300 px-3 py-1.5 text-sm" />
        </label>
        <p class="text-xs text-slate-500 sm:col-span-2">Nilai rubrik: <b>{{ weighted ?? '—' }}</b>
          <button class="text-navy ml-2" @click="useWeighted">Pakai sebagai nilai</button></p>
      </div>

      <div class="grid sm:grid-cols-[8rem_1fr] gap-2">
        <label class="text-xs text-slate-500">Nilai (0–{{ assignment.max_score }})
          <input v-model="g.score" type="number" min="0" :max="assignment.max_score" step="0.01" class="block w-full rounded-xl border border-slate-300 px-3 py-1.5 text-sm" /></label>
        <label class="text-xs text-slate-500">Feedback
          <textarea v-model="g.feedback" rows="2" class="block w-full rounded-xl border border-slate-300 px-3 py-1.5 text-sm"></textarea></label>
      </div>
      <p v-for="(e, k) in g.errors" :key="k" class="text-red-600 text-xs">{{ e }}</p>
      <button class="rounded-xl bg-navy text-white text-sm font-semibold px-4 py-2" :disabled="g.processing" @click="saveGrade">Simpan nilai</button>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-500 text-xs">
          <tr><th class="text-left px-3 py-2">Nama</th><th class="text-left px-3 py-2">NIS</th><th class="text-left px-3 py-2">Pengumpulan</th><th class="text-right px-3 py-2">Nilai</th><th></th></tr>
        </thead>
        <tbody>
          <tr v-for="s in students" :key="s.id" class="border-t border-slate-100">
            <td class="px-3 py-2">{{ s.name }}</td>
            <td class="px-3 py-2 text-slate-500">{{ s.nis }}</td>
            <td class="px-3 py-2 text-xs">
              <span v-if="!s.submission" class="text-slate-400">Belum</span>
              <span v-else>{{ s.submission.submitted_at?.replace('T', ' ') }} <span v-if="s.submission.is_late" class="text-amber-700">· terlambat</span></span>
            </td>
            <td class="px-3 py-2 text-right">{{ s.submission?.score ?? '—' }}</td>
            <td class="px-3 py-2 text-right">
              <button v-if="s.submission" class="text-xs text-navy" @click="openGrade(s)">{{ s.submission.graded_at ? 'Ubah nilai' : 'Nilai' }}</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </LmsShell>
</template>
