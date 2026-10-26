<script setup>
import { reactive } from 'vue'
import { useForm } from '@inertiajs/vue3'
import LmsShell from '@/Components/Lms/LmsShell.vue'
const props = defineProps({ sessions: Array, statuses: Object })
const forms = reactive({})
const f = (id) => (forms[id] ??= useForm({ status: 'hadir', note: '', code: '' }))
const submit = (id) => f(id).post(`/belajar/presensi/${id}`, { preserveScroll: true })
const fmt = (v) => v.replace('T', ' ')
const color = { hadir: 'text-emerald-700', terlambat: 'text-amber-600', izin: 'text-sky-700', sakit: 'text-sky-700', alpa: 'text-red-600' }
</script>

<template>
  <LmsShell title="Presensi">
    <p v-if="!sessions.length" class="text-sm text-slate-500 bg-white rounded-2xl border p-6">Belum ada presensi untuk kelas Anda.</p>
    <div v-for="s in sessions" :key="s.id" class="bg-white rounded-2xl border border-slate-200 p-4 mb-3">
      <div class="flex flex-wrap items-center gap-2">
        <h2 class="font-semibold text-navy flex-1">{{ s.title }}</h2>
        <span v-if="s.status" class="text-sm font-semibold" :class="color[s.status]">{{ statuses[s.status] }}</span>
      </div>
      <p class="text-xs text-slate-500">{{ fmt(s.opens_at) }} &rarr; {{ fmt(s.closes_at) }} WIB &middot; {{ s.teacher }}</p>
      <p v-if="s.description" class="text-sm text-slate-600 mt-1 whitespace-pre-line">{{ s.description }}</p>
      <p v-if="s.note" class="text-xs text-slate-500 mt-1">Keterangan: {{ s.note }}</p>
      <p v-if="s.state==='soon'" class="text-xs text-slate-400 mt-2">Belum dibuka.</p>
      <p v-else-if="s.locked" class="text-xs text-slate-500 mt-2">Status diatur oleh guru.</p>
      <form v-else-if="s.state==='open'" class="mt-3 flex flex-wrap items-end gap-2" @submit.prevent="submit(s.id)">
        <select v-model="f(s.id).status" class="rounded-xl border border-slate-300 px-3 py-2 text-sm">
          <option value="hadir">Hadir</option><option value="izin">Izin</option><option value="sakit">Sakit</option>
        </select>
        <input v-if="f(s.id).status!=='hadir'" v-model="f(s.id).note" placeholder="Keterangan" maxlength="255" class="rounded-xl border border-slate-300 px-3 py-2 text-sm flex-1 min-w-[10rem]" />
        <input v-if="s.needs_code" v-model="f(s.id).code" placeholder="Kode dari guru" maxlength="20" autocomplete="off" class="rounded-xl border border-slate-300 px-3 py-2 text-sm w-40" />
        <button class="rounded-xl bg-accent text-navy font-bold text-sm px-4 py-2" :disabled="f(s.id).processing">{{ s.status ? 'Ubah' : 'Kirim' }}</button>
        <p v-for="(e, k) in f(s.id).errors" :key="k" class="text-red-600 text-xs w-full">{{ e }}</p>
      </form>
    </div>
  </LmsShell>
</template>
