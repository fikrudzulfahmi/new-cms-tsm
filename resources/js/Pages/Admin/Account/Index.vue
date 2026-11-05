<script setup>
import { useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps(['account'])

const profile = useForm({
  name: props.account?.name || '',
  email: props.account?.email || '',
})

const password = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
})

const saveProfile = () => profile.put('/admin/akun/profil', { preserveScroll: true })
const savePassword = () => password.put('/admin/akun/password', {
  preserveScroll: true,
  onSuccess: () => password.reset(),
})

const fmt = (d) => d ? new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) : '—'
</script>

<template>
  <AdminLayout>
    <title>Pengaturan Akun - Admin TSM</title>
    <div class="space-y-6 max-w-3xl">
      <div>
        <h1 class="font-display text-[28px] font-bold text-navy tracking-[-0.5px]">
          <span class="block font-mono text-[11px] font-normal tracking-[2px] uppercase text-label mb-1">Panel Kendali · TSM</span>
          Pengaturan Akun
        </h1>
        <p class="text-slate-500 text-sm">Ubah profil dan kata sandi akun Anda sendiri.</p>
      </div>

      <div class="card p-6 flex flex-wrap items-center gap-4">
        <div class="w-14 h-14 rounded-full bg-accent flex items-center justify-center text-navy text-xl font-bold font-display">
          {{ account?.name?.[0] }}
        </div>
        <div>
          <div class="font-bold text-slate-800">{{ account?.name }}</div>
          <div class="text-xs text-slate-500">{{ account?.email }}</div>
        </div>
        <div class="ml-auto text-right">
          <span class="badge bg-ice text-label border border-line">{{ account?.role }}</span>
          <div class="text-xs text-slate-400 mt-1">Gabung {{ fmt(account?.created_at) }}</div>
        </div>
      </div>

      <!-- Profil -->
      <form @submit.prevent="saveProfile" class="card p-6 space-y-4">
        <h2 class="font-bold text-slate-800">Profil</h2>
        <div class="grid sm:grid-cols-2 gap-4">
          <div>
            <label class="form-label" for="acc-name">Nama Lengkap</label>
            <input id="acc-name" v-model="profile.name" type="text" class="form-input" />
            <p v-if="profile.errors.name" class="text-xs text-red-600 mt-1">{{ profile.errors.name }}</p>
          </div>
          <div>
            <label class="form-label" for="acc-email">Email</label>
            <input id="acc-email" v-model="profile.email" type="email" class="form-input" />
            <p v-if="profile.errors.email" class="text-xs text-red-600 mt-1">{{ profile.errors.email }}</p>
          </div>
        </div>
        <button type="submit" :disabled="profile.processing" class="btn-primary px-6 py-2.5 text-sm">Simpan Profil</button>
      </form>

      <!-- Kata sandi -->
      <form @submit.prevent="savePassword" class="card p-6 space-y-4">
        <h2 class="font-bold text-slate-800">Ubah Kata Sandi</h2>
        <div class="grid sm:grid-cols-3 gap-4">
          <div>
            <label class="form-label" for="pw-cur">Sandi Saat Ini</label>
            <input id="pw-cur" v-model="password.current_password" type="password" class="form-input" autocomplete="current-password" />
            <p v-if="password.errors.current_password" class="text-xs text-red-600 mt-1">{{ password.errors.current_password }}</p>
          </div>
          <div>
            <label class="form-label" for="pw-new">Sandi Baru</label>
            <input id="pw-new" v-model="password.password" type="password" class="form-input" autocomplete="new-password" />
            <p v-if="password.errors.password" class="text-xs text-red-600 mt-1">{{ password.errors.password }}</p>
          </div>
          <div>
            <label class="form-label" for="pw-conf">Ulangi Sandi Baru</label>
            <input id="pw-conf" v-model="password.password_confirmation" type="password" class="form-input" autocomplete="new-password" />
          </div>
        </div>
        <p class="text-xs text-slate-500">Minimal 8 karakter.</p>
        <button type="submit" :disabled="password.processing" class="btn-primary px-6 py-2.5 text-sm">Perbarui Sandi</button>
      </form>
    </div>
  </AdminLayout>
</template>
