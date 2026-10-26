<script setup>
import { ref } from 'vue'
import { Link, useForm, router } from '@inertiajs/vue3'
import LmsShell from '@/Components/Lms/LmsShell.vue'
const props = defineProps({ sessions: Array, allClasses: Array, activeYear: String })
const editId = ref(null)
const form = useForm({ title: '', description: '', opens_at: '', closes_at: '', late_after: '', code: '', clear_code: false, class_ids: [] })
function reset() { editId.value = null; form.reset(); form.clearErrors() }
function edit(s) {
  editId.value = s.id
  Object.assign(form, { title: s.title, description: s.description || '', opens_at: s.opens_at, closes_at: s.closes_at, late_after: s.late_after || '', code: '', clear_code: false, class_ids: [...s.class_ids] })
  window.scrollTo({ top: 0 })
}
function save() {
  const o = { preserveScroll: true, onSuccess: reset }
  editId.value ? form.put(`/ruang-guru/presensi/${editId.value}`, o) : form.post('/ruang-guru/presensi', o)
}
function del(s) { if (window.confirm(`Hapus presensi "${s.title}" beserta rekapnya?`)) router.delete(`/ruang-guru/presensi/${s.id}`, { preserveScroll: true }) }
const fmt = (v) => v ? v.replace('T', ' ') : '-'
</script>

<template>
  <LmsShell title="Presensi">
    <p class="text-sm text-slate-500 -mt-2 mb-4">Tahun ajaran aktif: <b>{{ activeYear || 'belum diatur' }}</b>. Waktu dalam WIB.</p>
    <form class="bg-white rounded-2xl border border-slate-200 p-5 space-y-3 mb-6" @submit.prevent="save">
      <h2 class="font-semibold text-navy">{{ editId ? 'Ubah presensi' : 'Buat presensi' }}</h2>
      <input v-model="form.title" placeholder="Judul (mis. Praktik Bengkel 28 Okt)" maxlength="150" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" />
      <textarea v-model="form.description" rows="2" placeholder="Keterangan (opsional)" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"></textarea>
      <div class="grid sm:grid-cols-3 gap-3 text-xs text-slate-500">
        <label>Buka<input v-model="form.opens_at" type="datetime-local" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-800" /></label>
        <label>Tutup<input v-model="form.closes_at" type="datetime-local" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-800" /></label>
        <label>Terlambat setelah (opsional)<input v-model="form.late_after" type="datetime-local" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-800" /></label>
      </div>
      <input v-model="form.code" :placeholder="editId ? 'Kode baru (kosong = tidak diubah)' : 'Kode presensi (opsional)'" maxlength="20" autocomplete="off" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" />
      <label v-if="editId" class="text-xs flex items-center gap-2"><input v-model="form.clear_code" type="checkbox" /> Hapus kode</label>
      <div>
        <div class="text-xs text-slate-500 mb-1">Rombel peserta</div>
        <div class="flex flex-wrap gap-3"><label v-for="k in allClasses" :key="k.id" class="text-sm flex items-center gap-1.5"><input v-model="form.class_ids" type="checkbox" :value="k.id" /> {{ k.name }}</label></div>
      </div>
      <p v-for="(e, k) in form.errors" :key="k" class="text-red-600 text-xs">{{ e }}</p>
      <div class="flex gap-2">
        <button class="rounded-xl bg-navy text-white text-sm font-semibold px-4 py-2" :disabled="form.processing">{{ editId ? 'Simpan' : 'Buat' }}</button>
        <button v-if="editId" type="button" class="text-sm text-slate-500" @click="reset">Batal</button>
      </div>
    </form>

    <div class="bg-white rounded-2xl border border-slate-200 divide-y divide-slate-100">
      <div v-for="s in sessions" :key="s.id" class="p-4 flex flex-wrap items-center gap-3 text-sm">
        <div class="flex-1 min-w-[12rem]">
          <Link :href="`/ruang-guru/presensi/${s.id}`" class="font-semibold text-navy hover:underline">{{ s.title }}</Link>
          <span v-if="s.open" class="ml-2 text-[10px] bg-emerald-50 text-emerald-700 rounded px-1.5 py-0.5">DIBUKA</span>
          <div class="text-xs text-slate-500">{{ fmt(s.opens_at) }} &rarr; {{ fmt(s.closes_at) }} &middot; {{ s.classes.join(', ') }} &middot; terisi {{ s.filled }}</div>
        </div>
        <button class="text-navy text-xs font-semibold" @click="edit(s)">Ubah</button>
        <button class="text-red-600 text-xs" @click="del(s)">Hapus</button>
      </div>
      <p v-if="!sessions.length" class="p-4 text-sm text-slate-500">Belum ada presensi.</p>
    </div>
  </LmsShell>
</template>
