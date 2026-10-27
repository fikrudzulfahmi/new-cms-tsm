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
const isWriter = computed(() => !!user.value?.can_write)
const isAdmin = computed(() => (user.value?.role || 'editor') === 'admin')

const isActive = (item) => {
  const paths = item.match || [item.href]
  return paths.some((path) =>
    path === '/admin'
      ? currentUrl.value === '/admin'
      : currentUrl.value === path || currentUrl.value.startsWith(path + '/')
  )
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
      { label: 'Galeri & Media', adminOnly: true, href: '/admin/galeri', match: ['/admin/galeri', '/admin/slider'], icon: 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z' },
    ]
  },
  {
    label: 'Halaman Website',
    items: [
      { label: 'Konten Halaman', adminOnly: true, href: '/admin/guru', match: ['/admin/guru', '/admin/kompetensi', '/admin/program', '/admin/sarana', '/admin/prestasi', '/admin/testimoni', '/admin/mitra'], icon: 'M4 5.5A1.5 1.5 0 015.5 4h13A1.5 1.5 0 0120 5.5V9H4V5.5zM4 12h7v8H5.5A1.5 1.5 0 014 18.5V12zm10 0h6v6.5A1.5 1.5 0 0118.5 20H14v-8z' },
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
      { label: 'Laporan & Analisis', adminOnly: true, href: '/admin/laporan', icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z' },
      { label: 'Log Aktivitas', adminOnly: true, href: '/admin/aktivitas', badge: security?.failed_24h || 0, icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2z' },
      { label: 'Pengaturan', adminOnly: true, href: '/admin/pengaturan', icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z' },
    ]
  },
]

// Editor hanya melihat menu yang bukan adminOnly (Dashboard, Berita, Komentar).
const menuGroups = computed(() =>
  allMenuGroups
    .map((g) => ({ ...g, items: g.items.filter((i) => isWriter.value ? i.href === '/admin/berita' : (isAdmin.value || !i.adminOnly)) }))
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
              :class="['flex items-center gap-3 px-3 py-2.5 rounded-full text-sm font-semibold transition-all', isActive(item) ? 'bg-accent text-navy' : 'text-on-navy-muted hover:bg-white/5 hover:text-white']">
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
        <a v-if="isWriter" href="/pilih" class="text-slate-500 hover:text-label text-sm">← Pilih tujuan</a>
        <Link href="/" class="text-slate-500 hover:text-label text-sm flex items-center gap-1">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
          Lihat Website
        </Link>
        <div class="ml-auto flex items-center gap-3">
          <Link v-if="!isWriter" href="/admin/komentar" class="relative p-1.5 rounded-lg text-slate-500 hover:bg-slate-100">
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
