<script setup>
import { ref } from 'vue'
import { useForm, router, usePage, Head } from '@inertiajs/vue3'

const props = defineProps(['years', 'activeYear', 'indukReady', 'counts', 'classes', 'runs', 'accounts', 'q'])
const page = usePage()
const yearForm = useForm({ name: '' })
const teacherForm = useForm({ name: '', username: '' })
const search = ref(props.q || '')
const busy = ref(false)

const post = (url, opts = {}) => router.post(url, {}, { preserveScroll: true, ...opts })
const reset = (u) => confirm(`Reset password ${u.name} ke username?`) && post(`/superadmin/akun/${u.id}/reset`)
const toggle = (u) => confirm(`${u.is_active ? 'Nonaktifkan' : 'Aktifkan'} akun ${u.name}?`) && post(`/superadmin/akun/${u.id}/aktif`)
const hapusTahun = (y) => confirm(`Hapus tahun ajaran ${y.name}?`) && router.delete(`/superadmin/tahun-ajaran/${y.id}`, { preserveScroll: true })
const sync = () => { busy.value = true; post('/superadmin/sinkron', { onFinish: () => (busy.value = false) }) }
const cari = () => router.get('/superadmin', { q: search.value }, { preserveState: true, replace: true })
const logout = () => router.post('/logout')
const fmt = (t) => (t ? new Date(t).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) : '-')
</script>

<template>
  <Head title="Superadmin" />
  <div class="min-h-screen bg-slate-50 text-slate-800">
    <header class="bg-[#0b2a4a] text-white">
      <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3">
        <h1 class="font-semibold">Superadmin · LMS TSM</h1>
        <a href="/superadmin/mapel" class="rounded bg-white/10 px-3 py-1 text-sm hover:bg-white/20" @click.prevent="router.get('/superadmin/mapel')">Mapel</a>
        <button class="rounded bg-white/10 px-3 py-1 text-sm hover:bg-white/20" @click="logout">Keluar</button>
      </div>
    </header>

    <main class="mx-auto max-w-6xl space-y-6 px-4 py-6">
      <p v-if="page.props.flash?.success" class="rounded bg-green-100 px-3 py-2 text-sm text-green-800">{{ page.props.flash.success }}</p>
      <p v-if="page.props.errors?.sync || page.props.errors?.name" class="rounded bg-red-100 px-3 py-2 text-sm text-red-800">{{ page.props.errors.sync || page.props.errors.name }}</p>

      <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
        <div class="rounded-lg bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">Tahun ajaran aktif</div><div class="text-xl font-bold">{{ activeYear || 'Belum diatur' }}</div></div>
        <div class="rounded-lg bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">Siswa aktif</div><div class="text-xl font-bold">{{ counts.siswa }}</div></div>
        <div class="rounded-lg bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">Guru aktif</div><div class="text-xl font-bold">{{ counts.guru }}</div></div>
        <div class="rounded-lg bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">Rombel (dari induk)</div><div class="text-xl font-bold">{{ counts.rombel }}</div></div>
      </div>

      <section class="rounded-lg bg-white p-4 shadow-sm">
        <h2 class="mb-3 font-semibold">Tahun ajaran</h2>
        <form class="mb-3 flex gap-2" @submit.prevent="yearForm.post('/superadmin/tahun-ajaran', { preserveScroll: true, onSuccess: () => yearForm.reset() })">
          <input v-model="yearForm.name" placeholder="2026/2027" class="w-40 rounded border px-3 py-1.5 text-sm" />
          <button class="rounded bg-[#0b2a4a] px-3 py-1.5 text-sm text-white" :disabled="yearForm.processing">Tambah</button>
        </form>
        <ul class="divide-y text-sm">
          <li v-for="y in years" :key="y.id" class="flex items-center justify-between py-2">
            <span>{{ y.name }} <b v-if="y.is_active" class="ml-2 rounded bg-green-100 px-2 py-0.5 text-xs text-green-700">Aktif</b></span>
            <span class="space-x-2">
              <button v-if="!y.is_active" class="text-blue-700 hover:underline" @click="post(`/superadmin/tahun-ajaran/${y.id}/aktifkan`)">Jadikan aktif</button>
              <button v-if="!y.is_active" class="text-red-600 hover:underline" @click="hapusTahun(y)">Hapus</button>
            </span>
          </li>
          <li v-if="!years.length" class="py-2 text-slate-500">Belum ada tahun ajaran.</li>
        </ul>
      </section>

      <section class="rounded-lg bg-white p-4 shadow-sm">
        <div class="mb-2 flex items-center justify-between">
          <h2 class="font-semibold">Sinkronisasi Data Induk</h2>
          <button class="rounded bg-[#0b2a4a] px-3 py-1.5 text-sm text-white disabled:opacity-50" :disabled="busy || !indukReady || !activeYear" @click="sync">{{ busy ? 'Menyinkron…' : 'Sinkron sekarang' }}</button>
        </div>
        <p class="mb-3 text-xs text-slate-500">Rombel, siswa, dan kenaikan kelas dikelola di aplikasi induk; di sini hanya disalin (otomatis tiap 02:00). Keanggotaan kelas mengikuti tahun ajaran aktif.
          <span v-if="!indukReady" class="text-red-600"> API induk belum diatur di .env.</span></p>
        <div class="overflow-x-auto"><table class="w-full text-left text-sm">
          <thead class="text-xs text-slate-500"><tr><th class="py-1">Waktu</th><th>Pemicu</th><th>Hasil</th><th>Rombel</th><th>Baru</th><th>Ubah</th><th>Nonaktif</th><th>Bentrok</th></tr></thead>
          <tbody><tr v-for="r in runs" :key="r.id" class="border-t">
            <td class="py-1">{{ fmt(r.started_at) }}</td><td>{{ r.trigger }}</td><td>{{ r.status }}<span v-if="r.message" class="block text-xs text-slate-500">{{ r.message }}</span></td>
            <td>{{ r.classes }}</td><td>{{ r.created }}</td><td>{{ r.updated }}</td><td>{{ r.deactivated }}</td><td>{{ r.conflicts }}</td></tr>
            <tr v-if="!runs.length"><td colspan="8" class="py-2 text-slate-500">Belum pernah sinkron.</td></tr></tbody>
        </table></div>
        <div v-if="classes.length" class="mt-3 flex flex-wrap gap-2 text-xs">
          <span v-for="c in classes" :key="c.id" class="rounded bg-slate-100 px-2 py-1">{{ c.name }} · {{ c.members }}</span>
        </div>
      </section>

      <section class="rounded-lg bg-white p-4 shadow-sm">
        <h2 class="mb-3 font-semibold">Akun guru & siswa</h2>
        <form class="mb-4 grid gap-2 md:grid-cols-[1fr_1fr_auto]" @submit.prevent="teacherForm.post('/superadmin/guru', { preserveScroll: true, onSuccess: () => teacherForm.reset() })">
          <input v-model="teacherForm.name" placeholder="Nama guru" class="rounded border px-3 py-1.5 text-sm" />
          <input v-model="teacherForm.username" placeholder="NIK (username login)" class="rounded border px-3 py-1.5 text-sm" />
          <button class="rounded bg-[#0b2a4a] px-3 py-1.5 text-sm text-white" :disabled="teacherForm.processing">Buat akun guru</button>
          <p v-if="teacherForm.errors.name || teacherForm.errors.username" class="text-xs text-red-600 md:col-span-3">{{ teacherForm.errors.name || teacherForm.errors.username }}</p>
        </form>
        <form class="mb-2" @submit.prevent="cari"><input v-model="search" placeholder="Cari nama / username…" class="w-full rounded border px-3 py-1.5 text-sm md:w-72" /></form>
        <div class="overflow-x-auto"><table class="w-full text-left text-sm">
          <thead class="text-xs text-slate-500"><tr><th class="py-1">Nama</th><th>Username</th><th>Peran</th><th>Status</th><th></th></tr></thead>
          <tbody><tr v-for="u in accounts" :key="u.id" class="border-t">
            <td class="py-1.5">{{ u.name }}</td><td>{{ u.username }}</td><td>{{ u.role }}</td>
            <td>{{ u.is_active ? 'Aktif' : 'Nonaktif' }}<span v-if="u.must_change_password" class="ml-1 text-xs text-amber-600">(belum ganti password)</span></td>
            <td class="space-x-2 text-right"><button class="text-blue-700 hover:underline" @click="reset(u)">Reset password</button><button class="text-slate-600 hover:underline" @click="toggle(u)">{{ u.is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button></td></tr>
            <tr v-if="!accounts.length"><td colspan="5" class="py-2 text-slate-500">Tidak ada akun.</td></tr></tbody>
        </table></div>
      </section>
    </main>
  </div>
</template>
