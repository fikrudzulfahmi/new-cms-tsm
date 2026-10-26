<script setup>
import { ref, reactive } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import LmsShell from '@/Components/Lms/LmsShell.vue'
const props = defineProps({ course: Object, sections: Array })

const secForm = useForm({ title: '' })
function addSection() { secForm.post(`/ruang-guru/mapel/${props.course.id}/bab`, { preserveScroll: true, onSuccess: () => secForm.reset() }) }
function renameSection(s) {
  const t = window.prompt('Nama bab', s.title)
  if (t && t.trim()) router.put(`/ruang-guru/bab/${s.id}`, { title: t, is_published: s.is_published }, { preserveScroll: true })
}
function togglePub(s) { router.put(`/ruang-guru/bab/${s.id}`, { title: s.title, is_published: !s.is_published }, { preserveScroll: true }) }
function delSection(s) { if (window.confirm(`Hapus bab "${s.title}" beserta materinya?`)) router.delete(`/ruang-guru/bab/${s.id}`, { preserveScroll: true }) }
function move(s, dir) { router.post(`/ruang-guru/bab/${s.id}/geser`, { dir }, { preserveScroll: true }) }

const editing = ref(null)   // { sectionId, lesson|null }
const form = useForm({ type: 'reading', title: '', content: '', video_url: '', est_minutes: '', is_published: true, file: null })
function openLesson(sectionId, l = null) {
  editing.value = { sectionId, id: l?.id ?? null }
  form.clearErrors()
  form.type = l?.type ?? 'reading'; form.title = l?.title ?? ''; form.content = l?.content ?? ''
  form.video_url = ''; form.est_minutes = l?.est_minutes ?? ''; form.is_published = l?.is_published ?? true; form.file = null
}
function saveLesson() {
  const url = editing.value.id ? `/ruang-guru/materi/${editing.value.id}` : `/ruang-guru/bab/${editing.value.sectionId}/materi`
  form.post(url, { forceFormData: true, preserveScroll: true, onSuccess: () => (editing.value = null) })
}
function delLesson(l) { if (window.confirm(`Hapus materi "${l.title}"?`)) router.delete(`/ruang-guru/materi/${l.id}`, { preserveScroll: true }) }

const prog = ref(null)
async function showProgress() {
  const r = await fetch(`/ruang-guru/mapel/${props.course.id}/progres`, { headers: { Accept: 'application/json' } })
  prog.value = await r.json()
}
const typeLabel = { reading: 'Bacaan', video: 'Video', file: 'Berkas' }
</script>

<template>
  <LmsShell :title="course.title" back="/ruang-guru" backLabel="Mapel saya">
    <p class="text-xs text-slate-500 -mt-3 mb-4">Rombel: {{ course.classes.join(', ') }}</p>

    <div class="flex gap-2 mb-5">
      <input v-model="secForm.title" placeholder="Nama bab baru" maxlength="150" class="flex-1 rounded-xl border border-slate-300 px-3 py-2 text-sm" @keyup.enter="addSection" />
      <button class="rounded-xl bg-navy text-white text-sm font-semibold px-4" :disabled="secForm.processing" @click="addSection">Tambah bab</button>
    </div>
    <p v-if="secForm.errors.title" class="text-red-600 text-xs -mt-4 mb-3">{{ secForm.errors.title }}</p>

    <p v-if="!sections.length" class="text-sm text-slate-500">Belum ada bab.</p>

    <section v-for="(s, i) in sections" :key="s.id" class="bg-white rounded-2xl border border-slate-200 mb-4">
      <div class="flex flex-wrap items-center gap-2 px-4 py-3 border-b border-slate-100">
        <h2 class="font-semibold text-navy flex-1 min-w-[10rem]">{{ s.title }}</h2>
        <span class="text-xs px-2 py-0.5 rounded" :class="s.is_published ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'">{{ s.is_published ? 'Tayang' : 'Draf' }}</span>
        <button class="text-xs text-slate-500 hover:text-navy" :disabled="i===0" @click="move(s,'up')">Naik</button>
        <button class="text-xs text-slate-500 hover:text-navy" :disabled="i===sections.length-1" @click="move(s,'down')">Turun</button>
        <button class="text-xs text-slate-500 hover:text-navy" @click="renameSection(s)">Ubah</button>
        <button class="text-xs text-navy font-semibold" @click="togglePub(s)">{{ s.is_published ? 'Sembunyikan' : 'Tayangkan' }}</button>
        <button class="text-xs text-red-600" @click="delSection(s)">Hapus</button>
      </div>
      <ul>
        <li v-for="l in s.lessons" :key="l.id" class="flex items-center gap-3 px-4 py-2 border-b border-slate-50 text-sm">
          <span class="text-[10px] uppercase tracking-wide bg-slate-100 rounded px-1.5 py-0.5">{{ typeLabel[l.type] }}</span>
          <span class="flex-1">{{ l.title }}</span>
          <span v-if="!l.is_published" class="text-xs text-slate-400">draf</span>
          <button class="text-xs text-navy" @click="openLesson(s.id, l)">Ubah</button>
          <button class="text-xs text-red-600" @click="delLesson(l)">Hapus</button>
        </li>
      </ul>
      <div class="px-4 py-3"><button class="text-sm font-semibold text-navy" @click="openLesson(s.id)">+ Tambah materi</button></div>
    </section>

    <button v-if="sections.length" class="text-sm text-navy underline" @click="showProgress">Lihat progres siswa</button>
    <div v-if="prog" class="mt-3 bg-white rounded-2xl border p-4 text-sm">
      <div class="mb-2 text-slate-500">Total materi tayang: {{ prog.total }}</div>
      <div v-for="(st, i) in prog.students" :key="i" class="flex justify-between border-b border-slate-50 py-1"><span>{{ st.name }}</span><span>{{ st.done }}/{{ prog.total }}</span></div>
    </div>

    <div v-if="editing" class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4" @click.self="editing = null">
      <form class="bg-white rounded-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto p-5 space-y-3" @submit.prevent="saveLesson">
        <h3 class="font-display font-bold text-navy">{{ editing.id ? 'Ubah materi' : 'Materi baru' }}</h3>
        <select v-model="form.type" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
          <option value="reading">Bacaan</option><option value="video">Video (YouTube / Google Drive)</option><option value="file">Berkas (PDF/JPG/PNG, maks 5 MB)</option>
        </select>
        <input v-model="form.title" placeholder="Judul" maxlength="150" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" />
        <p v-if="form.errors.title" class="text-red-600 text-xs">{{ form.errors.title }}</p>
        <textarea v-model="form.content" rows="6" placeholder="Isi / penjelasan (teks biasa)" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"></textarea>
        <template v-if="form.type==='video'">
          <input v-model="form.video_url" placeholder="https://youtu.be/... atau tautan Google Drive" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" />
          <p v-if="editing.id" class="text-xs text-slate-500">Kosongkan tidak diperbolehkan; tempel ulang tautan saat mengubah.</p>
        </template>
        <template v-if="form.type==='file'">
          <input type="file" accept=".pdf,.jpg,.jpeg,.png" class="text-sm" @input="form.file = $event.target.files[0]" />
          <p v-if="form.errors.file" class="text-red-600 text-xs">{{ form.errors.file }}</p>
        </template>
        <div class="flex items-center gap-3">
          <input v-model="form.est_minutes" type="number" min="1" max="600" placeholder="Menit" class="w-24 rounded-xl border border-slate-300 px-3 py-2 text-sm" />
          <label class="text-sm flex items-center gap-2"><input v-model="form.is_published" type="checkbox" /> Tayang</label>
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <button type="button" class="text-sm text-slate-500 px-3" @click="editing = null">Batal</button>
          <button class="rounded-xl bg-navy text-white text-sm font-semibold px-4 py-2" :disabled="form.processing">Simpan</button>
        </div>
      </form>
    </div>
  </LmsShell>
</template>
