<script setup>
import { ref, computed } from 'vue'
import { Link, useForm, router } from '@inertiajs/vue3'
import SuperappLayout from '@/Layouts/SuperappLayout.vue'

const props = defineProps({
  feature: Object, roles: Array, counts: Object, members: Object,
  teachers: Array, classes: Object, filters: Object, layout: String,
})

const roleLabel = Object.fromEntries(props.roles.map((r) => [r.key, r.label]))
const showAdd = ref(false)
const form = useForm({ role: props.roles[0]?.key || '', user_ids: [], kelas: [], nis: '' })
const cur = computed(() => props.roles.find((r) => r.key === form.role) || { accounts: [] })
const allowGuru = computed(() => cur.value.accounts.includes('guru'))
const allowSiswa = computed(() => cur.value.accounts.includes('siswa'))
const tq = ref('')
const teacherList = computed(() => {
  const q = tq.value.toLowerCase()
  return props.teachers.filter((t) => !q || t.name.toLowerCase().includes(q) || (t.niy || '').toLowerCase().includes(q))
})
function submit() {
  if (!allowGuru.value) form.user_ids = []
  if (!allowSiswa.value) { form.kelas = []; form.nis = '' }
  form.post(`/app/${props.feature.key}/admin/anggota`, { preserveScroll: true, onSuccess: () => { form.reset('user_ids', 'kelas', 'nis'); showAdd.value = false } })
}

// pilih & keluarkan
const selected = ref([])
const key = (m) => `${m.feature_role}:${m.id}`
const allChecked = computed(() => props.members.data.length && props.members.data.every((m) => selected.value.includes(key(m))))
function toggleAll() { selected.value = allChecked.value ? [] : props.members.data.map(key) }
function removeSelected() {
  const byRole = {}
  for (const k of selected.value) { const [r, id] = k.split(':'); (byRole[r] ||= []).push(+id) }
  if (!confirm(`Keluarkan ${selected.value.length} anggota dari ${props.feature.name}?`)) return
  Object.entries(byRole).forEach(([role, user_ids], i, arr) =>
    router.delete(`/app/${props.feature.key}/admin/anggota`, { data: { role, user_ids }, preserveScroll: true, onSuccess: () => { if (i === arr.length - 1) selected.value = [] } }))
}
function removeOne(m) {
  if (confirm(`Keluarkan ${m.name} (${roleLabel[m.feature_role]}) dari ${props.feature.name}?`))
    router.delete(`/app/${props.feature.key}/admin/anggota`, { data: { role: m.feature_role, user_ids: [m.id] }, preserveScroll: true })
}

// filter daftar
const q = ref(props.filters?.q || '')
const fRole = ref(props.filters?.role || '')
const fKelas = ref(props.filters?.kelas || '')
function search() {
  router.get(`/app/${props.feature.key}/admin`, { q: q.value || undefined, role: fRole.value || undefined, kelas: fKelas.value || undefined }, { preserveState: true, replace: true })
}
</script>

<template>
  <SuperappLayout :variant="layout">
    <title>Admin {{ feature.name }} - TSM Superapp</title>
    <div class="max-w-6xl mx-auto space-y-6">
      <Link v-if="layout === 'portal'" href="/portal" class="text-sm text-slate-500 hover:text-label">← Kembali ke Portal</Link>
      <div class="flex flex-wrap items-end justify-between gap-4">
        <h1 class="font-display text-[28px] font-bold text-navy tracking-[-0.5px]"><span class="block font-mono text-[11px] font-normal tracking-[2px] uppercase text-label mb-1">{{ feature.adminLabel }}</span>Anggota {{ feature.name }}</h1>
        <div class="flex gap-3 items-center">
          <Link :href="`/app/${feature.key}/rencana`" class="text-sm font-semibold text-slate-500 hover:text-label">Fitur admin lainnya (segera)</Link>
          <button v-if="!showAdd" @click="showAdd = true" class="btn-primary text-sm py-2.5">+ Tambah Anggota</button>
        </div>
      </div>
      <p class="text-sm text-slate-500 -mt-3">Hanya anggota di daftar ini yang melihat card <b>{{ feature.name }}</b> di portal mereka.</p>

      <div class="flex flex-wrap gap-3">
        <div v-for="r in roles" :key="r.key" class="card px-5 py-3">
          <div class="text-xs text-slate-500">{{ r.label }}</div>
          <div class="font-display text-2xl font-bold text-navy">{{ counts[r.key] || 0 }}</div>
        </div>
      </div>

      <!-- Tambah anggota -->
      <div v-if="showAdd" class="card p-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="font-bold text-slate-800">Tambah Anggota</h2>
          <button @click="showAdd = false" class="text-xs font-semibold text-slate-500">Batal</button>
        </div>
        <form @submit.prevent="submit" class="space-y-5">
          <div>
            <label class="form-label">Sebagai</label>
            <div class="flex flex-wrap gap-2">
              <button v-for="r in roles" :key="r.key" type="button" @click="form.role = r.key"
                :class="['px-4 py-2 rounded-full text-sm font-semibold', form.role === r.key ? 'bg-navy text-white' : 'bg-slate-100 text-slate-600']">{{ r.label }}</button>
            </div>
          </div>

          <div class="grid gap-5" :class="{ 'lg:grid-cols-2': allowGuru && allowSiswa }">
            <div v-if="allowGuru">
              <label class="form-label">Pilih guru <span class="text-slate-400 font-normal">({{ form.user_ids.length }} dipilih)</span></label>
              <input v-model="tq" class="form-input text-sm mb-2" placeholder="Cari nama / NIY" />
              <div class="max-h-64 overflow-y-auto rounded-xl border border-line divide-y bg-white">
                <label v-for="t in teacherList" :key="t.id" class="flex items-center gap-3 px-3 py-2 text-sm cursor-pointer hover:bg-slate-50">
                  <input type="checkbox" :value="t.id" v-model="form.user_ids" :disabled="t.roles.includes(form.role)" class="rounded border-slate-300" />
                  <span class="flex-1">{{ t.name }} <span v-if="t.niy" class="text-xs text-slate-400 font-mono">{{ t.niy }}</span></span>
                  <span v-if="t.roles.includes(form.role)" class="text-[10px] font-semibold text-emerald-700">sudah</span>
                </label>
                <div v-if="!teacherList.length" class="px-3 py-4 text-sm text-slate-500">Tidak ada guru. Data guru diisi superadmin.</div>
              </div>
            </div>

            <div v-if="allowSiswa" class="space-y-4">
              <div>
                <label class="form-label">Siswa per kelas <span class="text-slate-400 font-normal">(semua siswa aktif di kelas itu)</span></label>
                <div class="flex flex-wrap gap-2">
                  <label v-for="(n, k) in classes" :key="k" :class="['px-3 py-1.5 rounded-full text-sm cursor-pointer border', form.kelas.includes(k) ? 'bg-navy text-white border-navy' : 'bg-white text-slate-600 border-line']">
                    <input type="checkbox" :value="k" v-model="form.kelas" class="hidden" />{{ k }} <span class="opacity-60">({{ n }})</span>
                  </label>
                  <span v-if="!Object.keys(classes).length" class="text-sm text-slate-500">Belum ada data siswa.</span>
                </div>
              </div>
              <div>
                <label class="form-label">Atau siswa tertentu (NIS)</label>
                <textarea v-model="form.nis" rows="3" class="form-input text-sm font-mono" placeholder="32337, 32338&#10;atau satu NIS per baris"></textarea>
                <p class="text-xs text-slate-500 mt-1">Pakai 5 angka NIS (NIS lengkap seperti 32337/1862/041 juga bisa).</p>
              </div>
            </div>
          </div>
          <p v-if="form.errors.user_ids" class="text-sm text-red-600">{{ form.errors.user_ids }}</p>
          <p v-if="form.errors.role" class="text-sm text-red-600">{{ form.errors.role }}</p>
          <button type="submit" :disabled="form.processing" class="btn-primary text-sm py-2.5">{{ form.processing ? 'Menyimpan…' : 'Tambahkan' }}</button>
        </form>
      </div>

      <!-- Daftar anggota -->
      <div class="card p-6">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
          <h2 class="font-bold text-slate-800">Daftar Anggota <span class="text-slate-400 font-normal text-sm">({{ members.total }})</span></h2>
          <form @submit.prevent="search" class="flex flex-wrap gap-2">
            <select v-model="fRole" @change="search" class="form-input text-sm w-auto">
              <option value="">Semua peran</option>
              <option v-for="r in roles" :key="r.key" :value="r.key">{{ r.label }}</option>
            </select>
            <select v-model="fKelas" @change="search" class="form-input text-sm w-auto">
              <option value="">Semua kelas</option>
              <option v-for="(n, k) in classes" :key="k" :value="k">{{ k }}</option>
            </select>
            <input v-model="q" class="form-input text-sm w-48" placeholder="Nama / NIS / NIY" />
            <button class="btn-primary text-sm py-2">Cari</button>
          </form>
        </div>

        <div v-if="selected.length" class="mb-3 flex items-center gap-3 text-sm bg-red-50 rounded-xl px-4 py-2">
          <span>{{ selected.length }} dipilih</span>
          <button @click="removeSelected" class="font-semibold text-red-700">Keluarkan dari {{ feature.name }}</button>
          <button @click="selected = []" class="text-slate-500">Batal</button>
        </div>

        <div v-if="!members.data.length" class="text-sm text-slate-500 py-10 text-center">Belum ada anggota. Klik “+ Tambah Anggota”.</div>
        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-left text-xs uppercase tracking-wide text-slate-500 border-b">
                <th class="py-2 pr-3 w-8"><input type="checkbox" :checked="allChecked" @change="toggleAll" class="rounded border-slate-300" /></th>
                <th class="py-2 pr-3">Nama</th>
                <th class="py-2 pr-3">NIS / NIY</th>
                <th class="py-2 pr-3">Kelas</th>
                <th class="py-2 pr-3">Peran di {{ feature.name }}</th>
                <th class="py-2"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="m in members.data" :key="key(m)" class="border-b last:border-0">
                <td class="py-2 pr-3"><input type="checkbox" :value="key(m)" v-model="selected" class="rounded border-slate-300" /></td>
                <td class="py-2 pr-3 font-semibold text-slate-800">{{ m.name }} <span v-if="!m.is_active" class="text-[10px] text-red-600 font-semibold">nonaktif</span></td>
                <td class="py-2 pr-3 font-mono text-xs">{{ m.nis || m.niy || '–' }}</td>
                <td class="py-2 pr-3">{{ m.kelas || '–' }}</td>
                <td class="py-2 pr-3"><span class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-blue-50 text-blue-800">{{ roleLabel[m.feature_role] || m.feature_role }}</span></td>
                <td class="py-2 text-right"><button @click="removeOne(m)" class="text-xs font-semibold text-red-600">Keluarkan</button></td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="members.last_page > 1" class="flex flex-wrap gap-1 mt-4">
          <template v-for="l in members.links" :key="l.label">
            <Link v-if="l.url" :href="l.url" preserve-scroll :class="['px-3 py-1 rounded text-xs', l.active ? 'bg-navy text-white' : 'bg-slate-100 text-slate-600']" v-html="l.label" />
            <span v-else class="px-3 py-1 rounded text-xs text-slate-300" v-html="l.label" />
          </template>
        </div>
      </div>
    </div>
  </SuperappLayout>
</template>
