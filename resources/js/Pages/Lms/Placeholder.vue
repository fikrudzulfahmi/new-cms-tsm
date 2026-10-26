<script setup>
import { usePage, router } from '@inertiajs/vue3'

defineProps({ role: String })
const user = usePage().props.auth.user
const labels = { guru: 'Ruang Guru', siswa: 'Ruang Belajar', superadmin: 'Superadmin' }
</script>

<template>
  <div class="min-h-screen grid-dark flex items-center justify-center p-4">
    <div class="bg-white rounded-[24px] border border-accent/30 p-8 max-w-md w-full text-center">
      <span class="mono-label mb-2">{{ labels[role] || 'Akun' }}</span>
      <h1 class="font-display text-2xl font-bold text-navy mb-2">Halo, {{ user.name }}</h1>
      <p class="text-slate-500 text-sm mb-6">Akun Anda sudah aktif. Fitur belajar sedang disiapkan dan akan muncul di halaman ini.</p>
      <div class="flex justify-center gap-3">
        <a href="/ganti-password" class="text-sm font-semibold text-navy underline underline-offset-2">Ganti password</a>
        <button type="button" class="text-sm text-slate-500 hover:text-navy" @click="router.post('/logout')">Keluar</button>
      </div>
    </div>
  </div>
</template>
