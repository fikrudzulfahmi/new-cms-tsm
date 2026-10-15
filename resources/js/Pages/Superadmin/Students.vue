<script setup>
import { ref, computed } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import axios from 'axios'
import SuperappLayout from '@/Layouts/SuperappLayout.vue'

const props = defineProps({ students: Object, kelasLokal: Array, filters: Object, apiReady: Boolean, apiUrl: String })

// ---- Tarik dari API ----
const kelasApi = ref([])
const kelasId = ref('')
const loadingKelas = ref(false)
const loadingPreview = ref(false)
const apiError = ref('')
const preview = ref(null) // { kelas, data: [...] }
const picked = ref([])
const importForm = useForm({ kelas_id: '', nis: [] })

const stateInfo = {
  baru: ['Baru', 'bg-emerald-100 text-emerald-800'],
  berubah: ['Berubah', 'bg-amber-100 text-amber-800'],
  sama: ['Sudah ada', 'bg-slate-100 text-slate-600'],
  masalah: ['Bermasalah', 'bg-red-100 text-red-700'],
}
const counts = computed(() => (preview.value?.data || []).reduce((a, s) => ({ ...a, [s.state]: (a[s.state] || 0) + 1 }), {}))
const selectable = computed(() => (preview.value?.data || []).filter((s) => s.state !== 'masalah'))

async function loadKelas() {
  loadingKelas.value = true; apiError.value = ''
  try { kelasApi.value = (await axios.get('/superadmin/siswa/api/kelas')).data.data }
  catch (e) { apiError.value = e.response?.data?.message || 'Gagal memuat daftar kelas.' }
  finally { loadingKelas.value = false }
}
async function loadPreview() {
  if (!kelasId.value) return
  loadingPreview.value = true; apiError.value = ''; preview.value = null
  try {
    preview.value = (await axios.post('/superadmin/siswa/api/pratinjau', { kelas_id: kelasId.value })).data
    picked.value = preview.value.data.filter((s) => ['baru', 'berubah'].includes(s.state)).map((s) => s.nis)
  } catch (e) { apiError.value = e.response?.data?.message || 'Gagal menarik data siswa.' }
  finally { loadingPreview.value = false }
}
function pickAll(on) { picked.value = on ? selectable.value.map((s) => s.nis) : [] }
function doImport() {
  if (!picked.value.length) return
  importForm.kelas_id = kelasId.value
  importForm.nis = picked.value
  importForm.post('/superadmin/siswa/impor', { onSuccess: () => { preview.value = null; picked.value = [] } })
}
if (props.apiReady) loadKelas()

// ---- Daftar siswa lokal ----
const q = ref(props.filters?.q || '')
const kelas = ref(props.filters?.kelas || '')
function search() { router.get('/superadmin/siswa', { q: q.value || undefined, kelas: kelas.value || undefined }, { preserveState: true, replace: true }) }
function toggleActive(s) { router.put(`/superadmin/siswa/${s.id}`, { is_active: !s.is_active }, { preserveScroll: true }) }
const fmtDate = (d) => d ? new Date(d + 'T00:00:00').toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) : '-'
</script>

<template>
  <SuperappLayout variant="superadmin">
    <title>Data Siswa - TSM Superapp</title>
    <div class="space-y-6">
      <h1 class="font-display text-[28px] font-bold text-navy tracking-[-0.5px]"><span class="block font-mono text-[11px] font-normal tracking-[2px] uppercase text-label mb-1">TSM Superapp · Superadmin</span>Data Siswa</h1>

      <!-- Tarik dari Data Induk -->
      <div class="card p-6">
        <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
          <div>
            <h2 class="font-bold text-slate-800">Tarik dari Data Induk Siswa</h2>
            <p class="text-xs text-slate-500">Pilih kelas, periksa pratinjau, lalu simpan. Siswa login memakai NIS + tanggal lahir.</p>
          </div>
          <span class="text-[11px] font-mono px-2 py-1 rounded-full" :class="apiReady ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'">{{ apiReady ? 'API terhubung: ' + apiUrl : 'API belum diatur' }}</span>
        </div>

        <div v-if="!apiReady" class="rounded-2xl bg-amber-50 border border-amber-200 p-4 text-sm text-amber-800">
          Isi <code>INDUK_API_URL</code> dan <code>INDUK_API_TOKEN</code> di file <code>.env</code> server, lalu jalankan <code>php artisan config:clear</code>.
        </div>

        <template v-else>
          <div class="flex flex-wrap gap-3">
            <select v-model="kelasId" class="form-input text-sm w-auto min-w-[220px]" :disabled="loadingKelas">
              <option value="">{{ loadingKelas ? 'Memuat kelas…' : '— Pilih kelas —' }}</option>
              <option v-for="k in kelasApi" :key="k.id" :value="k.id">{{ k.nama }}<template v-if="k.jumlah_siswa"> ({{ k.jumlah_siswa }} siswa)</template></option>
            </select>
            <button @click="loadPreview" :disabled="!kelasId || loadingPreview" class="btn-primary text-sm py-2.5 disabled:opacity-50">{{ loadingPreview ? 'Menarik…' : 'Tarik Data' }}</button>
            <button @click="loadKelas" :disabled="loadingKelas" class="btn-ghost text-sm py-2.5">Muat ulang kelas</button>
          </div>
          <p v-if="apiError" class="mt-3 text-sm text-red-600">{{ apiError }}</p>

          <div v-if="preview" class="mt-6">
            <div class="flex flex-wrap items-center gap-2 mb-3 text-xs">
              <span class="font-bold text-slate-800 text-sm mr-2">{{ preview.kelas }} · {{ preview.data.length }} siswa</span>
              <span v-for="(v, k) in stateInfo" :key="k" v-show="counts[k]" class="px-2 py-0.5 rounded-full font-semibold" :class="v[1]">{{ v[0] }}: {{ counts[k] }}</span>
            </div>
            <div class="overflow-x-auto rounded-xl border border-line">
              <table class="w-full text-sm">
                <thead class="bg-slate-50 text-xs text-slate-500 text-left">
                  <tr>
                    <th class="p-3 w-8"><input type="checkbox" :checked="picked.length && picked.length === selectable.length" @change="pickAll($event.target.checked)" class="accent-blue-900" /></th>
                    <th class="p-3">NIS</th><th class="p-3">Nama</th><th class="p-3">L/P</th><th class="p-3">Tgl Lahir</th><th class="p-3">Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="s in preview.data" :key="s.nis + s.name" class="border-t border-line" :class="s.state === 'masalah' ? 'bg-red-50/50' : ''">
                    <td class="p-3"><input type="checkbox" :value="s.nis" v-model="picked" :disabled="s.state === 'masalah'" class="accent-blue-900" /></td>
                    <td class="p-3 font-mono text-xs">{{ s.nis || '-' }}</td>
                    <td class="p-3">{{ s.name }}<div v-if="s.status !== 'aktif'" class="text-[11px] text-amber-700">status induk: {{ s.status }} (akun nonaktif)</div></td>
                    <td class="p-3">{{ s.gender || '-' }}</td>
                    <td class="p-3 whitespace-nowrap">{{ fmtDate(s.birth_date) }}</td>
                    <td class="p-3">
                      <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full" :class="stateInfo[s.state][1]">{{ stateInfo[s.state][0] }}</span>
                      <div v-if="s.problem" class="text-[11px] text-red-600 mt-1">{{ s.problem }}</div>
                      <div v-else-if="s.kelas_lama" class="text-[11px] text-amber-700 mt-1">pindah dari {{ s.kelas_lama }}</div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div class="flex flex-wrap items-center gap-3 mt-4">
              <button @click="doImport" :disabled="!picked.length || importForm.processing" class="btn-primary text-sm py-2.5 disabled:opacity-50">{{ importForm.processing ? 'Menyimpan…' : `Simpan ${picked.length} siswa` }}</button>
              <button @click="preview = null" class="btn-ghost text-sm py-2.5">Batal</button>
              <span class="text-xs text-slate-500">Data diambil ulang dari API saat disimpan. Siswa yang sudah ada diperbarui (nama, kelas, tgl lahir).</span>
            </div>
          </div>
        </template>
      </div>

      <!-- Daftar siswa -->
      <div class="card p-6">
        <form @submit.prevent="search" class="flex flex-wrap gap-3 mb-5">
          <input v-model="q" placeholder="Cari nama / NIS / NISN" class="form-input text-sm flex-1 min-w-[200px]" />
          <select v-model="kelas" @change="search" class="form-input text-sm w-auto">
            <option value="">Semua kelas</option>
            <option v-for="k in kelasLokal" :key="k.kelas" :value="k.kelas">{{ k.kelas }} ({{ k.total }})</option>
          </select>
          <button class="btn-ghost text-sm py-2">Cari</button>
        </form>

        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="text-xs text-slate-500 text-left border-b border-line">
              <tr><th class="py-2 pr-3">NIS</th><th class="py-2 pr-3">Nama</th><th class="py-2 pr-3">Kelas</th><th class="py-2 pr-3">Tgl Lahir</th><th class="py-2 pr-3">Login terakhir</th><th class="py-2"></th></tr>
            </thead>
            <tbody>
              <tr v-for="s in students.data" :key="s.id" class="border-b border-line/60">
                <td class="py-2.5 pr-3 font-mono text-xs">{{ s.nis || '-' }}</td>
                <td class="py-2.5 pr-3">{{ s.name }} <span v-if="!s.is_active" class="ml-1 text-[10px] font-semibold px-1.5 py-0.5 rounded-full bg-red-100 text-red-700">Nonaktif</span></td>
                <td class="py-2.5 pr-3">{{ s.kelas || '-' }}</td>
                <td class="py-2.5 pr-3 whitespace-nowrap">{{ fmtDate(s.birth_date) }}</td>
                <td class="py-2.5 pr-3 text-xs text-slate-500">{{ s.last_login_at ? new Date(s.last_login_at).toLocaleString('id-ID') : 'belum pernah' }}</td>
                <td class="py-2.5 text-right"><button @click="toggleActive(s)" class="text-xs font-semibold" :class="s.is_active ? 'text-red-600' : 'text-emerald-700'">{{ s.is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button></td>
              </tr>
              <tr v-if="!students.data.length"><td colspan="6" class="py-8 text-center text-xs text-slate-400">Belum ada data siswa. Tarik dari Data Induk di atas.</td></tr>
            </tbody>
          </table>
        </div>
        <p class="text-xs text-slate-500 mt-3">Plotting fitur siswa (LMS, UKK, dll.) diatur di menu <Link href="/superadmin/pengguna?role=siswa" class="text-label font-semibold">Pengguna & Akses</Link>.</p>

        <div v-if="students.last_page > 1" class="flex flex-wrap gap-1 mt-5">
          <template v-for="l in students.links" :key="l.label">
            <Link v-if="l.url" :href="l.url" preserve-scroll class="px-3 py-1.5 rounded-lg text-xs" :class="l.active ? 'bg-navy text-white' : 'bg-slate-100 text-slate-600'" v-html="l.label" />
            <span v-else class="px-3 py-1.5 text-xs text-slate-300" v-html="l.label" />
          </template>
        </div>
      </div>
    </div>
  </SuperappLayout>
</template>
