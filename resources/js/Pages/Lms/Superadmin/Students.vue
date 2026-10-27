<script setup>
import { confirmDialog } from '@/dialog'
import { ref, computed } from 'vue'
import { router, usePage, Head, Link } from '@inertiajs/vue3'
import SuperadminLayout from '@/Layouts/SuperadminLayout.vue'

const props = defineProps(['indukReady', 'activeYear', 'classes', 'students', 'q', 'kelas', 'preview'])
const page = usePage()
const search = ref(props.q || '')
const busy = ref('')
const ok = computed(() => props.indukReady && props.activeYear)

const act = (key, url) => { busy.value = key; router.post(url, {}, { preserveScroll: true, onFinish: () => (busy.value = '') }) }
const tarikKelas = () => act('kelas', '/superadmin/kelas/tarik')
const pratinjau = (c) => act('p' + c.id, `/superadmin/kelas/${c.id}/pratinjau`)
const tarik = async (c) => await confirmDialog(`Tarik & simpan siswa kelas ${c.name} dari induk?`) && act('t' + c.id, `/superadmin/kelas/${c.id}/tarik`)
const penulisKelas = async (c, on) => await confirmDialog(`${on ? 'Beri' : 'Cabut'} izin menulis berita ${on ? 'ke' : 'dari'} semua siswa kelas ${c.name}?`) && router.post(`/superadmin/kelas/${c.id}/penulis`, { on }, { preserveScroll: true })
const filter = (extra = {}) => router.get('/superadmin/siswa', { q: search.value || undefined, kelas: props.kelas || undefined, ...extra }, { preserveState: true, replace: true })
const writer = (u) => router.post(`/superadmin/akun/${u.id}/penulis`, {}, { preserveScroll: true })
const reset = async (u) => await confirmDialog(`Reset password ${u.name} ke username?`) && router.post(`/superadmin/akun/${u.id}/reset`, {}, { preserveScroll: true })
const toggle = async (u) => await confirmDialog(`${u.is_active ? 'Nonaktifkan' : 'Aktifkan'} akun ${u.name}?`) && router.post(`/superadmin/akun/${u.id}/aktif`, {}, { preserveScroll: true })
const fmt = (t) => (t ? new Date(t).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) : 'belum pernah')
const warna = { baru: 'bg-green-100 text-green-700', berubah: 'bg-amber-100 text-amber-700', sama: 'bg-slate-100 text-slate-600', 'masuk kelas': 'bg-blue-100 text-blue-700', 'keluar kelas': 'bg-red-100 text-red-700' }
</script>

<template>
  <Head title="Data Siswa" />
  <SuperadminLayout title="Data Siswa">
    <div class="space-y-6">
      <p v-if="page.props.errors?.sync" class="rounded bg-red-100 px-3 py-2 text-sm text-red-800">{{ page.props.errors.sync }}</p>

      <section class="rounded-2xl bg-white border border-line p-5">
        <div class="mb-1 flex flex-wrap items-center justify-between gap-2">
          <h2 class="font-semibold">Kelas dari Data Induk</h2>
          <button class="rounded bg-navy px-3 py-1.5 text-sm text-white disabled:opacity-50" :disabled="!ok || !!busy" @click="tarikKelas">{{ busy === 'kelas' ? 'Menarik…' : 'Tarik daftar kelas' }}</button>
        </div>
        <p class="mb-3 text-xs text-slate-500">Tahun ajaran aktif: <b>{{ activeYear || 'belum diatur' }}</b>. Tarik daftar kelas dulu, lalu tarik siswa <b>per kelas</b>. "Pratinjau" tidak menyimpan apa pun. Sinkron ulang dilakukan manual dengan menarik kelasnya lagi saat induk berubah.
          <span v-if="!indukReady" class="text-red-600"> API induk belum diatur di .env.</span>
          <span v-if="!activeYear" class="text-red-600"> Belum ada tahun ajaran aktif.</span></p>
        <div class="overflow-x-auto"><table class="w-full text-left text-sm">
          <thead class="text-xs text-slate-500"><tr><th class="py-1">Kelas</th><th>Siswa di sini</th><th>Terakhir ditarik</th><th></th></tr></thead>
          <tbody>
            <tr v-for="c in classes" :key="c.id" class="border-t">
              <td class="py-1.5 font-medium">{{ c.name }}</td><td>{{ c.members }}</td><td class="text-xs text-slate-500">{{ fmt(c.synced_at) }}</td>
              <td class="space-x-3 text-right">
                <button class="text-emerald-700 hover:underline" title="Izinkan semua siswa kelas ini menulis berita" @click="penulisKelas(c, true)">Izinkan menulis</button>
                <button class="text-red-600 hover:underline" @click="penulisKelas(c, false)">Cabut</button>
                <button class="text-slate-600 hover:underline disabled:opacity-50" :disabled="!ok || !!busy" @click="pratinjau(c)">{{ busy === 'p' + c.id ? '…' : 'Pratinjau' }}</button>
                <button class="rounded bg-navy px-2.5 py-1 text-xs text-white disabled:opacity-50" :disabled="!ok || !!busy" @click="tarik(c)">{{ busy === 't' + c.id ? 'Menarik…' : (c.synced_at ? 'Sinkron ulang' : 'Tarik siswa') }}</button>
              </td>
            </tr>
            <tr v-if="!classes.length"><td colspan="4" class="py-3 text-slate-500">Belum ada kelas. Klik "Tarik daftar kelas".</td></tr>
          </tbody>
        </table></div>
      </section>

      <section v-if="preview" class="rounded-2xl border border-amber-300 bg-amber-50 p-5">
        <div class="mb-2 flex items-center justify-between">
          <h2 class="font-semibold">Pratinjau {{ preview.class }} <span class="text-xs font-normal text-slate-500">(belum disimpan)</span></h2>
          <button class="rounded bg-navy px-3 py-1.5 text-sm text-white disabled:opacity-50" :disabled="!!busy" @click="tarik({ id: preview.class_id, name: preview.class })">Simpan ke sistem</button>
        </div>
        <p class="mb-2 text-sm">{{ preview.total }} siswa di induk · {{ preview.created }} baru · {{ preview.updated }} berubah · {{ preview.same }} sama · {{ preview.left }} keluar kelas · {{ preview.conflicts }} bentrok</p>
        <div class="max-h-72 overflow-auto"><table class="w-full text-left text-sm">
          <tbody><tr v-for="(r, i) in preview.rows" :key="i" class="border-t border-amber-200"><td class="py-1">{{ r.nis }}</td><td>{{ r.name }}</td><td><span class="rounded px-2 py-0.5 text-xs" :class="warna[r.state] || 'bg-red-100 text-red-700'">{{ r.state }}</span></td></tr></tbody>
        </table></div>
      </section>

      <section class="rounded-2xl bg-white border border-line p-5 space-y-3">
        <h2 class="font-semibold">Daftar siswa</h2>
        <form class="flex flex-wrap gap-2" @submit.prevent="filter()">
          <input v-model="search" placeholder="Cari nama / NIS…" class="w-full rounded border px-3 py-1.5 text-sm md:w-64" />
          <select :value="kelas" class="rounded border px-3 py-1.5 text-sm" @change="filter({ kelas: $event.target.value || undefined })">
            <option value="0">Semua kelas</option><option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
          <button class="rounded border px-3 py-1.5 text-sm">Cari</button>
        </form>
        <div class="overflow-x-auto"><table class="w-full text-left text-sm">
          <thead class="text-xs text-slate-500"><tr><th class="py-1">Nama</th><th>NIS</th><th>Username</th><th>Kelas</th><th>Penulis berita</th><th>Status</th><th></th></tr></thead>
          <tbody>
            <tr v-for="u in students.data" :key="u.id" class="border-t">
              <td class="py-1.5">{{ u.name }}</td><td>{{ u.nis }}</td><td>{{ u.username }}</td><td>{{ u.kelas || '-' }}</td>
              <td><button type="button" role="switch" :aria-checked="u.can_write_posts" :title="u.can_write_posts ? 'Cabut izin menulis' : 'Beri izin menulis berita'" class="relative h-5 w-9 rounded-full transition" :class="u.can_write_posts ? 'bg-navy' : 'bg-slate-300'" @click="writer(u)"><span class="absolute top-0.5 h-4 w-4 rounded-full bg-white transition-all" :class="u.can_write_posts ? 'left-[18px]' : 'left-0.5'"></span></button></td>
              <td>{{ u.is_active ? 'Aktif' : 'Nonaktif' }}<span v-if="u.must_change_password" class="ml-1 text-xs text-amber-600">(belum ganti password)</span></td>
              <td class="space-x-2 text-right"><button class="text-navy font-semibold hover:underline" @click="reset(u)">Reset password</button><button class="text-slate-600 hover:underline" @click="toggle(u)">{{ u.is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button></td>
            </tr>
            <tr v-if="!students.data.length"><td colspan="7" class="py-3 text-slate-500">Belum ada siswa.</td></tr>
          </tbody>
        </table></div>
      <div v-if="students.links && students.last_page > 1" class="flex flex-wrap gap-1 text-sm">
        <template v-for="l in students.links" :key="l.label">
          <Link v-if="l.url" :href="l.url" preserve-scroll class="rounded border px-2.5 py-1" :class="l.active ? 'bg-navy text-white border-navy' : 'bg-white hover:bg-slate-50'" v-html="l.label" />
          <span v-else class="rounded border px-2.5 py-1 text-slate-400" v-html="l.label" />
        </template>
      </div>
      </section>
    </div>
  </SuperadminLayout>
</template>
