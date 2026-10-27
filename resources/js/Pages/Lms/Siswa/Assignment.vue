<script setup>
import { ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import LmsShell from '@/Components/Lms/LmsShell.vue'
const props = defineProps({ course: Object, assignment: Object, criteria: Array, submission: Object })
const mode = ref(props.submission?.type || 'text')
const f = useForm({ type: mode.value, text: props.submission?.text || '', link: props.submission?.link || '', file: null })
const base = `/belajar/tugas/${props.assignment.id}`
function pick(m) { mode.value = m; f.type = m; f.clearErrors() }
function send() {
  f.post(base, { forceFormData: true, preserveScroll: true })
}
</script>

<template>
  <LmsShell :title="assignment.title" :back="`/belajar/mapel/${course.id}`" backLabel="Mapel">
    <div class="bg-white rounded-2xl border border-slate-200 p-4 mb-4">
      <p class="text-xs text-slate-500">
        {{ assignment.due_at ? 'Batas ' + assignment.due_at.replace('T', ' ') : 'Tanpa batas akhir' }} · nilai maksimal {{ assignment.max_score }}
        <span v-if="assignment.past_due && !assignment.allow_late" class="text-red-600">· batas waktu lewat</span>
      </p>
      <p v-if="assignment.instructions" class="text-sm whitespace-pre-line mt-2">{{ assignment.instructions }}</p>
      <a v-if="assignment.attachment_name" :href="`${base}/lampiran`" class="inline-block text-xs text-navy underline mt-2">{{ assignment.attachment_name }}</a>
      <p v-if="criteria.length" class="text-xs text-slate-500 mt-2">Dinilai dari: {{ criteria.map(c => `${c.name} (×${c.weight})`).join(', ') }}</p>
    </div>

    <div v-if="submission?.graded" class="bg-emerald-50 border border-emerald-200 rounded-2xl p-4 mb-4">
      <p class="text-sm font-semibold text-emerald-800">Sudah dinilai: {{ submission.score }} / {{ assignment.max_score }}</p>
      <p v-if="submission.feedback" class="text-sm whitespace-pre-line mt-1">{{ submission.feedback }}</p>
      <ul v-if="submission.rubric_scores" class="text-xs text-slate-600 mt-2 list-disc list-inside">
        <li v-for="c in criteria" :key="c.id">{{ c.name }}: {{ submission.rubric_scores[c.id] ?? '—' }}</li>
      </ul>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 p-4">
      <h2 class="font-semibold text-navy mb-2">{{ submission ? 'Pengumpulan Anda' : 'Kumpulkan tugas' }}</h2>
      <p v-if="submission" class="text-xs text-slate-500 mb-2">
        Dikumpulkan {{ submission.submitted_at?.replace('T', ' ') }} <span v-if="submission.is_late" class="text-amber-700">· terlambat</span>
        <a v-if="submission.file_name" :href="`/belajar/tugas-berkas/${submission.file_id}`" class="text-navy underline ml-2">{{ submission.file_name }}</a>
      </p>
      <p v-if="submission?.graded" class="text-xs text-slate-500">Tugas sudah dinilai, pengumpulan dikunci.</p>

      <div v-else-if="!assignment.accepts" class="text-sm text-red-600">Batas waktu sudah lewat dan guru tidak menerima pengumpulan terlambat.</div>

      <template v-else>
        <div class="flex gap-2 mb-3">
          <button v-for="m in [['text','Teks'],['link','Tautan'],['file','Berkas']]" :key="m[0]" class="rounded-xl text-sm px-3 py-1.5 border"
            :class="mode === m[0] ? 'bg-navy text-white border-navy' : 'border-slate-300 text-slate-600'" @click="pick(m[0])">{{ m[1] }}</button>
        </div>

        <textarea v-if="mode === 'text'" v-model="f.text" rows="6" maxlength="20000" placeholder="Tulis jawaban Anda" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"></textarea>
        <div v-else-if="mode === 'link'" class="space-y-1">
          <input v-model="f.link" placeholder="https://youtu.be/... atau https://drive.google.com/..." class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" />
          <p class="text-xs text-slate-500">Video YouTube/Google Drive bisa diputar langsung oleh guru.</p>
        </div>
        <div v-else>
          <input type="file" class="block w-full text-sm" @input="f.file = $event.target.files[0]" />
          <p class="text-xs text-slate-500 mt-1">JPG, PNG, WEBP, PDF, DOCX, atau MP4 — maks 5 MB. Foto otomatis diperkecil.</p>
        </div>

        <p v-for="(e, k) in f.errors" :key="k" class="text-red-600 text-xs mt-2">{{ e }}</p>
        <button class="mt-3 rounded-xl bg-navy text-white text-sm font-semibold px-4 py-2" :disabled="f.processing" @click="send">
          {{ submission ? 'Kirim ulang' : 'Kumpulkan' }}
        </button>
      </template>
    </div>
  </LmsShell>
</template>
