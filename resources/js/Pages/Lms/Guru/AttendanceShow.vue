<script setup>
import { router } from '@inertiajs/vue3'
import LmsShell from '@/Components/Lms/LmsShell.vue'
const props = defineProps({ session: Object, rows: Array, summary: Object, statuses: Object })
function set(r, status) {
  const note = status === 'izin' || status === 'sakit' ? window.prompt('Keterangan (opsional)', r.note || '') : ''
  if (note === null) return
  router.post(`/ruang-guru/presensi/${props.session.id}/siswa/${r.id}`, { status, note }, { preserveScroll: true })
}
const color = { hadir: 'text-emerald-700', terlambat: 'text-amber-600', izin: 'text-sky-700', sakit: 'text-sky-700', alpa: 'text-red-600' }
</script>

<template>
  <LmsShell :title="session.title" back="/ruang-guru/presensi" backLabel="Presensi">
    <div class="flex flex-wrap gap-3 text-xs mb-4">
      <span v-for="(label, k) in statuses" :key="k" class="bg-white border rounded-full px-3 py-1">{{ label }}: <b>{{ summary[k] || 0 }}</b></span>
      <a :href="`/ruang-guru/presensi/${session.id}/ekspor`" class="rounded-full bg-navy text-white px-3 py-1 font-semibold">Unduh CSV</a>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200 divide-y divide-slate-100">
      <div v-for="r in rows" :key="r.id" class="px-4 py-2 flex flex-wrap items-center gap-3 text-sm">
        <span class="flex-1 min-w-[10rem]">{{ r.name }}<span v-if="r.note" class="block text-xs text-slate-500">{{ r.note }}</span></span>
        <span class="font-semibold" :class="color[r.status]">{{ statuses[r.status] }}<span v-if="!r.filled" class="text-slate-400 font-normal"> (belum mengisi)</span></span>
        <select class="text-xs rounded-lg border border-slate-300 px-2 py-1" :value="r.status" @change="set(r, $event.target.value)">
          <option v-for="(label, k) in statuses" :key="k" :value="k">{{ label }}</option>
        </select>
      </div>
      <p v-if="!rows.length" class="p-4 text-sm text-slate-500">Belum ada peserta.</p>
    </div>
  </LmsShell>
</template>
