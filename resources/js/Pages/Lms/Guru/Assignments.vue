<script setup>
import { confirmDialog } from '@/dialog'
import { ref } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import LmsShell from '@/Components/Lms/LmsShell.vue'
const props = defineProps({ course: Object, assignments: Array, sections: Array })
const show = ref(false)
const editId = ref(null)
const blank = () => ({ title: '', instructions: '', due_at: '', allow_late: true, max_score: 100, is_published: false, section_id: '', criteria: [] })
const form = useForm(blank())
function open(a = null) {
  editId.value = a?.id ?? null
  form.clearErrors()
  Object.assign(form, a ? {
    title: a.title, instructions: a.instructions || '', due_at: a.due_at || '', allow_late: a.allow_late,
    max_score: a.max_score, is_published: a.is_published, section_id: a.section_id || '',
    criteria: (a.criteria || []).map(c => ({ name: c.name, weight: c.weight })),
  } : blank())
  form.brief = null
  show.value = true
}
function addCriterion() { if (form.criteria.length < 10) form.criteria.push({ name: '', weight: 1 }) }
function save() {
  const opt = { forceFormData: true, preserveScroll: true, onSuccess: () => (show.value = false) }
  editId.value ? form.post(`/ruang-guru/tugas/${editId.value}`, { ...opt, _method: 'put' }) : form.post(`/ruang-guru/mapel/${props.course.id}/tugas`, opt)
}
async function del(a) { if (await confirmDialog(`Hapus tugas "${a.title}" beserta semua pengumpulan?`)) router.delete(`/ruang-guru/tugas/${a.id}`, { preserveScroll: true }) }
function toggle(a) { router.put(`/ruang-guru/tugas/${a.id}`, { ...a, is_published: !a.is_published }, { preserveScroll: true }) }
</script>

<template>
  <LmsShell :title="`Tugas — ${course.title}`" :back="`/ruang-guru/mapel/${course.id}`" backLabel="Mapel">
    <div class="flex flex-wrap gap-2 mb-4">
      <button class="rounded-xl bg-navy text-white text-sm font-semibold px-4 py-2" @click="open()">Tugas baru</button>
      <Link :href="`/ruang-guru/mapel/${course.id}/buku-nilai`" class="rounded-xl border border-slate-300 text-sm px-4 py-2">Buku nilai</Link>
    </div>

    <div v-if="show" class="bg-white rounded-2xl border border-slate-200 p-4 mb-5 space-y-2">
      <input v-model="form.title" maxlength="150" placeholder="Judul tugas" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" />
      <textarea v-model="form.instructions" rows="4" placeholder="Instruksi / soal tugas" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"></textarea>
      <div class="grid sm:grid-cols-3 gap-2">
        <label class="text-xs text-slate-500">Batas akhir (WIB)<input v-model="form.due_at" type="datetime-local" class="block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" /></label>
        <label class="text-xs text-slate-500">Nilai maksimal<input v-model="form.max_score" type="number" min="1" max="1000" class="block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" /></label>
        <label class="text-xs text-slate-500">Bab (opsional)
          <select v-model="form.section_id" class="block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
            <option value="">—</option>
            <option v-for="s in sections" :key="s.id" :value="s.id">{{ s.title }}</option>
          </select>
        </label>
      </div>
      <label class="flex items-center gap-2 text-sm"><input v-model="form.allow_late" type="checkbox" /> Terima pengumpulan terlambat</label>
      <label class="flex items-center gap-2 text-sm"><input v-model="form.is_published" type="checkbox" /> Tayangkan ke siswa</label>
      <label class="block text-xs text-slate-500">Lampiran soal (PDF/DOCX/gambar, maks 5 MB)
        <input type="file" class="block w-full text-sm mt-1" @input="form.brief = $event.target.files[0]" />
      </label>

      <div>
        <div class="flex items-center justify-between mb-1">
          <span class="text-xs font-semibold text-slate-600">Rubrik penilaian (opsional)</span>
          <button class="text-xs text-navy" @click="addCriterion">+ Kriteria</button>
        </div>
        <div v-for="(c, i) in form.criteria" :key="i" class="flex gap-2 mb-1">
          <input v-model="c.name" placeholder="Nama kriteria" maxlength="120" class="flex-1 rounded-xl border border-slate-300 px-3 py-1.5 text-sm" />
          <input v-model="c.weight" type="number" min="1" max="100" class="w-20 rounded-xl border border-slate-300 px-2 py-1.5 text-sm" />
          <button class="text-xs text-red-600" @click="form.criteria.splice(i, 1)">Hapus</button>
        </div>
      </div>

      <p v-for="(e, k) in form.errors" :key="k" class="text-red-600 text-xs">{{ e }}</p>
      <div class="flex gap-2">
        <button class="rounded-xl bg-navy text-white text-sm font-semibold px-4 py-2" :disabled="form.processing" @click="save">Simpan</button>
        <button class="text-sm text-slate-500 px-3" @click="show = false">Batal</button>
      </div>
    </div>

    <p v-if="!assignments.length" class="text-sm text-slate-500">Belum ada tugas.</p>
    <div v-for="a in assignments" :key="a.id" class="bg-white rounded-2xl border border-slate-200 p-3 mb-2">
      <div class="flex flex-wrap items-center gap-2">
        <Link :href="`/ruang-guru/tugas/${a.id}`" class="font-semibold text-navy flex-1 min-w-[10rem] hover:underline">{{ a.title }}</Link>
        <span class="text-[10px] uppercase rounded px-1.5 py-0.5" :class="a.is_published ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'">{{ a.is_published ? 'Tayang' : 'Draf' }}</span>
        <button class="text-xs text-slate-500" @click="toggle(a)">{{ a.is_published ? 'Sembunyikan' : 'Tayangkan' }}</button>
        <button class="text-xs text-slate-500" @click="open(a)">Ubah</button>
        <button class="text-xs text-red-600" @click="del(a)">Hapus</button>
      </div>
      <p v-if="a.attachment_name" class="text-xs text-slate-500 mt-1">Lampiran: {{ a.attachment_name }}</p>
      <p class="text-xs text-slate-500 mt-1">
        {{ a.due_at ? a.due_at.replace('T', ' ') : 'Tanpa batas akhir' }} · maks {{ a.max_score }} ·
        {{ a.submissions_count }} dikumpulkan · {{ a.graded_count }} dinilai
        <span v-if="!a.allow_late"> · tidak menerima terlambat</span>
      </p>
    </div>
  </LmsShell>
</template>
