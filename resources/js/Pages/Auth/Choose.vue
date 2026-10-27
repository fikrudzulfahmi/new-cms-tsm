<script setup>
import { Head, Link, usePage, router } from '@inertiajs/vue3'
defineProps({ lms: String })
const user = usePage().props.auth.user
</script>

<template>
  <Head title="Pilih Tujuan" />
  <div class="grid-dark flex min-h-screen items-center justify-center px-4 py-10">
    <div class="w-full max-w-2xl">
      <div class="mb-8 text-center text-white">
        <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full border border-accent/50 font-mono text-xs tracking-wider text-accent">TSM</div>
        <h1 class="font-display text-2xl font-bold">Halo, {{ user.name }}</h1>
        <p class="mt-1 text-sm text-on-navy-muted">Mau masuk ke mana?</p>
      </div>
      <div class="grid gap-4 sm:grid-cols-2">
        <Link :href="lms" class="group rounded-3xl bg-white p-6 shadow-xl transition hover:-translate-y-1 hover:ring-2 hover:ring-accent">
          <span class="mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-navy text-accent">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
          </span>
          <div class="font-display text-lg font-bold text-navy">LMS</div>
          <p class="mt-1 text-sm text-slate-500">Mapel, materi, tugas, kuis, dan presensi.</p>
          <span class="mt-4 inline-block text-sm font-semibold text-label group-hover:underline">Masuk LMS →</span>
        </Link>
        <a href="/admin/berita" class="group rounded-3xl bg-white p-6 shadow-xl transition hover:-translate-y-1 hover:ring-2 hover:ring-accent">
          <span class="mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-accent text-navy">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" /></svg>
          </span>
          <div class="font-display text-lg font-bold text-navy">CMS</div>
          <p class="mt-1 text-sm text-slate-500">Tulis berita untuk website sekolah (menunggu persetujuan admin).</p>
          <span class="mt-4 inline-block text-sm font-semibold text-label group-hover:underline">Tulis berita →</span>
        </a>
      </div>
      <button class="mx-auto mt-6 block text-sm text-on-navy-muted hover:text-white" @click="router.post('/logout')">Keluar</button>
    </div>
  </div>
</template>
