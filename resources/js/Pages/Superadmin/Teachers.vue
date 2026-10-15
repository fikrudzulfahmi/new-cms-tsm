<script setup>
import { ref } from 'vue'
import { Link, useForm, router, usePage } from '@inertiajs/vue3'
import SuperappLayout from '@/Layouts/SuperappLayout.vue'

const props = defineProps({ teachers: Object, filters: Object })
const flash = () => usePage().props.flash || {}

const blank = { name: '', niy: '', gender: 'L', phone: '', email: '', address: '', password: '', is_active: true }
const form = useForm({ ...blank })
const editing = ref(null)
const showForm = ref(false)
const showPass = ref(false)

function openCreate() {
  editing.value = null
  form.defaults({ ...blank }); form.reset(); form.clearErrors()
  showForm.value = true
}
function openEdit(t) {
  editing.value = t
  form.defaults({ name: t.name, niy: t.niy || '', gender: t.gender || 'L', phone: t.phone || '', email: t.email || '', address: t.address || '', password: '', is_active: !!t.is_active })
  form.reset(); form.clearErrors(); showForm.value = true
  window.scrollTo({ top: 0, behavior: 'smooth' })
}
function close() { showForm.value = false; editing.value = null }
function submit() {
  const opts = { preserveScroll: true, onSuccess: () => close(), onFinish: () => form.reset('password') }
  editing.value ? form.put(`/superadmin/guru/${editing.value.id}`, opts) : form.post('/superadmin/guru', opts)
}
function destroy(t) {
  if (confirm(`Hapus data guru ${t.name}? Akun login dan plotting fiturnya ikut terhapus.`)) router.delete(`/superadmin/guru/${t.id}`, { preserveScroll: true })
}
function genPass() {
  const c = 'abcdefghjkmnpqrstuvwxyz23456789'
  form.password = Array.from(crypto.getRandomValues(new Uint32Array(10)), (n) => c[n % c.length]).join('')
  showPass.value = true
}

const q = ref(props.filters?.q || '')
function search() { router.get('/superadmin/guru', { q: q.value || undefined }, { preserveState: true, replace: true }) }
const fmtPhone = (p) => (p ? p.replace(/^(\d{4})(\d{4})(\d+)$/, '$1-$2-$3') : '')
</script>

<template>
  <SuperappLayout variant="superadmin">
    <title>Data Guru - TSM Superapp</title>
    <div class="space-y-6">
      <div class="flex flex-wrap items-end justify-between gap-4">
        <h1 class="font-display text-[28px] font-bold text-navy tracking-[-0.5px]"><span class="block font-mono text-[11px] font-normal tracking-[2px] uppercase text-label mb-1">TSM Superapp · Superadmin</span>Data Guru</h1>
        <button v-if="!showForm" @click="openCreate" class="btn-primary text-sm py-2.5">+ Tambah Guru</button>
      </div>
      <p class="text-sm text-slate-500 -mt-3">Data guru diisi manual. Guru masuk di halaman <b>/masuk</b> tab <b>Guru / Staf</b> memakai <b>email atau no HP</b> dan password yang dibuat di sini. Guru dimasukkan ke fitur (LMS, PKL, UKK, Perpus) oleh admin fitur masing-masing. Akun admin fitur dibuat terpisah di menu <Link href="/superadmin/admin-fitur" class="text-label font-semibold">Admin Fitur</Link>.</p>

      <div v-if="flash().success" class="rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm px-4 py-3">{{ flash().success }}</div>

      <!-- Form -->
      <div v-if="showForm" class="card p-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="font-bold text-slate-800">{{ editing ? `Ubah Data Guru: ${editing.name}` : 'Tambah Guru' }}</h2>
          <button @click="close" class="text-xs font-semibold text-slate-500 hover:text-slate-800">Batal</button>
        </div>
        <form @submit.prevent="submit" class="space-y-5">
          <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div class="lg:col-span-2">
              <label class="form-label">Nama lengkap</label>
              <input v-model="form.name" class="form-input text-sm" placeholder="mis. Ahmad Fauzi, S.Pd." required />
              <p v-if="form.errors.name" class="text-xs text-red-600 mt-1">{{ form.errors.name }}</p>
            </div>
            <div>
              <label class="form-label">NIY</label>
              <input v-model="form.niy" class="form-input text-sm" placeholder="Nomor Induk Yayasan" />
              <p v-if="form.errors.niy" class="text-xs text-red-600 mt-1">{{ form.errors.niy }}</p>
            </div>
            <div>
              <label class="form-label">Jenis kelamin</label>
              <select v-model="form.gender" class="form-input text-sm">
                <option value="L">Laki-laki</option>
                <option value="P">Perempuan</option>
              </select>
              <p v-if="form.errors.gender" class="text-xs text-red-600 mt-1">{{ form.errors.gender }}</p>
            </div>
            <div>
              <label class="form-label">No HP <span class="text-slate-400 font-normal">(untuk login)</span></label>
              <input v-model="form.phone" type="tel" inputmode="tel" class="form-input text-sm" placeholder="0812xxxxxxxx" />
              <p v-if="form.errors.phone" class="text-xs text-red-600 mt-1">{{ form.errors.phone }}</p>
            </div>
            <div>
              <label class="form-label">Email <span class="text-slate-400 font-normal">(untuk login)</span></label>
              <input v-model="form.email" type="email" class="form-input text-sm" placeholder="nama@gmail.com" />
              <p v-if="form.errors.email" class="text-xs text-red-600 mt-1">{{ form.errors.email }}</p>
            </div>
            <div class="sm:col-span-2 lg:col-span-3">
              <label class="form-label">Alamat</label>
              <textarea v-model="form.address" rows="2" class="form-input text-sm" placeholder="Alamat rumah"></textarea>
              <p v-if="form.errors.address" class="text-xs text-red-600 mt-1">{{ form.errors.address }}</p>
            </div>
            <div>
              <label class="form-label">Password {{ editing ? '(kosongkan bila tidak diganti)' : '' }}</label>
              <div class="flex gap-2">
                <input v-model="form.password" :type="showPass ? 'text' : 'password'" class="form-input text-sm" :required="!editing" minlength="8" autocomplete="new-password" placeholder="min. 8 karakter" />
                <button type="button" @click="showPass = !showPass" class="text-xs font-semibold text-slate-500 px-2">{{ showPass ? 'Sembunyi' : 'Lihat' }}</button>
              </div>
              <button type="button" @click="genPass" class="text-xs font-semibold text-label mt-1">Buatkan password acak</button>
              <p v-if="form.errors.password" class="text-xs text-red-600 mt-1">{{ form.errors.password }}</p>
            </div>
            <div class="flex items-center">
              <label class="inline-flex items-center gap-2 text-sm text-slate-700"><input v-model="form.is_active" type="checkbox" class="rounded border-slate-300"> Akun aktif (boleh login)</label>
            </div>
          </div>
          <p class="text-xs text-slate-500">Isi minimal salah satu dari no HP atau email. Catat password-nya lalu berikan ke guru yang bersangkutan.</p>
          <div class="flex gap-3">
            <button type="submit" :disabled="form.processing" class="btn-primary text-sm py-2.5">{{ form.processing ? 'Menyimpan…' : (editing ? 'Simpan Perubahan' : 'Simpan Guru') }}</button>
            <button type="button" @click="close" class="text-sm font-semibold text-slate-500">Batal</button>
          </div>
        </form>
      </div>

      <!-- Daftar -->
      <div class="card p-6">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
          <h2 class="font-bold text-slate-800">Daftar Guru <span class="text-slate-400 font-normal text-sm">({{ teachers.total }})</span></h2>
          <form @submit.prevent="search" class="flex gap-2">
            <input v-model="q" class="form-input text-sm w-64" placeholder="Cari nama, NIY, email, no HP" />
            <button class="btn-primary text-sm py-2">Cari</button>
          </form>
        </div>

        <div v-if="!teachers.data.length" class="text-sm text-slate-500 py-10 text-center">
          {{ filters?.q ? 'Tidak ada guru yang cocok.' : 'Belum ada data guru. Klik “+ Tambah Guru”.' }}
        </div>
        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-left text-xs uppercase tracking-wide text-slate-500 border-b">
                <th class="py-2 pr-3">Nama</th>
                <th class="py-2 pr-3">NIY</th>
                <th class="py-2 pr-3">L/P</th>
                <th class="py-2 pr-3">No HP</th>
                <th class="py-2 pr-3">Email</th>
                <th class="py-2 pr-3">Alamat</th>
                <th class="py-2 pr-3">Status</th>
                <th class="py-2"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="t in teachers.data" :key="t.id" class="border-b last:border-0 align-top">
                <td class="py-2.5 pr-3 font-semibold text-slate-800">{{ t.name }}</td>
                <td class="py-2.5 pr-3 font-mono text-xs">{{ t.niy || '–' }}</td>
                <td class="py-2.5 pr-3">{{ t.gender || '–' }}</td>
                <td class="py-2.5 pr-3 font-mono text-xs whitespace-nowrap">{{ fmtPhone(t.phone) || '–' }}</td>
                <td class="py-2.5 pr-3 text-xs">{{ t.email || '–' }}</td>
                <td class="py-2.5 pr-3 text-xs text-slate-600 max-w-[220px]">{{ t.address || '–' }}</td>
                <td class="py-2.5 pr-3">
                  <span :class="['text-[11px] font-semibold rounded-full px-2 py-0.5', t.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600']">{{ t.is_active ? 'Aktif' : 'Nonaktif' }}</span>
                </td>
                <td class="py-2.5 text-right whitespace-nowrap">
                  <button @click="openEdit(t)" class="text-xs font-semibold text-label mr-3">Ubah</button>
                  <button @click="destroy(t)" class="text-xs font-semibold text-red-600">Hapus</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="teachers.last_page > 1" class="flex flex-wrap gap-1 mt-4">
          <template v-for="l in teachers.links" :key="l.label">
            <Link v-if="l.url" :href="l.url" preserve-scroll :class="['px-3 py-1 rounded text-xs', l.active ? 'bg-navy text-white' : 'bg-slate-100 text-slate-600']" v-html="l.label" />
            <span v-else class="px-3 py-1 rounded text-xs text-slate-300" v-html="l.label" />
          </template>
        </div>
      </div>
    </div>
  </SuperappLayout>
</template>
