<script setup>
import { Link, usePage, router } from '@inertiajs/vue3'
defineProps({ title: String, back: String, backLabel: String })
const page = usePage()
const user = page.props.auth.user
</script>

<template>
  <div class="min-h-screen bg-slate-50">
    <header class="bg-navy text-white">
      <div class="max-w-5xl mx-auto px-4 py-3 flex items-center justify-between gap-3">
        <span class="font-display font-bold">LMS TSM</span>
        <div class="flex items-center gap-4 text-sm">
          <Link v-if="user.role==='guru'" href="/ruang-guru" class="text-white/80 hover:text-white">Mapel</Link>
          <Link v-if="user.role==='guru'" href="/ruang-guru/presensi" class="text-white/80 hover:text-white">Presensi</Link>
          <Link v-if="user.role==='siswa'" href="/belajar" class="text-white/80 hover:text-white">Mapel</Link>
          <Link v-if="user.role==='siswa'" href="/belajar/presensi" class="text-white/80 hover:text-white">Presensi</Link>
          <span class="hidden md:inline text-white/70">{{ user.name }}</span>
          <Link href="/ganti-password" class="text-white/80 hover:text-white">Password</Link>
          <button type="button" class="text-white/80 hover:text-white" @click="router.post('/logout')">Keluar</button>
        </div>
      </div>
    </header>
    <main class="max-w-5xl mx-auto px-4 py-6">
      <Link v-if="back" :href="back" class="text-sm text-slate-500 hover:text-navy">&larr; {{ backLabel || 'Kembali' }}</Link>
      <h1 class="font-display text-2xl font-bold text-navy mt-1 mb-4">{{ title }}</h1>
      <div v-if="page.props.flash?.success" class="mb-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm px-4 py-2">{{ page.props.flash.success }}</div>
      <slot />
    </main>
  </div>
</template>
