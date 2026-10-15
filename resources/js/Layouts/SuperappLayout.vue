<script setup>
// Layout TSM Superapp. variant="superadmin" -> sidebar menu superadmin; "portal" -> header sederhana guru/siswa
import { ref, computed } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'

const props = defineProps({ variant: { type: String, default: 'portal' } })
const page = usePage()
const user = computed(() => page.props.auth?.user)
const flash = computed(() => page.props.flash || {})
const sidebarOpen = ref(false)
const path = computed(() => (page.url || '').split('?')[0])
const isActive = (href) => href === '/superadmin' ? path.value === '/superadmin' : path.value.startsWith(href)
const logout = () => router.post('/logout')
const roleLabel = { superadmin: 'Superadmin', guru: 'Guru', siswa: 'Siswa', staf: 'Admin Fitur' }

const I = {
  home: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
  siswa: 'M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z',
  guru: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
  akun: 'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z',
  lms: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
  pkl: 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
  ukk: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
  perpus: 'M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z',
}
const menuGroups = [
  { label: null, items: [{ label: 'Dashboard', href: '/superadmin', icon: I.home }] },
  { label: 'Data Master', items: [
    { label: 'Data Siswa', href: '/superadmin/siswa', icon: I.siswa },
    { label: 'Data Guru', href: '/superadmin/guru', icon: I.guru },
    { label: 'Admin Fitur', href: '/superadmin/admin-fitur', icon: I.akun },
  ] },
  { label: 'Fitur', items: [
    { label: 'LMS', href: '/app/lms/admin', icon: I.lms },
    { label: 'PKL', href: '/app/pkl/admin', icon: I.pkl },
    { label: 'UKK', href: '/app/ukk/admin', icon: I.ukk },
    { label: 'Perpustakaan Digital', href: '/app/perpus/admin', icon: I.perpus },
  ] },
]
</script>

<template>
  <div class="min-h-screen grid-light flex">
    <!-- Sidebar superadmin -->
    <template v-if="variant === 'superadmin'">
      <div v-if="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 z-20 bg-navy/60 lg:hidden"></div>
      <aside :class="['fixed inset-y-0 left-0 z-30 w-64 grid-dark border-r border-accent/20 flex flex-col transition-transform duration-300 lg:translate-x-0 lg:static lg:z-auto', sidebarOpen ? 'translate-x-0' : '-translate-x-full']">
        <div class="flex items-center gap-3 px-5 py-5 border-b border-accent/20">
          <div class="w-10 h-10 rounded-full border border-accent/45 flex items-center justify-center text-accent font-mono text-[11px] tracking-wider">TSM</div>
          <div>
            <div class="font-display text-white font-bold text-sm leading-tight">TSM Superapp</div>
            <div class="font-mono text-[11px] tracking-wider uppercase text-accent">Superadmin</div>
          </div>
        </div>
        <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-5">
          <div v-for="g in menuGroups" :key="g.label || 'main'">
            <div v-if="g.label" class="font-mono text-on-navy-subtle text-[11px] uppercase tracking-[2px] px-3 mb-2">{{ g.label }}</div>
            <div class="space-y-0.5">
              <Link v-for="it in g.items" :key="it.href" :href="it.href" @click="sidebarOpen = false"
                :class="['flex items-center gap-3 px-3 py-2.5 rounded-full text-sm font-semibold transition-all', isActive(it.href) ? 'bg-accent text-navy' : 'text-on-navy-muted hover:bg-white/5 hover:text-white']">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" :d="it.icon" /></svg>
                <span class="flex-1">{{ it.label }}</span>
                <span v-if="it.soon" class="text-[9px] font-mono uppercase tracking-wider px-1.5 py-0.5 rounded-full" :class="isActive(it.href) ? 'bg-navy/15' : 'bg-white/10 text-on-navy-subtle'">Segera</span>
              </Link>
            </div>
          </div>
        </nav>
        <div class="p-4 border-t border-accent/20">
          <div class="flex items-center gap-3 mb-3">
            <div class="w-9 h-9 rounded-full bg-accent flex items-center justify-center text-navy text-sm font-bold font-display">{{ user?.name?.[0] }}</div>
            <div class="overflow-hidden">
              <div class="text-white text-sm font-medium truncate">{{ user?.name }}</div>
              <div class="font-mono text-on-navy-subtle text-[11px] uppercase tracking-wider">{{ roleLabel[user?.role] || user?.role }}</div>
            </div>
          </div>
          <button @click="logout" class="w-full px-3 py-2 rounded-full border border-accent/30 text-on-navy-muted hover:border-accent hover:text-white text-sm transition">Keluar</button>
        </div>
      </aside>
    </template>

    <div class="flex-1 flex flex-col min-w-0">
      <header class="bg-white/85 backdrop-blur-xl border-b border-line h-16 flex items-center gap-4 px-4 sm:px-6 sticky top-0 z-10">
        <button v-if="variant === 'superadmin'" @click="sidebarOpen = true" class="lg:hidden p-1.5 rounded-lg text-slate-500 hover:bg-slate-100" aria-label="Buka menu">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
        </button>
        <Link v-else href="/portal" class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-full bg-navy flex items-center justify-center text-accent font-mono text-[10px] tracking-wider">TSM</div>
          <div class="leading-tight">
            <div class="font-display font-bold text-navy text-sm">TSM Superapp</div>
            <div class="font-mono text-[10px] tracking-wider uppercase text-label">Portal {{ roleLabel[user?.role] || '' }}</div>
          </div>
        </Link>
        <a href="/" class="hidden sm:inline text-slate-500 hover:text-label text-sm">Lihat Website</a>
        <div class="ml-auto flex items-center gap-3">
          <div class="text-right leading-tight hidden sm:block">
            <div class="text-sm text-slate-700 font-semibold">{{ user?.name }}</div>
            <div class="text-[11px] text-slate-500">{{ roleLabel[user?.role] || user?.role }}</div>
          </div>
          <button v-if="variant !== 'superadmin'" @click="logout" class="px-4 py-2 rounded-full border border-line text-sm font-semibold text-slate-600 hover:border-label hover:text-label">Keluar</button>
        </div>
      </header>

      <div v-if="flash.success || flash.error" class="mx-4 sm:mx-6 mt-4">
        <div v-if="flash.success" class="bg-navy border border-accent/40 text-white px-4 py-3 rounded-2xl text-sm">{{ flash.success }}</div>
        <div v-if="flash.error" class="bg-white border border-red-200 text-red-700 px-4 py-3 rounded-2xl text-sm">{{ flash.error }}</div>
      </div>

      <main class="flex-1 p-4 sm:p-6">
        <slot />
      </main>
    </div>
  </div>
</template>
