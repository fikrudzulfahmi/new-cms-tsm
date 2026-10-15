<script setup>
import { ref } from 'vue'
import { Link, useForm, router } from '@inertiajs/vue3'
import SuperappLayout from '@/Layouts/SuperappLayout.vue'

defineProps({ features: Array })

// form: tambah (feature diisi) atau ubah (editId diisi)
const openKey = ref(null)
const editId = ref(null)
const form = useForm({ feature: '', name: '', email: '', phone: '', password: '', is_active: true })
const showPass = ref(false)

function openAdd(f) {
  editId.value = null; openKey.value = f.key
  form.defaults({ feature: f.key, name: '', email: '', phone: '', password: '', is_active: true })
  form.reset(); form.clearErrors(); showPass.value = false
}
function openEdit(f, a) {
  editId.value = a.id; openKey.value = f.key
  form.defaults({ feature: f.key, name: a.name, email: a.email || '', phone: a.phone || '', password: '', is_active: !!a.is_active })
  form.reset(); form.clearErrors(); showPass.value = false
}
function close() { openKey.value = null; editId.value = null }
function submit() {
  const opts = { preserveScroll: true, onSuccess: close, onFinish: () => form.reset('password') }
  editId.value ? form.put(`/superadmin/admin-fitur/akun/${editId.value}`, opts) : form.post('/superadmin/admin-fitur', opts)
}
function remove(f, a) {
  if (confirm(`Hapus akun admin ${a.name} dari ${f.adminLabel}?`)) router.delete(`/superadmin/admin-fitur/${f.key}/${a.id}`, { preserveScroll: true })
}
function toggleActive(a) {
  router.put(`/superadmin/admin-fitur/akun/${a.id}`, { is_active: !a.is_active }, { preserveScroll: true })
}
function genPass() {
  const c = 'abcdefghjkmnpqrstuvwxyz23456789'
  form.password = Array.from(crypto.getRandomValues(new Uint32Array(10)), (n) => c[n % c.length]).join('')
  showPass.value = true
}
</script>

<template>
  <SuperappLayout variant="superadmin">
    <title>Admin Fitur - TSM Superapp</title>
    <div class="space-y-6">
      <h1 class="font-display text-[28px] font-bold text-navy tracking-[-0.5px]"><span class="block font-mono text-[11px] font-normal tracking-[2px] uppercase text-label mb-1">TSM Superapp · Superadmin</span>Admin Fitur</h1>
      <p class="text-sm text-slate-500 -mt-3 max-w-3xl">Buat <b>akun khusus admin</b> untuk tiap fitur (terpisah dari akun guru). Admin login di <b>/masuk</b> tab Guru / Staf dengan email atau no HP + password, lalu langsung masuk ke halaman fiturnya untuk <b>menambahkan anggota</b> (guru, pembimbing, penguji, siswa).</p>

      <div class="grid lg:grid-cols-2 gap-5">
        <div v-for="f in features" :key="f.key" class="card p-6 flex flex-col gap-4">
          <div class="flex items-start gap-3">
            <div class="w-11 h-11 rounded-2xl bg-navy text-accent flex items-center justify-center shrink-0">
              <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" :d="f.icon" /></svg>
            </div>
            <div class="flex-1">
              <div class="font-display text-lg font-bold text-navy">{{ f.name }}</div>
              <div class="text-xs text-slate-500">{{ f.full }} · {{ f.members }} anggota</div>
            </div>
            <Link :href="`/app/${f.key}/admin`" class="text-xs font-semibold text-label whitespace-nowrap">Lihat anggota →</Link>
          </div>

          <div>
            <div class="font-mono text-[10px] tracking-[2px] uppercase text-slate-500 mb-2">{{ f.adminLabel }}</div>
            <div v-if="!f.admins.length" class="text-sm text-amber-700 bg-amber-50 rounded-xl px-3 py-2">Belum ada akun admin.</div>
            <ul v-else class="divide-y">
              <li v-for="a in f.admins" :key="a.id" class="py-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
                <div class="flex-1 min-w-0">
                  <div class="font-semibold text-slate-800 truncate">{{ a.name }}
                    <span v-if="!a.is_active" class="ml-1 text-[10px] font-semibold rounded-full px-1.5 py-0.5 bg-red-100 text-red-700">Nonaktif</span>
                  </div>
                  <div class="text-xs text-slate-500 truncate">{{ [a.email, a.phone].filter(Boolean).join(' · ') || '—' }}</div>
                </div>
                <template v-if="a.role === 'staf'">
                  <button @click="openEdit(f, a)" class="text-xs font-semibold text-label">Ubah</button>
                  <button @click="toggleActive(a)" class="text-xs font-semibold text-slate-500">{{ a.is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                </template>
                <button @click="remove(f, a)" class="text-xs font-semibold text-red-600">Hapus</button>
              </li>
            </ul>
          </div>

          <button v-if="openKey !== f.key" @click="openAdd(f)" class="self-start text-sm font-semibold text-label">+ Buat akun admin {{ f.name }}</button>

          <form v-else @submit.prevent="submit" class="rounded-2xl bg-slate-50 p-4 space-y-3">
            <div class="font-semibold text-sm text-slate-700">{{ editId ? 'Ubah akun admin' : `Akun admin ${f.name} baru` }}</div>
            <div class="grid sm:grid-cols-2 gap-3">
              <div class="sm:col-span-2">
                <label class="form-label">Nama</label>
                <input v-model="form.name" class="form-input text-sm" :placeholder="`mis. ${f.adminLabel}`" required />
                <p v-if="form.errors.name" class="text-xs text-red-600 mt-1">{{ form.errors.name }}</p>
              </div>
              <div>
                <label class="form-label">Email</label>
                <input v-model="form.email" type="email" class="form-input text-sm" />
                <p v-if="form.errors.email" class="text-xs text-red-600 mt-1">{{ form.errors.email }}</p>
              </div>
              <div>
                <label class="form-label">No HP</label>
                <input v-model="form.phone" type="tel" inputmode="tel" class="form-input text-sm" placeholder="0812xxxxxxxx" />
                <p v-if="form.errors.phone" class="text-xs text-red-600 mt-1">{{ form.errors.phone }}</p>
              </div>
              <div class="sm:col-span-2">
                <label class="form-label">Password {{ editId ? '(kosongkan bila tidak diganti)' : '' }}</label>
                <div class="flex gap-2">
                  <input v-model="form.password" :type="showPass ? 'text' : 'password'" minlength="8" :required="!editId" autocomplete="new-password" class="form-input text-sm" placeholder="min. 8 karakter" />
                  <button type="button" @click="showPass = !showPass" class="text-xs font-semibold text-slate-500 px-2">{{ showPass ? 'Sembunyi' : 'Lihat' }}</button>
                </div>
                <button type="button" @click="genPass" class="text-xs font-semibold text-label mt-1">Buatkan password acak</button>
                <p v-if="form.errors.password" class="text-xs text-red-600 mt-1">{{ form.errors.password }}</p>
              </div>
            </div>
            <p class="text-xs text-slate-500">Isi minimal email atau no HP (untuk login). Catat password lalu berikan ke admin yang bersangkutan.</p>
            <div class="flex gap-3">
              <button type="submit" :disabled="form.processing" class="btn-primary text-sm py-2">{{ form.processing ? 'Menyimpan…' : (editId ? 'Simpan' : 'Buat akun admin') }}</button>
              <button type="button" @click="close" class="text-sm font-semibold text-slate-500">Batal</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </SuperappLayout>
</template>
