<script setup>
import { Link } from '@inertiajs/vue3'
import LmsShell from '@/Components/Lms/LmsShell.vue'
const props = defineProps({ course: Object, columns: Array, rows: Array })
</script>

<template>
  <LmsShell :title="`Buku nilai — ${course.title}`" :back="`/ruang-guru/mapel/${course.id}`" backLabel="Mapel">
    <div class="flex flex-wrap gap-2 mb-4">
      <a :href="`/ruang-guru/mapel/${course.id}/buku-nilai/ekspor`" class="rounded-xl bg-navy text-white text-sm font-semibold px-4 py-2">Ekspor Excel</a>
      <Link :href="`/ruang-guru/mapel/${course.id}/tugas`" class="rounded-xl border border-slate-300 text-sm px-4 py-2">Tugas</Link>
    </div>
    <p class="text-xs text-slate-500 mb-3">Nilai tugas dalam persen dari nilai maksimal; quiz memakai nilai terbaik. Rata-rata menghitung semua kolom (kosong = 0).</p>

    <div v-if="!columns.length" class="text-sm text-slate-500">Belum ada tugas atau quiz yang ditayangkan.</div>
    <div v-else class="bg-white rounded-2xl border border-slate-200 overflow-x-auto">
      <table class="text-sm">
        <thead class="bg-slate-50 text-xs text-slate-500">
          <tr>
            <th class="text-left px-3 py-2 sticky left-0 bg-slate-50">Nama</th>
            <th class="text-left px-3 py-2">NIS</th>
            <th v-for="c in columns" :key="c.id" class="px-3 py-2 text-center whitespace-nowrap">
              {{ c.title }}<br /><span class="text-[10px] font-normal">{{ c.kind }}</span>
            </th>
            <th class="px-3 py-2 text-center">Rata-rata</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="r in rows" :key="r.id" class="border-t border-slate-100">
            <td class="px-3 py-2 sticky left-0 bg-white whitespace-nowrap">{{ r.name }}</td>
            <td class="px-3 py-2 text-slate-500">{{ r.nis }}</td>
            <td v-for="(v, i) in r.cells" :key="i" class="px-3 py-2 text-center" :class="v === null ? 'text-slate-300' : ''">{{ v === null ? '—' : v }}</td>
            <td class="px-3 py-2 text-center font-semibold text-navy">{{ r.average ?? '—' }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </LmsShell>
</template>
