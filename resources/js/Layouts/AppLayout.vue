<script setup>
import { ref, computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

const page = usePage()
const settings = computed(() => page.props.settings || {})
const flash = computed(() => page.props.flash || {})
const mobileOpen = ref(false)
const showFlash = ref(true)

const navLinks = [
  { label: 'Beranda', href: '/' },
  { label: 'Kompetensi', href: '/beranda#kompetensi' },
  { label: 'Program', href: '/beranda#program' },
  { label: 'Sarana', href: '/beranda#sarana' },
  { label: 'Prestasi', href: '/beranda#prestasi' },
  { label: 'Berita', href: '/berita' },
  { label: 'PPDB', href: '/ppdb' },
]
</script>

<template>
  <div class="min-h-screen flex flex-col">
    <!-- Flash -->
    <Transition enter-active-class="transition duration-300" enter-from-class="opacity-0 -translate-y-2" leave-active-class="transition duration-200" leave-to-class="opacity-0 -translate-y-2">
      <div v-if="showFlash && (flash.success || flash.error)" class="fixed top-4 right-4 z-50 max-w-md">
        <div v-if="flash.success" class="bg-green-500 text-white px-5 py-3 rounded-xl shadow-lg flex items-center gap-3">
          <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
          <span class="text-sm">{{ flash.success }}</span>
          <button @click="showFlash=false" class="ml-auto"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <div v-if="flash.error" class="bg-red-500 text-white px-5 py-3 rounded-xl shadow-lg flex items-center gap-3">
          <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          <span class="text-sm">{{ flash.error }}</span>
          <button @click="showFlash=false" class="ml-auto"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
      </div>
    </Transition>

    <!-- Navbar Glassy -->
    <nav class="fixed top-4 left-1/2 -translate-x-1/2 w-11/12 max-w-5xl z-50 backdrop-blur-xl bg-white/20 border border-white/30 rounded-2xl shadow-2xl">
      <div class="px-6 py-3">
        <div class="flex items-center justify-between">
          <Link href="/" class="flex items-center gap-3">
            <div class="w-10 h-10 bg-blue-900 rounded-xl flex items-center justify-center text-white font-bold text-sm shadow-lg">TSM</div>
            <div class="hidden sm:block">
              <div class="font-bold text-blue-900 text-sm leading-tight">TSM</div>
              <div class="text-xs text-slate-600">SMKS Islam 1</div>
            </div>
          </Link>
          <div class="hidden lg:flex items-center gap-0.5">
            <a v-for="link in navLinks" :key="link.href" :href="link.href" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-700 hover:text-blue-900 hover:bg-white/40 transition backdrop-blur-sm">{{ link.label }}</a>
          </div>
          <div class="flex items-center gap-2">
            <Link href="/login" class="hidden lg:inline-flex items-center gap-1.5 bg-blue-900/80 text-white text-xs font-bold px-3.5 py-1.5 rounded-lg hover:bg-blue-900 transition backdrop-blur-sm">
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
              Admin
            </Link>
            <button @click="mobileOpen=!mobileOpen" class="lg:hidden p-1.5 rounded-lg text-slate-700 hover:bg-white/30 backdrop-blur-sm">
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
          </div>
        </div>
        <!-- Mobile menu -->
        <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0 -translate-y-1" leave-to-class="opacity-0 -translate-y-1">
          <div v-if="mobileOpen" class="lg:hidden mt-3 pt-3 border-t border-white/20 space-y-1">
            <a v-for="link in navLinks" :key="link.href" :href="link.href" class="block px-3 py-2 rounded-lg text-xs font-semibold text-slate-700 hover:bg-white/40">{{ link.label }}</a>
            <Link href="/login" class="block px-3 py-2 rounded-lg text-xs font-bold text-blue-900 bg-white/40">Login Admin</Link>
          </div>
        </Transition>
      </div>
    </nav>

    <!-- Content -->
    <main class="flex-1">
      <slot />
    </main>

    <!-- Footer -->
    <footer class="bg-blue-950 text-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10">
          <div class="lg:col-span-2">
            <div class="flex items-center gap-3 mb-4">
              <div class="w-12 h-12 bg-white/10 rounded-xl flex items-center justify-center text-white font-bold">TSM</div>
              <div>
                <div class="font-bold text-lg">Teknik Sepeda Motor</div>
                <div class="text-blue-300 text-sm">SMKS Islam 1 Kota Blitar</div>
              </div>
            </div>
            <p class="text-blue-200 text-sm leading-relaxed mb-4">Mencetak teknisi sepeda motor yang kompeten, berkarakter Islami, dan siap memasuki dunia industri.</p>
            <div class="text-blue-300 text-sm space-y-2">
              <div class="flex items-start gap-2">
                <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                {{ settings.address || 'Jl. Musi No. 06 Kota Blitar' }}
              </div>
              <div v-if="settings.phone" class="flex items-center gap-2">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                {{ settings.phone }}
              </div>
              <div v-if="settings.email" class="flex items-center gap-2">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                {{ settings.email }}
              </div>
            </div>
          </div>
          <div>
            <h4 class="font-semibold mb-4">Tautan Cepat</h4>
            <ul class="space-y-2 text-blue-300 text-sm">
              <li><a href="/#tentang" class="hover:text-white transition">Tentang Kami</a></li>
              <li><a href="/#kompetensi" class="hover:text-white transition">Kompetensi</a></li>
              <li><a href="/#program" class="hover:text-white transition">Program Unggulan</a></li>
              <li><a href="/#prestasi" class="hover:text-white transition">Prestasi</a></li>
              <li><a href="/berita" class="hover:text-white transition">Berita</a></li>
              <li><a href="/ppdb" class="hover:text-white transition">PPDB</a></li>
            </ul>
          </div>
          <div>
            <h4 class="font-semibold mb-4">Media Sosial</h4>
            <div class="space-y-3">
              <a v-if="settings.instagram_url" :href="settings.instagram_url" target="_blank" class="flex items-center gap-2 text-blue-300 hover:text-white text-sm transition">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                Instagram
              </a>
              <a v-if="settings.facebook_url" :href="settings.facebook_url" target="_blank" class="flex items-center gap-2 text-blue-300 hover:text-white text-sm transition">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"/></svg>
                Facebook
              </a>
              <a v-if="settings.youtube_url" :href="settings.youtube_url" target="_blank" class="flex items-center gap-2 text-blue-300 hover:text-white text-sm transition">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                YouTube
              </a>
            </div>
          </div>
        </div>
        <div class="border-t border-blue-900 mt-10 pt-6 text-center text-blue-400 text-sm">
          {{ settings.footer_text || '© 2026 Teknik Sepeda Motor SMKS Islam 1 Kota Blitar.' }}
        </div>
      </div>
    </footer>
  </div>
</template>
