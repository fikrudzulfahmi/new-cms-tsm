<script setup>
import { ref, computed } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'

defineProps({ title: String, back: String, backLabel: String })
const page = usePage()
const user = computed(() => page.props.auth?.user)
const flash = computed(() => page.props.flash || {})
const sidebarOpen = ref(false)
const showFlash = ref(true)
const url = computed(() => page.url)
const logout = () => router.post('/logout')

const menu = [
  { label: 'Dashboard', href: '/superadmin', exact: true, icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
  { label: 'Data Siswa', href: '/superadmin/siswa', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z' },
  { label: 'Data Guru', href: '/superadmin/guru', icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' },
  { label: 'Mapel & Guru', href: '/superadmin/mapel', icon: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253' },
  { label: 'Penyimpanan', href: '/superadmin/penyimpanan', icon: 'M4 7v10c0 2 1.5 3 3.5 3h9c2 0 3.5-1 3.5-3V7c0-2-1.5-3-3.5-3h-9C5.5 4 4 5 4 7zm0 5h16' },
]
const isActive = (i) => (i.exact ? url.value.split('?')[0] === i.href : url.value === i.href || url.value.startsWith(i.href + '/'))
</script>

<template>
  <div class="min-h-screen grid-light flex">
    <div v-if="sidebarOpen" @click="sidebarOpen=false" class="fixed inset-0 z-20 bg-navy/60 lg:hidden"></div>
    <aside :class="['fixed inset-y-0 left-0 z-30 w-64 grid-dark border-r border-accent/20 flex flex-col transition-transform duration-300 lg:translate-x-0 lg:static lg:z-auto', sidebarOpen ? 'translate-x-0' : '-translate-x-full']">
      <div class="flex items-center gap-3 px-5 py-5 border-b border-accent/20">
        <div class="w-10 h-10 rounded-full border border-accent/45 flex items-center justify-center text-accent font-mono text-[11px] tracking-wider">TSM</div>
        <div>
          <div class="font-display text-white font-bold text-sm leading-tight">Teknik Sepeda Motor</div>
          <div class="font-mono text-[11px] tracking-wider uppercase text-accent">Superadmin LMS</div>
        </div>
      </div>
      <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-0.5">
        <Link v-for="item in menu" :key="item.href" :href="item.href" @click="sidebarOpen = false"
          :class="['flex items-center gap-3 px-3 py-2.5 rounded-full text-sm font-semibold transition-all', isActive(item) ? 'bg-accent text-navy' : 'text-on-navy-muted hover:bg-white/5 hover:text-white']">
          <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" :d="item.icon"/></svg>
          {{ item.label }}
        </Link>
      </nav>
      <div class="p-4 border-t border-accent/20">
        <div class="flex items-center gap-3 mb-3">
          <div class="w-9 h-9 rounded-full bg-accent flex items-center justify-center text-navy text-sm font-bold font-display">{{ user?.name?.[0] }}</div>
          <div class="overflow-hidden">
            <div class="text-white text-sm font-medium truncate">{{ user?.name }}</div>
            <div class="font-mono text-on-navy-subtle text-[11px] uppercase tracking-wider">{{ user?.role }}</div>
          </div>
        </div>
        <Link href="/ganti-password" class="block text-center mb-2 px-3 py-2 rounded-full border border-accent/30 text-on-navy-muted hover:border-accent hover:text-white text-sm transition">Ganti Password</Link>
        <button @click="logout" class="w-full px-3 py-2 rounded-full border border-accent/30 text-on-navy-muted hover:border-accent hover:text-white text-sm transition">Keluar</button>
      </div>
    </aside>

    <div class="flex-1 flex flex-col min-w-0">
      <header class="bg-white/85 backdrop-blur-xl border-b border-line h-16 flex items-center gap-4 px-4 sm:px-6 sticky top-0 z-10">
        <button @click="sidebarOpen=true" class="lg:hidden p-1.5 rounded-lg text-slate-500 hover:bg-slate-100">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <Link href="/" class="text-slate-500 hover:text-label text-sm">Lihat Website</Link>
        <div class="ml-auto text-sm text-slate-600 font-medium">{{ user?.name }}</div>
      </header>

      <div v-if="showFlash && (flash.success || flash.error)" class="m-4 mb-0">
        <div v-if="flash.success" class="bg-navy border border-accent/40 text-white px-4 py-3 rounded-2xl flex items-center gap-3 text-sm">
          {{ flash.success }}<button @click="showFlash=false" class="ml-auto text-on-navy-subtle hover:text-white">✕</button>
        </div>
        <div v-if="flash.error" class="bg-white border border-red-200 text-red-700 px-4 py-3 rounded-2xl text-sm">{{ flash.error }}</div>
      </div>

      <main class="flex-1 p-4 sm:p-6">
        <Link v-if="back" :href="back" class="text-sm text-slate-500 hover:text-navy">&larr; {{ backLabel || 'Kembali' }}</Link>
        <h1 v-if="title" class="font-display text-2xl font-bold text-navy mt-1 mb-4">{{ title }}</h1>
        <slot />
      </main>
    </div>
  </div>
</template>
