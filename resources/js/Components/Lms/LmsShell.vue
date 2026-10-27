<script setup>
import { ref, computed } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'

defineProps({ title: String, back: String, backLabel: String })
const page = usePage()
const user = computed(() => page.props.auth.user)
const flash = computed(() => page.props.flash || {})
const url = computed(() => page.url.split('?')[0])
const sheet = ref(false)

const base = computed(() => (user.value.role === 'guru' ? '/ruang-guru' : '/belajar'))
const items = computed(() => [
  { label: 'Mapel', href: base.value, exact: true, icon: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253' },
  { label: 'Presensi', href: base.value + '/presensi', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4' },
])
const active = (i) => (i.exact ? !url.value.startsWith(i.href + '/presensi') : url.value.startsWith(i.href))
const logout = () => router.post('/logout')
const roleLabel = computed(() => (user.value.role === 'guru' ? 'Guru' : 'Siswa'))
</script>

<template>
  <div class="min-h-screen bg-gradient-to-b from-ice-section to-slate-50 pb-24 md:pb-10">
    <!-- Header -->
    <header class="sticky top-0 z-30 bg-navy text-white shadow-md shadow-navy/20">
      <div class="mx-auto flex h-14 max-w-5xl items-center gap-3 px-4">
        <Link :href="base" class="flex items-center gap-2.5">
          <span class="flex h-8 w-8 items-center justify-center rounded-full border border-accent/50 font-mono text-[10px] tracking-wider text-accent">TSM</span>
          <span class="font-display text-sm font-bold leading-tight">LMS TSM<span class="block font-mono text-[10px] font-normal uppercase tracking-widest text-accent">{{ roleLabel }}</span></span>
        </Link>

        <nav class="ml-6 hidden items-center gap-1 md:flex">
          <Link v-for="i in items" :key="i.href" :href="i.href" :class="['rounded-full px-4 py-1.5 text-sm font-semibold transition', active(i) ? 'bg-accent text-navy' : 'text-on-navy-muted hover:bg-white/10 hover:text-white']">{{ i.label }}</Link>
        </nav>

        <div class="ml-auto hidden items-center gap-3 md:flex">
          <span class="text-sm text-on-navy-muted">{{ user.name }}</span>
          <a v-if="user.can_write" href="/admin/berita" class="rounded-full bg-accent px-3 py-1 text-xs font-semibold text-navy">CMS</a>
          <Link href="/ganti-password" class="rounded-full border border-accent/30 px-3 py-1 text-xs text-on-navy-muted transition hover:border-accent hover:text-white">Password</Link>
          <button type="button" class="rounded-full border border-accent/30 px-3 py-1 text-xs text-on-navy-muted transition hover:border-accent hover:text-white" @click="logout">Keluar</button>
        </div>
        <button type="button" class="ml-auto flex h-8 w-8 items-center justify-center rounded-full bg-accent text-sm font-bold text-navy md:hidden" aria-label="Akun" @click="sheet = true">{{ user.name?.[0] }}</button>
      </div>
    </header>

    <main class="mx-auto max-w-5xl px-4 pt-5">
      <Link v-if="back" :href="back" class="mb-2 inline-flex items-center gap-1 rounded-full bg-white px-3 py-1 text-xs font-medium text-label shadow-sm ring-1 ring-line transition hover:ring-accent">
        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>{{ backLabel || 'Kembali' }}
      </Link>
      <h1 class="mb-4 flex items-center gap-2 font-display text-xl font-bold text-navy sm:text-2xl"><span class="h-6 w-1.5 rounded-full bg-accent"></span>{{ title }}</h1>
      <div v-if="flash.success" class="mb-4 flex items-center gap-2 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm text-emerald-800"><svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>{{ flash.success }}</div>
      <div v-if="flash.error" class="mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-2.5 text-sm text-red-700">{{ flash.error }}</div>
      <slot />
    </main>

    <!-- Bottom menu (mobile) -->
    <nav class="fixed inset-x-0 bottom-0 z-30 border-t border-line bg-white/95 backdrop-blur md:hidden" style="padding-bottom: env(safe-area-inset-bottom)">
      <div class="mx-auto grid max-w-md grid-cols-3">
        <Link v-for="i in items" :key="i.href" :href="i.href" class="flex flex-col items-center gap-0.5 py-2 text-[11px] font-semibold transition" :class="active(i) ? 'text-navy' : 'text-slate-400'">
          <span class="flex h-8 w-14 items-center justify-center rounded-full transition" :class="active(i) ? 'bg-accent/40' : ''">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" :d="i.icon" /></svg>
          </span>{{ i.label }}
        </Link>
        <button type="button" class="flex flex-col items-center gap-0.5 py-2 text-[11px] font-semibold text-slate-400" @click="sheet = true">
          <span class="flex h-8 w-14 items-center justify-center rounded-full">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
          </span>Akun
        </button>
      </div>
    </nav>

    <!-- Akun (sheet mobile) -->
    <transition name="sheet">
      <div v-if="sheet" class="fixed inset-0 z-40 md:hidden" @click.self="sheet = false">
        <div class="absolute inset-0 bg-navy/50" @click="sheet = false"></div>
        <div class="absolute inset-x-0 bottom-0 rounded-t-3xl bg-white p-5 shadow-2xl" style="padding-bottom: calc(1.25rem + env(safe-area-inset-bottom))">
          <div class="mx-auto mb-4 h-1 w-10 rounded-full bg-slate-200"></div>
          <div class="mb-4 flex items-center gap-3">
            <span class="flex h-11 w-11 items-center justify-center rounded-full bg-navy font-display text-lg font-bold text-accent">{{ user.name?.[0] }}</span>
            <div class="min-w-0"><div class="truncate font-semibold text-navy">{{ user.name }}</div><div class="font-mono text-[11px] uppercase tracking-wider text-slate-500">{{ roleLabel }}</div></div>
          </div>
          <a v-if="user.can_write" href="/admin/berita" class="mb-2 block rounded-xl bg-accent/30 px-4 py-3 text-sm font-semibold text-navy">Tulis berita (CMS)</a>
          <Link href="/ganti-password" class="mb-2 block rounded-xl border border-line px-4 py-3 text-sm font-medium text-navy active:bg-ice" @click="sheet = false">Ganti password</Link>
          <a href="/" class="mb-2 block rounded-xl border border-line px-4 py-3 text-sm font-medium text-navy active:bg-ice">Lihat website</a>
          <button type="button" class="w-full rounded-xl bg-red-50 px-4 py-3 text-left text-sm font-semibold text-red-700 active:bg-red-100" @click="logout">Keluar</button>
        </div>
      </div>
    </transition>
  </div>
</template>

<style>
.sheet-enter-active, .sheet-leave-active { transition: opacity .2s }
.sheet-enter-active > div:last-child, .sheet-leave-active > div:last-child { transition: transform .25s }
.sheet-enter-from, .sheet-leave-to { opacity: 0 }
.sheet-enter-from > div:last-child, .sheet-leave-to > div:last-child { transform: translateY(100%) }
</style>
