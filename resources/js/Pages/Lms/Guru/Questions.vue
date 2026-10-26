<script setup>
import { ref, computed } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import LmsShell from '@/Components/Lms/LmsShell.vue'
const props = defineProps({ course: Object, types: Object, questions: Array, importResult: Object })
const base = `/ruang-guru/mapel/${props.course.id}`
const blank = () => ({ type: 'pg', body: '', explanation: '', points: 1, options: [{ body: '', is_correct: true }, { body: '', is_correct: false }, { body: '', is_correct: false }, { body: '', is_correct: false }] })
const editId = ref(null)
const show = ref(false)
const form = useForm(blank())
function setType() {
  const t = form.type
  if (t === 'benar_salah') form.options = [{ body: 'Benar', is_correct: true }, { body: 'Salah', is_correct: false }]
  else if (t === 'menjodohkan') form.options = [{ body: '', match_body: '' }, { body: '', match_body: '' }, { body: '', match_body: '' }]
  else if (t === 'isian') form.options = [{ body: '', is_correct: true }]
  else if (t === 'uraian') form.options = []
  else form.options = [{ body: '', is_correct: t === 'pg' }, { body: '', is_correct: false }, { body: '', is_correct: false }, { body: '', is_correct: false }]
}
function newQ() { editId.value = null; Object.assign(form, blank()); form.clearErrors(); show.value = true }
function edit(q) {
  editId.value = q.id; form.clearErrors(); form.type = q.type; form.body = q.body; form.explanation = q.explanation || ''; form.points = q.points
  form.options = q.options.map(o => ({ ...o })); show.value = true
}
function pickOne(i) { form.options.forEach((o, k) => (o.is_correct = k === i)) }
function addOpt() { if (form.options.length < 12) form.options.push(form.type === 'menjodohkan' ? { body: '', match_body: '' } : { body: '', is_correct: form.type === 'isian' }) }
function save() {
  const opt = { preserveScroll: true, onSuccess: () => (show.value = false) }
  editId.value ? form.put(`/ruang-guru/soal/${editId.value}`, opt) : form.post(`${base}/soal`, opt)
}
function del(q) { if (confirm('Hapus soal ini?')) router.delete(`/ruang-guru/soal/${q.id}`, { preserveScroll: true }) }
const imp = useForm({ file: null })
function preview() { imp.post(`${base}/soal/impor`, { forceFormData: true, preserveScroll: true }) }
function commit() { router.post(`${base}/soal/impor/simpan`, {}, { preserveScroll: true }) }
const err = computed(() => form.errors.options || form.errors.body || form.errors.points || form.errors.type)
</script>

<template>
  <LmsShell :title="`Bank soal — ${course.title}`" :back="`/ruang-guru/mapel/${course.id}`" backLabel="Mapel">
    <div class="flex flex-wrap gap-2 mb-4">
      <button class="rounded-xl bg-navy text-white text-sm font-semibold px-4 py-2" @click="newQ">+ Soal baru</button>
      <a :href="`/ruang-guru/mapel/${course.id}/quiz`" class="rounded-xl border border-slate-300 text-sm px-4 py-2" @click.prevent="router.get(`/ruang-guru/mapel/${course.id}/quiz`)">Ke daftar quiz</a>
    </div>

    <details class="bg-white rounded-2xl border border-slate-200 p-4 mb-4">
      <summary class="text-sm font-semibold text-navy cursor-pointer">Impor dari Word (.docx / .rtf)</summary>
      <div class="text-xs text-slate-600 mt-2 space-y-1">
        <p>Format per soal (nomor di awal, pilihan A., B., ...):</p>
        <pre class="bg-slate-50 rounded p-2 overflow-x-auto">1. Ibu kota Indonesia?
A. Bandung
B. Jakarta
Jawaban: B
Skor: 2
Pembahasan: ...</pre>
        <p>PG kompleks: <code>Jawaban: A,C</code> · Benar/Salah: <code>Jawaban: Benar</code> (tanpa pilihan) · Isian: <code>Jawaban: kunci1 | kunci2</code> · Menjodohkan: baris <code>Kiri =&gt; Kanan</code> · Uraian: tanpa baris Jawaban. Gambar belum didukung.</p>
      </div>
      <div class="flex flex-wrap items-center gap-2 mt-3">
        <input type="file" accept=".docx,.rtf,.txt" class="text-sm" @input="imp.file = $event.target.files[0]" />
        <button class="rounded-xl bg-navy text-white text-sm px-3 py-1.5 disabled:opacity-50" :disabled="!imp.file || imp.processing" @click="preview">Pratinjau</button>
      </div>
      <p v-if="imp.errors.file" class="text-red-600 text-xs mt-1">{{ imp.errors.file }}</p>
      <div v-if="importResult" class="mt-3 text-sm">
        <p><b>{{ importResult.count }}</b> soal terbaca.</p>
        <ul v-if="importResult.errors.length" class="text-xs text-red-600 list-disc pl-5"><li v-for="e in importResult.errors" :key="e">{{ e }}</li></ul>
        <ol class="text-xs text-slate-600 list-decimal pl-5 mt-2 max-h-48 overflow-y-auto"><li v-for="(it, i) in importResult.items" :key="i">[{{ types[it.type] }}] {{ it.body.slice(0, 90) }}</li></ol>
        <button v-if="importResult.count" class="mt-2 rounded-xl bg-accent text-navy font-semibold text-sm px-4 py-1.5" @click="commit">Simpan {{ importResult.count }} soal ke bank</button>
      </div>
    </details>

    <div v-if="show" class="bg-white rounded-2xl border border-slate-300 p-4 mb-4 space-y-3">
      <div class="grid sm:grid-cols-3 gap-2">
        <select v-model="form.type" class="rounded-xl border border-slate-300 px-3 py-2 text-sm" :disabled="!!editId" @change="setType">
          <option v-for="(l, k) in types" :key="k" :value="k">{{ l }}</option>
        </select>
        <input v-model="form.points" type="number" step="0.5" min="0.5" class="rounded-xl border border-slate-300 px-3 py-2 text-sm" placeholder="Skor" />
      </div>
      <textarea v-model="form.body" rows="3" maxlength="5000" placeholder="Teks soal" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"></textarea>

      <div v-if="form.type === 'benar_salah'" class="flex gap-4 text-sm">
        <label v-for="(o, i) in form.options" :key="i"><input type="radio" :checked="o.is_correct" @change="pickOne(i)" /> {{ o.body }}</label>
      </div>
      <div v-else-if="['pg','pg_kompleks'].includes(form.type)" class="space-y-2">
        <div v-for="(o, i) in form.options" :key="i" class="flex items-center gap-2">
          <input v-if="form.type === 'pg'" type="radio" :checked="o.is_correct" @change="pickOne(i)" />
          <input v-else type="checkbox" v-model="o.is_correct" />
          <input v-model="o.body" maxlength="500" :placeholder="`Pilihan ${i + 1}`" class="flex-1 rounded-xl border border-slate-300 px-3 py-1.5 text-sm" />
        </div>
        <button class="text-xs text-navy underline" @click="addOpt">+ pilihan</button>
      </div>
      <div v-else-if="form.type === 'menjodohkan'" class="space-y-2">
        <div v-for="(o, i) in form.options" :key="i" class="flex items-center gap-2">
          <input v-model="o.body" maxlength="500" placeholder="Kiri" class="flex-1 rounded-xl border border-slate-300 px-3 py-1.5 text-sm" />
          <span>&rarr;</span>
          <input v-model="o.match_body" maxlength="500" placeholder="Pasangan kanan" class="flex-1 rounded-xl border border-slate-300 px-3 py-1.5 text-sm" />
        </div>
        <button class="text-xs text-navy underline" @click="addOpt">+ pasangan</button>
      </div>
      <div v-else-if="form.type === 'isian'" class="space-y-2">
        <p class="text-xs text-slate-500">Jawaban yang diterima (tidak peka huruf besar/kecil):</p>
        <input v-for="(o, i) in form.options" :key="i" v-model="o.body" maxlength="500" class="block w-full rounded-xl border border-slate-300 px-3 py-1.5 text-sm" />
        <button class="text-xs text-navy underline" @click="addOpt">+ jawaban alternatif</button>
      </div>
      <p v-else class="text-xs text-slate-500">Uraian dinilai manual oleh guru.</p>

      <textarea v-model="form.explanation" rows="2" maxlength="3000" placeholder="Pembahasan (opsional, tampil setelah siswa selesai)" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"></textarea>
      <p v-if="err" class="text-red-600 text-xs">{{ err }}</p>
      <div class="flex gap-2">
        <button class="rounded-xl bg-navy text-white text-sm font-semibold px-4 py-2" :disabled="form.processing" @click="save">Simpan soal</button>
        <button class="rounded-xl border border-slate-300 text-sm px-4 py-2" @click="show = false">Batal</button>
      </div>
    </div>

    <p v-if="!questions.length" class="text-sm text-slate-500">Bank soal masih kosong.</p>
    <div v-for="q in questions" :key="q.id" class="bg-white rounded-2xl border border-slate-200 p-3 mb-2 flex items-start gap-3">
      <span class="text-[10px] uppercase bg-slate-100 rounded px-1.5 py-0.5 mt-0.5 whitespace-nowrap">{{ types[q.type] }}</span>
      <p class="flex-1 text-sm whitespace-pre-line line-clamp-3">{{ q.body }}</p>
      <span class="text-xs text-slate-400">{{ q.points }} poin</span>
      <button class="text-xs text-navy underline" @click="edit(q)">Ubah</button>
      <button class="text-xs text-red-600 underline" @click="del(q)">Hapus</button>
    </div>
  </LmsShell>
</template>
