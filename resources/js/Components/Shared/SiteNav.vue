<script setup>
// Navbar publik tunggal: dipakai landing (/) dan AppLayout (/berita, artikel)
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import { usePage, Link } from '@inertiajs/vue3'

const page = usePage()
const s = computed(() => page.props.settings || {})
const open = ref(false)
const scrolled = ref(false)
// Tombol masuk admin sengaja tidak ditampilkan; admin akses manual lewat /admin.
const path = computed(() => (page.url || '/').split('#')[0].split('?')[0])
const logo = computed(() => {
  const p = s.value.site_logo
  return p ? (/^https?:\/\//.test(p) ? p : `/storage/${p}`) : null
})

const links = [
  { label: 'Beranda', href: '/' },
  { label: 'Profil', href: '/profil', also: ['/guru'] },
  { label: 'Kompetensi', href: '/kompetensi' },
  { label: 'Program', href: '/program' },
  { label: 'Sarana', href: '/sarana' },
  { label: 'Prestasi', href: '/prestasi' },
  { label: 'Berita', href: '/berita' },
  { label: 'Alumni', href: '/alumni' },
  { label: 'Kontak', href: '/#kontak' },
]
// Anchor yang punya section di landing (untuk penanda menu aktif saat menggulir).
const sectionIds = links.filter((l) => l.href.startsWith('/#')).map((l) => l.href.slice(2))
const activeHash = ref('')

function updateActiveHash() {
  if (path.value !== '/') { activeHash.value = ''; return }
  const offset = 130
  let current = ''
  for (const id of sectionIds) {
    const el = document.getElementById(id)
    if (el && el.getBoundingClientRect().top - offset <= 0) current = '#' + id
  }
  // Di dasar halaman, section terakhir yang ada selalu dianggap aktif.
  if (window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 4) {
    for (let i = sectionIds.length - 1; i >= 0; i--) {
      if (document.getElementById(sectionIds[i])) { current = '#' + sectionIds[i]; break }
    }
  }
  activeHash.value = current
}

const isActive = (href) => {
  if (href.startsWith('/#')) return path.value === '/' && activeHash.value === href.slice(1)
  if (href === '/') return path.value === '/' && !activeHash.value
  const l = links.find((x) => x.href === href)
  return [href, ...(l?.also || [])].some((h) => path.value === h || path.value.startsWith(h + '/'))
}

// Di landing, anchor /#x cukup scroll native; dari halaman lain pakai Link (tanpa reload).
const navTag = (href) => (href.startsWith('/#') && path.value === '/' ? 'a' : Link)

function onNavClick(href) {
  open.value = false
  if (href.startsWith('/#')) activeHash.value = href.slice(1)
}

let ticking = false
function onScroll() {
  scrolled.value = window.scrollY > 16
  if (ticking) return
  ticking = true
  requestAnimationFrame(() => { updateActiveHash(); ticking = false })
}

onMounted(() => {
  updateActiveHash()
  scrolled.value = window.scrollY > 16
  window.addEventListener('scroll', onScroll, { passive: true })
  window.addEventListener('resize', onScroll, { passive: true })
})
onBeforeUnmount(() => {
  window.removeEventListener('scroll', onScroll)
  window.removeEventListener('resize', onScroll)
})
// Pindah halaman lewat Inertia (/ -> /berita) harus memindahkan penanda aktif.
watch(path, () => { open.value = false; updateActiveHash() })
</script>

<template>
  <nav aria-label="Navigasi utama"
    class="site-nav fixed left-1/2 -translate-x-1/2 w-[calc(100%-2rem)] max-w-[1200px] z-50 backdrop-blur-xl border border-accent/25 transition-all duration-200"
    :class="[scrolled || open ? 'top-2.5 bg-navy/90 shadow-[0_10px_40px_rgba(10,26,58,.35)]' : 'top-4 bg-navy/60', open ? 'rounded-3xl' : 'rounded-[28px]']">
    <div class="flex items-center justify-between gap-4 px-4 sm:px-5 py-2.5">
      <Link href="/" class="flex items-center gap-3 min-w-0" aria-label="Beranda TSM">
        <img v-if="logo" :src="logo" alt="" class="w-10 h-10 object-contain flex-shrink-0" />
        <svg v-else class="text-accent flex-shrink-0" width="40" height="40" viewBox="0 0 40 40" fill="none" aria-hidden="true">
          <circle cx="20" cy="20" r="17" stroke="currentColor" stroke-width="2"/>
          <circle cx="20" cy="20" r="6" fill="currentColor"/>
          <path d="M20 3v8M20 29v8M3 20h8M29 20h8M8 8l5.5 5.5M26.5 26.5L32 32M32 8l-5.5 5.5M13.5 26.5L8 32" stroke="currentColor" stroke-width="1.6"/>
        </svg>
        <span class="flex flex-col leading-tight min-w-0">
          <span class="font-display font-bold text-white text-[15px] truncate">{{ s.site_name || s.department_name || 'Teknik Sepeda Motor' }}</span>
          <span class="font-mono text-[11px] tracking-wider uppercase text-on-navy-subtle truncate">{{ s.school_name || 'SMKS Islam 1 Blitar' }}</span>
        </span>
      </Link>

      <div class="hidden xl:flex items-center gap-1">
        <component :is="navTag(l.href)" v-for="l in links" :key="l.href" :href="l.href" @click="onNavClick(l.href)"
          class="px-3 py-2 rounded-full text-sm font-semibold transition"
          :class="isActive(l.href) ? 'text-white bg-white/10' : 'text-on-navy-muted hover:text-white hover:bg-white/5'"
          :aria-current="isActive(l.href) ? 'page' : undefined">{{ l.label }}</component>
      </div>

      <div class="flex items-center gap-2 flex-shrink-0">
        <component :is="navTag('/#spmb')" href="/#spmb" class="hidden md:inline-flex items-center h-11 px-5 rounded-full bg-accent text-navy text-sm font-bold hover:brightness-105 transition">Gabung Bersama Kami</component>
        <button type="button" @click="open = !open" :aria-expanded="open" :aria-label="open ? 'Tutup menu' : 'Buka menu'"
          class="xl:hidden w-11 h-11 rounded-full border border-accent/40 text-white flex items-center justify-center">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path v-if="!open" stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16"/>
            <path v-else stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>
    </div>

    <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0 -translate-y-1" leave-active-class="transition duration-150" leave-to-class="opacity-0 -translate-y-1">
      <div v-if="open" class="xl:hidden px-4 pb-4 pt-3 border-t border-accent/20 space-y-1">
        <component :is="navTag(l.href)" v-for="l in links" :key="l.href" :href="l.href" @click="onNavClick(l.href)"
          class="block px-4 py-2.5 rounded-2xl text-sm font-semibold transition"
          :class="isActive(l.href) ? 'text-white bg-white/10' : 'text-on-navy-muted hover:text-white hover:bg-white/5'"
          :aria-current="isActive(l.href) ? 'page' : undefined">{{ l.label }}</component>
        <div class="grid gap-2 pt-2 md:hidden">
          <component :is="navTag('/#spmb')" href="/#spmb" @click="open = false" class="inline-flex justify-center items-center h-11 rounded-full bg-accent text-navy text-sm font-bold">Gabung Bersama Kami</component>
        </div>
      </div>
    </Transition>
  </nav>
</template>
