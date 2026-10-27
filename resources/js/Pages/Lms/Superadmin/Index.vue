<script setup>
import { confirmDialog } from '@/dialog'
import { ref } from 'vue'
import { useForm, router, usePage, Head } from '@inertiajs/vue3'
import SuperadminLayout from '@/Layouts/SuperadminLayout.vue'

const props = defineProps(['years', 'activeYear', 'indukReady', 'counts', 'classes', 'runs', 'accounts', 'q'])
const page = usePage()
const yearForm = useForm({ name: '' })
const teacherForm = useForm({ name: '', username: '' })
const search = ref(props.q || '')
const busy = ref(false)

const post = (url, opts = {}) => router.post(url, {}, { preserveScroll: true, ...opts })
const reset = async (u) => await confirmDialog(`Reset password ${u.name} ke username?`) && post(`/superadmin/akun/${u.id}/reset`)
const toggle = async (u) => await confirmDialog(`${u.is_active ? 'Nonaktifkan' : 'Aktifkan'} akun ${u.name}?`) && post(`/superadmin/akun/${u.id}/aktif`)
const hapusTahun = async (y) => await confirmDialog(`Hapus tahun ajaran ${y.name}?`) && router.delete(`/superadmin/tahun-ajaran/${y.id}`, { preserveScroll: true })
const sync = () => { busy.value = true; post('/superadmin/sinkron', { onFinish: () => (busy.value = false) }) }
const cari = () => router.get('/superadmin', { q: search.value }, { preserveState: true, replace: true })
const logout = () => router.post('/logout')
const fmt = (t) => (t ? new Date(t).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) : '-')
</script>

<template>
  <Head title="Superadmin" />
  <SuperadminLayout title="Dashboard Superadmin">
    <div class="space-y-6">
      <p v-if="page.props.errors?.sync || page.props.errors?.name" class="rounded bg-red-100 px-3 py-2 text-sm text-red-800">{{ page.props.errors.sync || page.props.errors.name }}</p>

      <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
        <div class="rounded-2xl bg-white border border-line p-5"><div class="text-xs text-slate-500">Tahun ajaran aktif</div><div class="text-xl font-bold">{{ activeYear || 'Belum diatur' }}</div></div>
        <div class="rounded-2xl bg-white border border-line p-5"><div class="text-xs text-slate-500">Siswa aktif</div><div class="text-xl font-bold">{{ counts.siswa }}</div></div>
        <div class="rounded-2xl bg-white border border-line p-5"><div class="text-xs text-slate-500">Guru aktif</div><div class="text-xl font-bold">{{ counts.guru }}</div></div>
        <div class="rounded-2xl bg-white border border-line p-5"><div class="text-xs text-slate-500">Rombel (dari induk)</div><div class="text-xl font-bold">{{ counts.rombel }}</div></div>
      </div>

      <section class="rounded-2xl bg-white border border-line p-5">
        <h2 class="mb-3 font-semibold">Tahun ajaran</h2>
        <form class="mb-3 flex gap-2" @submit.prevent="yearForm.post('/superadmin/tahun-ajaran', { preserveScroll: true, onSuccess: () => yearForm.reset() })">
          <input v-model="yearForm.name" placeholder="2026/2027" class="w-40 rounded border px-3 py-1.5 text-sm" />
          <button class="rounded bg-navy px-3 py-1.5 text-sm text-white" :disabled="yearForm.processing">Tambah</button>
        </form>
        <ul class="divide-y text-sm">
          <li v-for="y in years" :key="y.id" class="flex items-center justify-between py-2">
            <span>{{ y.name }} <b v-if="y.is_active" class="ml-2 rounded bg-green-100 px-2 py-0.5 text-xs text-green-700">Aktif</b></span>
            <span class="space-x-2">
              <button v-if="!y.is_active" class="text-navy font-semibold hover:underline" @click="post(`/superadmin/tahun-ajaran/${y.id}/aktifkan`)">Jadikan aktif</button>
              <button v-if="!y.is_active" class="text-red-600 hover:underline" @click="hapusTahun(y)">Hapus</button>
            </span>
          </li>
          <li v-if="!years.length" class="py-2 text-slate-500">Belum ada tahun ajaran.</li>
        </ul>
      </section>


    </div>
  </SuperadminLayout>
</template>
