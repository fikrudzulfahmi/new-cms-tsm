<script setup>
import { ref, computed } from 'vue'
import { Link, useForm, router } from '@inertiajs/vue3'
import SuperappLayout from '@/Layouts/SuperappLayout.vue'

const props = defineProps({ features: Array, teachers: Array })

const openKey = ref(null)
const form = useForm({ feature: '', mode: 'existing', user_id: '', name: '', email: '', phone: '', password: '' })
const showPass = ref(false)
const pick = ref('')

function openAdd(f) {
  openKey.value = f.key
  form.reset(); form.clearErrors()
  form.feature = f.key
  form.mode = props.teachers.length ? 'existing' : 'new'
  pick.value = ''
}
const candidates = computed(() => {
  const f = props.features.find((x) => x.key === openKey.value)
  const taken = new Set((f?.admins || []).map((a) => a.id))
  const q = pick.value.toLowerCase()
  return props.teachers.filter((t) => !taken.has(t.id) && (!q || t.name.toLowerCase().includes(q))).slice(0, 50)
})
function submit() {
  form.post('/superadmin/admin-fitur', { preserveScroll: true, onSuccess: () => (openKey.value = null), onFinish: () => form.reset('password') })
}
function revoke(f, a) {
  const extra = a.role === 'staf' ? ' Akun khusus admin ini akan dihapus bila tidak memegang fitur lain.' : ''
  if (confirm(`Cabut ${a.name} dari ${f.adminLabel}?${extra}`)) router.delete(`/superadmin/admin-fitur/${f.key}/${a.id}`, { preserveScroll: true })
}
function resetPass(a) {
  const p = prompt(`Password baru untuk ${a.name} (min. 8 karakter):`)
  if (p) router.put(`/superadmin/admin-fitur/akun/${a.id}`, { password: p, is_active: !!a.is_active }, { preserveScroll: true })
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
      <p class="text-sm text-slate-500 -mt-3 max-w-3xl">Tunjuk admin untuk tiap fitur. Admin bisa diambil dari <b>Data Guru</b> atau dibuatkan <b>akun khusus</b>. Setelah itu, <b>admin fitur yang menambahkan anggota</b> (guru, pembimbing, penguji, siswa) yang boleh mengakses fiturnya. Superadmin tetap bisa membuka halaman anggota tiap fitur.</p>

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
            <div v-if="!f.admins.length" class="text-sm text-amber-700 bg-amber-50 rounded-xl px-3 py-2">Belum ada admin.</div>
            <ul v-else class="divide-y">
              <li v-for="a in f.admins" :key="a.id" class="py-2 flex items-center gap-3 text-sm">
                <div class="flex-1 min-w-0">
                  <div class="font-semibold text-slate-800 truncate">{{ a.name }}
                    <span :class="['ml-1 text-[10px] font-semibold rounded-full px-1.5 py-0.5', a.role === 'guru' ? 'bg-blue-100 text-blue-800' : 'bg-slate-200 text-slate-700']">{{ a.role === 'guru' ? 'Guru' : 'Akun khusus' }}</span>
                    <span v-if="!a.is_active" class="ml-1 text-[10px] font-semibold rounded-full px-1.5 py-0.5 bg-red-100 text-red-700">Nonaktif</span>
                  </div>
                  <div class="text-xs text-slate-500 truncate">{{ [a.email, a.phone].filter(Boolean).join(' · ') || '—' }}</div>
                </div>
                <template v-if="a.role === 'staf'">
                  <button @click="resetPass(a)" class="text-xs font-semibold text-slate-500">Ganti password</button>
                  <button @click="toggleActive(a)" class="text-xs font-semibold text-slate-500">{{ a.is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                </template>
                <button @click="revoke(f, a)" class="text-xs font-semibold text-red-600">Cabut</button>
              </li>
            </ul>
          </div>

          <button v-if="openKey !== f.key" @click="openAdd(f)" class="self-start text-sm font-semibold text-label">+ Tambah admin {{ f.name }}</button>

          <form v-else @submit.prevent="submit" class="rounded-2xl bg-slate-50 p-4 space-y-3">
            <div class="flex gap-2 text-xs font-semibold">
              <button type="button" @click="form.mode = 'existing'" :class="['px-3 py-1.5 rounded-full', form.mode === 'existing' ? 'bg-navy text-white' : 'bg-white text-slate-600']">Pilih dari Data Guru</button>
              <button type="button" @click="form.mode = 'new'" :class="['px-3 py-1.5 rounded-full', form.mode === 'new' ? 'bg-navy text-white' : 'bg-white text-slate-600']">Buat akun khusus</button>
            </div>

            <template v-if="form.mode === 'existing'">
              <div v-if="!teachers.length" class="text-sm text-slate-500">Belum ada data guru. Tambahkan di <Link href="/superadmin/guru" class="text-label font-semibold">Data Guru</Link>, atau buat akun khusus.</div>
              <template v-else>
                <input v-model="pick" class="form-input text-sm" placeholder="Cari nama guru…" />
                <select v-model="form.user_id" size="6" class="form-input text-sm">
                  <option v-for="t in candidates" :key="t.id" :value="t.id">{{ t.name }}{{ t.email || t.phone ? ' — ' + (t.email || t.phone) : '' }}</option>
                </select>
                <p class="text-xs text-slate-500">Guru tetap login dengan email/no HP + password miliknya.</p>
              </template>
              <p v-if="form.errors.user_id" class="text-xs text-red-600">{{ form.errors.user_id }}</p>
            </template>

            <template v-else>
              <div class="grid sm:grid-cols-2 gap-3">
                <div class="sm:col-span-2">
                  <label class="form-label">Nama akun</label>
                  <input v-model="form.name" class="form-input text-sm" :placeholder="`mis. ${f.adminLabel}`" />
                  <p v-if="form.errors.name" class="text-xs text-red-600 mt-1">{{ form.errors.name }}</p>
                </div>
                <div>
                  <label class="form-label">Email</label>
                  <input v-model="form.email" type="email" class="form-input text-sm" />
                  <p v-if="form.errors.email" class="text-xs text-red-600 mt-1">{{ form.errors.email }}</p>
                </div>
                <div>
                  <label class="form-label">No HP</label>
                  <input v-model="form.phone" type="tel" class="form-input text-sm" placeholder="0812xxxxxxxx" />
                  <p v-if="form.errors.phone" class="text-xs text-red-600 mt-1">{{ form.errors.phone }}</p>
                </div>
                <div class="sm:col-span-2">
                  <label class="form-label">Password</label>
                  <div class="flex gap-2">
                    <input v-model="form.password" :type="showPass ? 'text' : 'password'" minlength="8" autocomplete="new-password" class="form-input text-sm" placeholder="min. 8 karakter" />
                    <button type="button" @click="showPass = !showPass" class="text-xs font-semibold text-slate-500 px-2">{{ showPass ? 'Sembunyi' : 'Lihat' }}</button>
                  </div>
                  <button type="button" @click="genPass" class="text-xs font-semibold text-label mt-1">Buatkan password acak</button>
                  <p v-if="form.errors.password" class="text-xs text-red-600 mt-1">{{ form.errors.password }}</p>
                </div>
              </div>
              <p class="text-xs text-slate-500">Isi minimal email atau no HP. Akun ini login di /masuk (tab Guru / Staf) dan langsung masuk ke admin {{ f.name }}.</p>
            </template>

            <div class="flex gap-3">
              <button type="submit" :disabled="form.processing" class="btn-primary text-sm py-2">{{ form.processing ? 'Menyimpan…' : 'Jadikan admin' }}</button>
              <button type="button" @click="openKey = null" class="text-sm font-semibold text-slate-500">Batal</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </SuperappLayout>
</template>
