<script setup>
import { ref } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import LmsShell from '@/Components/Lms/LmsShell.vue'
const props = defineProps({ course: Object, quiz: Object, attached: Array, bank: Array, types: Object, attempts: Array, average: Number })
const f = useForm({ ...props.quiz, access_code: '', clear_code: false })
function save() { f.put(`/ruang-guru/quiz/${props.quiz.id}`, { preserveScroll: true }) }
const items = ref(props.attached.map(a => ({ id: a.id, points: a.points })))
const label = Object.fromEntries([...props.attached, ...props.bank].map(q => [q.id, q]))
function add(q) { if (!items.value.find(i => i.id === q.id)) items.value.push({ id: q.id, points: Number(q.points) || 1 }) }
function rm(i) { items.value.splice(i, 1) }
function saveQs() { router.post(`/ruang-guru/quiz/${props.quiz.id}/soal`, { items: items.value }, { preserveScroll: true, onSuccess: () => router.reload() }) }
function del() { if (confirm('Hapus quiz ini?')) router.delete(`/ruang-guru/quiz/${props.quiz.id}`) }
const statusLabel = { in_progress: 'Berjalan', submitted: 'Menunggu nilai uraian / selesai', graded: 'Dinilai' }
</script>

<template>
  <LmsShell :title="quiz.title" :back="`/ruang-guru/mapel/${course.id}/quiz`" backLabel="Daftar quiz">
    <section class="bg-white rounded-2xl border border-slate-200 p-4 mb-5 grid sm:grid-cols-2 gap-2">
      <input v-model="f.title" maxlength="150" class="sm:col-span-2 rounded-xl border border-slate-300 px-3 py-2 text-sm" />
      <label class="text-xs text-slate-500">Buka (WIB)<input v-model="f.opens_at" type="datetime-local" class="block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" /></label>
      <label class="text-xs text-slate-500">Tutup (WIB)<input v-model="f.closes_at" type="datetime-local" class="block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" /></label>
      <label class="text-xs text-slate-500">Durasi (menit)<input v-model="f.duration_min" type="number" min="1" :disabled="quiz.locked" class="block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" /></label>
      <label class="text-xs text-slate-500">Maks. percobaan<input v-model="f.max_attempts" type="number" min="1" max="10" :disabled="quiz.locked" class="block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" /></label>
      <label class="text-xs text-slate-500">Nilai lulus (opsional)<input v-model="f.pass_score" type="number" min="0" max="100" class="block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" /></label>
      <label class="text-xs text-slate-500">Tampilkan hasil
        <select v-model="f.show_result" class="block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
          <option value="after_submit">Setelah dikumpulkan</option><option value="after_close">Setelah quiz ditutup</option><option value="never">Tidak ditampilkan</option>
        </select></label>
      <label class="text-xs text-slate-500">Kode quiz {{ quiz.has_code ? '(sudah diatur; isi untuk mengganti)' : '(opsional, min. 4 karakter)' }}
        <input v-model="f.access_code" maxlength="40" autocomplete="off" class="block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" /></label>
      <label v-if="quiz.has_code" class="text-xs text-slate-600 flex items-end gap-1"><input type="checkbox" v-model="f.clear_code" /> Hapus kode</label>
      <label class="text-sm"><input type="checkbox" v-model="f.shuffle_questions" :disabled="quiz.locked" /> Acak soal</label>
      <label class="text-sm"><input type="checkbox" v-model="f.shuffle_options" :disabled="quiz.locked" /> Acak pilihan</label>
      <label class="text-sm font-semibold"><input type="checkbox" v-model="f.is_published" /> Tayangkan ke siswa</label>
      <p v-for="(e, k) in f.errors" :key="k" class="text-red-600 text-xs sm:col-span-2">{{ e }}</p>
      <div class="sm:col-span-2 flex gap-2">
        <button class="rounded-xl bg-navy text-white text-sm font-semibold px-4 py-2" :disabled="f.processing" @click="save">Simpan pengaturan</button>
        <button class="rounded-xl border border-red-300 text-red-600 text-sm px-4 py-2" @click="del">Hapus quiz</button>
      </div>
    </section>

    <section class="bg-white rounded-2xl border border-slate-200 p-4 mb-5">
      <h2 class="font-semibold text-navy mb-2">Soal quiz ({{ items.length }})</h2>
      <p v-if="quiz.locked" class="text-xs text-amber-700 mb-2">Sudah dikerjakan siswa — susunan soal dikunci.</p>
      <div v-for="(it, i) in items" :key="it.id" class="flex items-center gap-2 text-sm border-b border-slate-50 py-1.5">
        <span class="text-xs text-slate-400 w-5">{{ i + 1 }}</span>
        <span class="flex-1 truncate">{{ label[it.id]?.body }}</span>
        <span v-if="label[it.id]?.percent_correct != null" class="text-[11px] rounded px-1.5 py-0.5" :class="label[it.id].percent_correct < 40 ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700'">{{ label[it.id].percent_correct }}% benar</span>
        <input v-model.number="it.points" type="number" step="0.5" min="0.5" :disabled="quiz.locked" class="w-16 rounded border border-slate-300 px-1 py-0.5 text-xs" />
        <button v-if="!quiz.locked" class="text-xs text-red-600" @click="rm(i)">Lepas</button>
      </div>
      <button v-if="!quiz.locked" class="mt-3 rounded-xl bg-navy text-white text-sm px-4 py-1.5" @click="saveQs">Simpan daftar soal</button>
      <details v-if="!quiz.locked" class="mt-3">
        <summary class="text-sm text-navy cursor-pointer">Tambah dari bank soal ({{ bank.length }})</summary>
        <div v-for="q in bank" :key="q.id" class="flex items-center gap-2 text-sm py-1">
          <span class="text-[10px] uppercase bg-slate-100 rounded px-1.5">{{ types[q.type] }}</span>
          <span class="flex-1 truncate">{{ q.body }}</span>
          <button class="text-xs text-navy underline" @click="add(q)">Tambah</button>
        </div>
      </details>
    </section>

    <section class="bg-white rounded-2xl border border-slate-200 p-4">
      <h2 class="font-semibold text-navy mb-2">Pengerjaan <span v-if="average != null" class="text-sm font-normal text-slate-500">· rata-rata {{ average }}</span></h2>
      <p v-if="!attempts.length" class="text-sm text-slate-500">Belum ada.</p>
      <Link v-for="a in attempts" :key="a.id" :href="`/ruang-guru/pengerjaan/${a.id}`" class="flex items-center gap-2 text-sm py-1.5 border-b border-slate-50 hover:bg-slate-50">
        <span class="flex-1">{{ a.student }} <span class="text-xs text-slate-400">#{{ a.no }}</span></span>
        <span v-if="a.tab_switch_count" class="text-[11px] bg-amber-100 text-amber-700 rounded px-1.5">pindah tab {{ a.tab_switch_count }}×</span>
        <span class="text-xs text-slate-500">{{ statusLabel[a.status] }}</span>
        <b class="w-12 text-right">{{ a.score ?? '-' }}</b>
      </Link>
    </section>
  </LmsShell>
</template>
