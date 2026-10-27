<script setup>
import { confirmDialog } from '@/dialog'
import { useForm, router } from '@inertiajs/vue3'
import SuperadminLayout from '@/Layouts/SuperadminLayout.vue'
const props = defineProps({ total: Number, count: Number, retention: Number, perCourse: Array, topFiles: Array })
const f = useForm({ retention: props.retention })
const mb = (b) => (b / 1048576).toFixed(2) + ' MB'
function save() { f.put('/superadmin/penyimpanan', { preserveScroll: true }) }
async function del(file) { if (await confirmDialog(`Hapus berkas "${file.name}"? Nilai siswa tetap tersimpan.`)) router.delete(`/superadmin/penyimpanan/${file.id}`, { preserveScroll: true }) }
</script>

<template>
  <SuperadminLayout title="Penyimpanan Lampiran">
    <div class="space-y-6">

      <div class="grid grid-cols-2 gap-3 md:grid-cols-3">
        <div class="rounded-2xl bg-white border border-line p-5"><div class="text-xs text-slate-500">Total lampiran tugas</div><div class="text-xl font-bold">{{ mb(total) }}</div></div>
        <div class="rounded-2xl bg-white border border-line p-5"><div class="text-xs text-slate-500">Jumlah berkas</div><div class="text-xl font-bold">{{ count }}</div></div>
        <div class="rounded-2xl bg-white border border-line p-5">
          <div class="text-xs text-slate-500">Retensi (hari, 0 = mati)</div>
          <div class="flex gap-2 mt-1">
            <input v-model="f.retention" type="number" min="0" max="3650" class="w-24 rounded border border-slate-300 px-2 py-1 text-sm" />
            <button class="rounded bg-navy px-3 py-1 text-sm text-white" :disabled="f.processing" @click="save">Simpan</button>
          </div>
        </div>
      </div>

      <section class="rounded-2xl bg-white border border-line p-5">
        <h2 class="font-semibold mb-2">Pemakaian per mapel</h2>
        <p v-if="!perCourse.length" class="text-sm text-slate-500">Belum ada lampiran.</p>
        <div v-for="c in perCourse" :key="c.id" class="flex justify-between border-t border-slate-100 py-1.5 text-sm">
          <span>{{ c.title }} <span class="text-xs text-slate-500">{{ c.year }}</span></span><span class="font-semibold">{{ mb(c.bytes) }}</span>
        </div>
      </section>

      <section class="rounded-2xl bg-white border border-line p-5 overflow-x-auto">
        <h2 class="font-semibold mb-2">Berkas terbesar</h2>
        <table class="w-full text-sm">
          <thead class="text-xs text-slate-500"><tr><th class="text-left py-1">Berkas</th><th class="text-left">Mapel</th><th class="text-left">Tugas</th><th class="text-right">Ukuran</th><th></th></tr></thead>
          <tbody>
            <tr v-for="file in topFiles" :key="file.id" class="border-t border-slate-100">
              <td class="py-1.5 pr-2">{{ file.name }}<div class="text-[11px] text-slate-400">{{ file.created_at }}</div></td>
              <td class="pr-2">{{ file.course }}</td>
              <td class="pr-2">{{ file.assignment }}</td>
              <td class="text-right">{{ mb(file.size) }}</td>
              <td class="text-right"><button class="text-xs text-red-600" @click="del(file)">Hapus</button></td>
            </tr>
          </tbody>
        </table>
      </section>
    </div>
  </SuperadminLayout>
</template>
