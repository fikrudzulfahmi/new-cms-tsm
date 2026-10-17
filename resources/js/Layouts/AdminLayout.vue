<script setup>
import { ref, computed } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'

const page = usePage()
const user = computed(() => page.props.auth?.user)
const flash = computed(() => page.props.flash || {})
const showFlash = ref(true)
const sidebarOpen = ref(false)

const currentUrl = computed(() => page.url)
const security = computed(() => page.props.security || null)
const isAdmin = computed(() => (user.value?.role || 'editor') === 'admin')

const isActive = (path) => {
  if (path === '/admin') return currentUrl.value === '/admin'
  if (path.includes('#')) return false
  return currentUrl.value.startsWith(path)
}

const logout = () => router.post('/logout')

const allMenuGroups = [
  {
    label: null,
    items: [
      { label: 'Dashboard', href: '/admin', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
    ]
  },
  {
    label: 'Konten',
    items: [
      { label: 'Berita', href: '/admin/berita', icon: 'M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z' },
      { label: 'Komentar', href: '/admin/komentar', icon: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z' },
      { label: 'Galeri', adminOnly: true, href: '/admin/galeri', icon: 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z' },
    ]
  },
  {
    label: 'Halaman',
    items: [
      { label: 'Profil Jurusan', adminOnly: true, href: '/admin/pengaturan#profil', icon: 'M12 14l9-5-9-5-9 5 9 5zm0 0v7m-6-10.5V16c0 1.5 2.7 3 6 3s6-1.5 6-3v-5.5' },
      { label: 'Slider Hero', adminOnly: true, href: '/admin/slider', icon: 'M4 6h16M4 10h16M4 14h16M4 18h16' },
      { label: 'Kompetensi', adminOnly: true, href: '/admin/kompetensi', icon: 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z' },
      { label: 'Program Unggulan', adminOnly: true, href: '/admin/program', icon: 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z' },
      { label: 'Sarana', adminOnly: true, href: '/admin/sarana', icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4' },
      { label: 'Prestasi', adminOnly: true, href: '/admin/prestasi', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
    ]
  },
  {
    label: 'SDM & Mitra',
    items: [
      { label: 'Guru & Instruktur', adminOnly: true, href: '/admin/guru', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z' },
      { label: 'Mitra Industri', adminOnly: true, href: '/admin/mitra', icon: 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z' },
      { label: 'Testimoni', adminOnly: true, href: '/admin/testimoni', icon: 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z' },
      { label: 'Kategori', adminOnly: true, href: '/admin/kategori', icon: 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z' },
    ]
  },
  {
    label: 'Komunikasi',
    items: [
      { label: 'Pesan Masuk', adminOnly: true, href: '/admin/pesan', icon: 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z' },
    ]
  },
  {
    label: 'Sistem',
    items: [
      { label: 'Laporan', adminOnly: true, href: '/admin/laporan', icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z' },
      { label: 'Log Aktivitas', adminOnly: true, href: '/admin/aktivitas', badge: security?.failed_24h || 0, icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2z' },
      { label: 'Pengaturan', adminOnly: true, href: '/admin/pengaturan', icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z' },
    ]
  },
]

// Editor hanya melihat menu yang bukan adminOnly (Dashboard, Berita, Komentar).
const menuGroups = computed(() =>
  allMenuGroups
    .map((g) => ({ ...g, items: g.items.filter((i) => isAdmin.value || !i.adminOnly) }))
    .filter((g) => g.items.length)
)
</script>

<template>
  <div class="min-h-screen grid-light flex">
    <!-- Sidebar Overlay (mobile) -->
    <div v-if="sidebarOpen" @click="sidebarOpen=false" class="fixed inset-0 z-20 bg-navy/60 lg:hidden"></div>

    <!-- Sidebar -->
    <aside :class="['fixed inset-y-0 left-0 z-30 w-64 grid-dark border-r border-accent/20 flex flex-col transition-transform duration-300 lg:translate-x-0 lg:static lg:z-auto', sidebarOpen ? 'translate-x-0' : '-translate-x-full']">
      <!-- Logo -->
      <div class="flex items-center gap-3 px-5 py-5 border-b border-accent/20">
        <div class="w-10 h-10 rounded-full border border-accent/45 flex items-center justify-center text-accent font-mono text-[11px] tracking-wider">TSM</div>
        <div>
          <div class="font-display text-white font-bold text-sm leading-tight">Teknik Sepeda Motor</div>
          <div class="font-mono text-[11px] tracking-wider uppercase text-accent">Panel Kendali</div>
        </div>
      </div>

      <!-- Menu -->
      <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-5">
        <div v-for="group in menuGroups" :key="group.label || 'main'">
          <div v-if="group.label" class="font-mono text-on-navy-subtle text-[11px] uppercase tracking-[2px] px-3 mb-2">{{ group.label }}</div>
          <div class="space-y-0.5">
            <Link v-for="item in group.items" :key="item.href" :href="item.href" @click="sidebarOpen = false"
              :class="['flex items-center gap-3 px-3 py-2.5 rounded-full text-sm font-semibold transition-all', isActive(item.href) ? 'bg-accent text-navy' : 'text-on-navy-muted hover:bg-white/5 hover:text-white']">
              <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon"/>
              </svg>
              {{ item.label }}
              <span v-if="item.badge" class="ml-auto min-w-5 h-5 px-1.5 rounded-full bg-red-500 text-white text-[11px] font-bold flex items-center justify-center" :aria-label="`${item.badge} login gagal 24 jam`">{{ item.badge }}</span>
            </Link>
          </div>
        </div>
      </nav>

      <!-- User + Logout -->
      <div class="p-4 border-t border-accent/20">
        <div class="flex items-center gap-3 mb-3">
          <div class="w-9 h-9 rounded-full bg-accent flex items-center justify-center text-navy text-sm font-bold font-display">{{ user?.name?.[0] }}</div>
          <div class="overflow-hidden">
            <div class="text-white text-sm font-medium truncate">{{ user?.name }}</div>
            <div class="font-mono text-on-navy-subtle text-[11px] uppercase tracking-wider">{{ user?.role }}</div>
          </div>
        </div>
        <button @click="logout" class="w-full flex items-center gap-2 px-3 py-2 rounded-full border border-accent/30 text-on-navy-muted hover:border-accent hover:text-white text-sm transition">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
          Keluar
        </button>
      </div>
    </aside>

    <!-- Main -->
    <div class="flex-1 flex flex-col min-w-0">
      <!-- Top bar -->
      <header class="bg-white/85 backdrop-blur-xl border-b border-line h-16 flex items-center gap-4 px-4 sm:px-6 sticky top-0 z-10">
        <button @click="sidebarOpen=true" class="lg:hidden p-1.5 rounded-lg text-slate-500 hover:bg-slate-100">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <Link href="/" class="text-slate-500 hover:text-label text-sm flex items-center gap-1">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
          Lihat Website
        </Link>
        <div class="ml-auto flex items-center gap-3">
          <Link href="/admin/komentar" class="relative p-1.5 rounded-lg text-slate-500 hover:bg-slate-100">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
          </Link>
          <div class="text-sm text-slate-600 font-medium">{{ user?.name }}</div>
        </div>
      </header>

      <!-- Flash -->
      <Transition enter-active-class="transition duration-300" enter-from-class="opacity-0 -translate-y-2" leave-to-class="opacity-0">
        <div v-if="showFlash && (flash.success || flash.error)" class="m-4">
          <div v-if="flash.success" class="bg-navy border border-accent/40 text-white px-4 py-3 rounded-2xl flex items-center gap-3 text-sm">
            <svg class="w-5 h-5 text-accent flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            {{ flash.success }}
            <button @click="showFlash=false" class="ml-auto text-on-navy-subtle hover:text-white">✕</button>
          </div>
          <div v-if="flash.error" class="bg-white border border-red-200 text-red-700 px-4 py-3 rounded-2xl flex items-center gap-3 text-sm">
            <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            {{ flash.error }}
            <button @click="showFlash=false" class="ml-auto text-red-600 hover:text-red-800">✕</button>
          </div>
        </div>
      </Transition>

      <!-- Content -->
      <main class="flex-1 p-4 sm:p-6">
        <slot />
      </main>
    </div>
  </div>
</template>
