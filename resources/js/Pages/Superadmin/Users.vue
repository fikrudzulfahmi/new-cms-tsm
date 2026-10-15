<script setup>
import { ref, computed } from 'vue'
import { Link, useForm, router, usePage } from '@inertiajs/vue3'
import SuperappLayout from '@/Layouts/SuperappLayout.vue'

const props = defineProps({ users: Object, features: Object, filters: Object })
const me = computed(() => usePage().props.auth?.user)
const featureList = Object.entries(props.features || {}).map(([key, f]) => ({ key, ...f }))
const roleLabel = { superadmin: 'Superadmin', guru: 'Guru', siswa: 'Siswa' }
const roleClass = { superadmin: 'bg-navy text-white', guru: 'bg-blue-100 text-blue-800', siswa: 'bg-emerald-100 text-emerald-800' }
const plotLabel = (p) => {
  const [f, r] = p.split(':')
  return `${props.features[f]?.name || f} · ${props.features[f]?.roles?.[r]?.label || r}`
}

// Peran fitur yang wajar per peran akun (siswa tidak bisa jadi pembimbing, dst.)
const allowed = (role, featureRole) => {
  if (role === 'siswa') return ['siswa', 'pembaca'].includes(featureRole)
  if (role === 'guru') return featureRole !== 'siswa'
  return false
}

const blank = { name: '', email: '', role: 'guru', password: '', is_active: true, plots: [] }
const form = useForm({ ...blank })
const editing = ref(null)
const showForm = ref(false)

function openCreate() { editing.value = null; form.defaults({ ...blank, plots: [] }); form.reset(); form.clearErrors(); showForm.value = true }
function openEdit(u) {
  editing.value = u
  form.defaults({ name: u.name, email: u.email, role: u.role, password: '', is_active: !!u.is_active, plots: [...u.plots] })
  form.reset(); form.clearErrors(); showForm.value = true
  window.scrollTo({ top: 0, behavior: 'smooth' })
}
function close() { showForm.value = false; editing.value = null }
function submit() {
  form.plots = form.plots.filter((p) => allowed(form.role, p.split(':')[1]))
  const opts = { preserveScroll: true, onSuccess: () => close() }
  editing.value ? form.put(`/superadmin/pengguna/${editing.value.id}`, opts) : form.post('/superadmin/pengguna', opts)
}
function destroy(u) { if (confirm(`Hapus akun ${u.name}?`)) router.delete(`/superadmin/pengguna/${u.id}`, { preserveScroll: true }) }

const q = ref(props.filters?.q || '')
const roleFilter = ref(props.filters?.role || '')
function search() { router.get('/superadmin/pengguna', { q: q.value || undefined, role: roleFilter.value || undefined }, { preserveState: true, replace: true }) }
</script>

<template>
  <SuperappLayout variant="superadmin">
    <title>Pengguna & Akses - TSM Superapp</title>
    <div class="space-y-6">
      <div class="flex flex-wrap items-end justify-between gap-4">
        <h1 class="font-display text-[28px] font-bold text-navy tracking-[-0.5px]"><span class="block font-mono text-[11px] font-normal tracking-[2px] uppercase text-label mb-1">TSM Superapp · Superadmin</span>Pengguna & Akses</h1>
        <button v-if="!showForm" @click="openCreate" class="btn-primary text-sm py-2.5">+ Tambah Akun</button>
      </div>
      <p class="text-sm text-slate-500 -mt-3">Buat akun guru/siswa dan tentukan fitur yang bisa mereka akses. Card fitur di portal hanya muncul untuk fitur yang di-plot. Akun Admin CMS (/admin) dikelola terpisah.</p>

      <!-- Form -->
      <div v-if="showForm" class="card p-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="font-bold text-slate-800">{{ editing ? `Ubah Akun: ${editing.name}` : 'Tambah Akun' }}</h2>
          <button @click="close" class="text-xs font-semibold text-slate-500 hover:text-slate-800">Batal</button>
        </div>
        <form @submit.prevent="submit" class="space-y-6">
          <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
              <label class="form-label">Nama</label>
              <input v-model="form.name" class="form-input text-sm" required />
              <p v-if="form.errors.name" class="text-xs text-red-600 mt-1">{{ form.errors.name }}</p>
            </div>
            <div>
              <label class="form-label">Email (untuk login)</label>
              <input v-model="form.email" type="email" class="form-input text-sm" required />
              <p v-if="form.errors.email" class="text-xs text-red-600 mt-1">{{ form.errors.email }}</p>
            </div>
            <div>
              <label class="form-label">Peran</label>
              <select v-model="form.role" class="form-input text-sm">
                <option value="guru">Guru</option>
                <option value="siswa">Siswa</option>
                <option value="superadmin">Superadmin</option>
              </select>
            </div>
            <div>
              <label class="form-label">Password {{ editing ? '(kosongkan bila tetap)' : '' }}</label>
              <input v-model="form.password" type="password" autocomplete="new-password" minlength="8" class="form-input text-sm" :required="!editing" />
              <p v-if="form.errors.password" class="text-xs text-red-600 mt-1">{{ form.errors.password }}</p>
            </div>
          </div>
          <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
            <input v-model="form.is_active" type="checkbox" class="w-4 h-4 accent-blue-900" /> Akun aktif
          </label>

          <div v-if="form.role === 'superadmin'" class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-600">Superadmin otomatis bisa mengakses dan mengatur semua fitur.</div>
          <div v-else>
            <div class="form-label mb-2">Plotting Fitur</div>
            <div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4">
              <div v-for="f in featureList" :key="f.key" class="rounded-2xl border border-line p-4">
                <div class="font-bold text-navy text-sm mb-2">{{ f.name }}</div>
                <template v-for="(r, rk) in f.roles" :key="rk">
                  <label v-if="allowed(form.role, rk)" class="flex items-center gap-2 text-sm text-slate-600 py-1 cursor-pointer">
                    <input type="checkbox" :value="`${f.key}:${rk}`" v-model="form.plots" class="w-4 h-4 accent-blue-900" />
                    {{ r.label }}
                  </label>
                </template>
              </div>
            </div>
          </div>

          <div class="flex gap-2">
            <button type="submit" :disabled="form.processing" class="btn-primary text-sm py-2.5">{{ editing ? 'Simpan Perubahan' : 'Buat Akun' }}</button>
            <button type="button" @click="close" class="btn-ghost text-sm py-2.5">Batal</button>
          </div>
        </form>
      </div>

      <!-- Daftar -->
      <div class="card p-6">
        <form @submit.prevent="search" class="flex flex-wrap gap-3 mb-5">
          <input v-model="q" placeholder="Cari nama / email" class="form-input text-sm flex-1 min-w-[200px]" />
          <select v-model="roleFilter" @change="search" class="form-input text-sm w-auto">
            <option value="">Semua peran</option>
            <option value="superadmin">Superadmin</option>
            <option value="guru">Guru</option>
            <option value="siswa">Siswa</option>
          </select>
          <button class="btn-ghost text-sm py-2">Cari</button>
        </form>

        <div class="space-y-3">
          <div v-for="u in users.data" :key="u.id" class="flex flex-wrap items-start justify-between gap-3 p-4 rounded-xl bg-slate-50" :class="editing?.id === u.id ? 'ring-2 ring-blue-400' : ''">
            <div class="min-w-0 space-y-1.5">
              <div class="flex flex-wrap items-center gap-2">
                <span class="font-bold text-sm text-slate-800">{{ u.name }}</span>
                <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full" :class="roleClass[u.role]">{{ roleLabel[u.role] }}</span>
                <span v-if="!u.is_active" class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-red-100 text-red-700">Nonaktif</span>
              </div>
              <div class="text-xs text-slate-500">{{ u.email }}<template v-if="u.last_login_at"> · login terakhir {{ new Date(u.last_login_at).toLocaleString('id-ID') }}</template></div>
              <div class="flex flex-wrap gap-1.5">
                <span v-if="u.role === 'superadmin'" class="text-[11px] px-2 py-0.5 rounded-full bg-white border border-line text-slate-600">Semua fitur</span>
                <span v-for="p in u.plots" :key="p" class="text-[11px] px-2 py-0.5 rounded-full bg-white border border-line text-slate-600">{{ plotLabel(p) }}</span>
                <span v-if="u.role !== 'superadmin' && !u.plots.length" class="text-[11px] text-amber-700">Belum di-plot ke fitur</span>
              </div>
            </div>
            <div class="flex items-center gap-2 text-xs font-semibold">
              <button @click="openEdit(u)" class="px-3 py-1.5 rounded-lg bg-blue-50 text-blue-800 hover:bg-blue-100">Ubah</button>
              <button v-if="u.id !== me?.id" @click="destroy(u)" class="text-red-600 hover:underline px-1">Hapus</button>
            </div>
          </div>
          <div v-if="!users.data.length" class="text-slate-400 text-xs text-center py-8">Belum ada akun</div>
        </div>

        <div v-if="users.last_page > 1" class="flex flex-wrap gap-1 mt-5">
          <template v-for="l in users.links" :key="l.label">
            <Link v-if="l.url" :href="l.url" preserve-scroll class="px-3 py-1.5 rounded-lg text-xs" :class="l.active ? 'bg-navy text-white' : 'bg-slate-100 text-slate-600'" v-html="l.label" />
            <span v-else class="px-3 py-1.5 text-xs text-slate-300" v-html="l.label" />
          </template>
        </div>
      </div>
    </div>
  </SuperappLayout>
</template>
