<script setup>
// Navbar publik tunggal: dipakai landing (/) dan AppLayout (/berita, artikel)
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import { usePage, Link } from '@inertiajs/vue3'

const page = usePage()
const s = computed(() => page.props.settings || {})
const open = ref(false)
const scrolled = ref(false)
const activeDropdown = ref(null)
const mobileOpenMenus = ref({
  Profil: false,
  Akademik: false,
  Fasilitas: false,
})

const path = computed(() => (page.url || '/').split('#')[0].split('?')[0])
const logo = computed(() => {
  const p = s.value.site_logo
  return p ? (/^https?:\/\//.test(p) ? p : `/storage/${p}`) : null
})

const navItems = [
  { label: 'Beranda', href: '/' },
  {
    label: 'Profil',
    children: [
      { label: 'Profil Jurusan', href: '/profil' },
      { label: 'Guru & Instruktur', href: '/guru' },
      { label: 'Mitra Industri', href: '/#mitra' },
      { label: 'Alumni', href: '/alumni' },
    ],
  },
  {
    label: 'Akademik',
    children: [
      { label: 'Kompetensi Keahlian', href: '/kompetensi' },
      { label: 'Program Unggulan', href: '/program' },
    ],
  },
  {
    label: 'Fasilitas',
    children: [
      { label: 'Sarana & Bengkel', href: '/sarana' },
      { label: 'Galeri Foto', href: '/#galeri' },
    ],
  },
  { label: 'Prestasi', href: '/prestasi' },
  { label: 'Berita', href: '/berita' },
  { label: 'Kontak', href: '/#kontak' },
]

// Kumpulkan semua link anak maupun root untuk deteksi anchor scrolling
const allLinks = []
navItems.forEach((item) => {
  if (item.children) {
    item.children.forEach((c) => allLinks.push(c))
  } else {
    allLinks.push(item)
  }
})

const sectionIds = allLinks.filter((l) => l.href.startsWith('/#')).map((l) => l.href.slice(2))
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
  if (!href) return false
  if (href.startsWith('/#')) return path.value === '/' && activeHash.value === href.slice(1)
  if (href === '/') return path.value === '/' && !activeHash.value
  return path.value === href || path.value.startsWith(href + '/')
}

const isParentActive = (item) => {
  if (item.children) {
    return item.children.some((c) => isActive(c.href))
  }
  return isActive(item.href)
}

// Di landing, anchor /#x cukup scroll native; dari halaman lain pakai Link (tanpa reload).
const navTag = (href) => (href && href.startsWith('/#') && path.value === '/' ? 'a' : Link)

function onNavClick(href) {
  open.value = false
  activeDropdown.value = null
  if (href && href.startsWith('/#')) activeHash.value = href.slice(1)
}

function toggleMobileSubmenu(label) {
  mobileOpenMenus.value[label] = !mobileOpenMenus.value[label]
}

let timeoutId = null
function onDropdownEnter(label) {
  clearTimeout(timeoutId)
  activeDropdown.value = label
}

function onDropdownLeave() {
  timeoutId = setTimeout(() => {
    activeDropdown.value = null
  }, 150)
}

let ticking = false
function onScroll() {
  scrolled.value = window.scrollY > 16
  if (ticking) return
  ticking = true
  requestAnimationFrame(() => { updateActiveHash(); ticking = false })
}

function onClickOutside(e) {
  if (!e.target.closest('.nav-dropdown-container')) {
    activeDropdown.value = null
  }
}

onMounted(() => {
  updateActiveHash()
  scrolled.value = window.scrollY > 16
  window.addEventListener('scroll', onScroll, { passive: true })
  window.addEventListener('resize', onScroll, { passive: true })
  document.addEventListener('click', onClickOutside)
})

onBeforeUnmount(() => {
  window.removeEventListener('scroll', onScroll)
  window.removeEventListener('resize', onScroll)
  document.removeEventListener('click', onClickOutside)
})

// Pindah halaman lewat Inertia (/ -> /berita) harus memindahkan penanda aktif.
watch(path, () => {
  open.value = false
  activeDropdown.value = null
  updateActiveHash()
})
</script>

<template>
  <nav aria-label="Navigasi utama"
    class="site-nav fixed left-1/2 -translate-x-1/2 w-[calc(100%-2rem)] max-w-[1200px] z-50 backdrop-blur-xl border border-accent/25 transition-all duration-200"
    :class="[scrolled || open ? 'top-2.5 bg-navy/95 shadow-[0_10px_40px_rgba(10,26,58,.35)]' : 'top-4 bg-navy/80', open ? 'rounded-3xl' : 'rounded-[28px]']">
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

      <!-- Desktop Navigation Menu -->
      <div class="hidden lg:flex items-center gap-1 xl:gap-2">
        <template v-for="item in navItems" :key="item.label">
          <!-- Item Flat -->
          <component
            v-if="!item.children"
            :is="navTag(item.href)"
            :href="item.href"
            @click="onNavClick(item.href)"
            class="px-3 py-2 rounded-full text-sm font-semibold transition cursor-pointer"
            :class="isActive(item.href) ? 'text-white bg-white/10' : 'text-on-navy-muted hover:text-white hover:bg-white/5'"
            :aria-current="isActive(item.href) ? 'page' : undefined"
          >
            {{ item.label }}
          </component>

          <!-- Item Dropdown -->
          <div
            v-else
            class="nav-dropdown-container relative"
            @mouseenter="onDropdownEnter(item.label)"
            @mouseleave="onDropdownLeave"
          >
            <button
              type="button"
              @click="activeDropdown = activeDropdown === item.label ? null : item.label"
              class="flex items-center gap-1 px-3 py-2 rounded-full text-sm font-semibold transition cursor-pointer"
              :class="isParentActive(item) ? 'text-white bg-white/10' : 'text-on-navy-muted hover:text-white hover:bg-white/5'"
              :aria-expanded="activeDropdown === item.label"
            >
              <span>{{ item.label }}</span>
              <svg
                class="w-3.5 h-3.5 transition-transform duration-200"
                :class="activeDropdown === item.label ? 'rotate-180 text-accent' : 'text-on-navy-subtle'"
                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"
              >
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
              </svg>
            </button>

            <!-- Dropdown popup -->
            <Transition
              enter-active-class="transition ease-out duration-150"
              enter-from-class="opacity-0 translate-y-2 scale-95"
              enter-to-class="opacity-100 translate-y-0 scale-100"
              leave-active-class="transition ease-in duration-100"
              leave-from-class="opacity-100 translate-y-0 scale-100"
              leave-to-class="opacity-0 translate-y-2 scale-95"
            >
              <div
                v-if="activeDropdown === item.label"
                class="absolute left-0 mt-2 w-52 bg-navy/95 rounded-2xl shadow-2xl border border-accent/25 py-2 z-50 backdrop-blur-xl overflow-hidden"
              >
                <component
                  :is="navTag(child.href)"
                  v-for="child in item.children"
                  :key="child.href"
                  :href="child.href"
                  @click="onNavClick(child.href)"
                  class="block px-4 py-2.5 text-sm transition-colors cursor-pointer"
                  :class="isActive(child.href) ? 'bg-accent/15 text-accent font-semibold border-l-2 border-accent' : 'text-on-navy-muted hover:text-white hover:bg-white/5'"
                  :aria-current="isActive(child.href) ? 'page' : undefined"
                >
                  {{ child.label }}
                </component>
              </div>
            </Transition>
          </div>
        </template>
      </div>

      <!-- Action Button (No Login Button) -->
      <div class="flex items-center gap-2 flex-shrink-0">
        <component :is="navTag('/#spmb')" href="/#spmb" class="hidden md:inline-flex items-center h-10 px-5 rounded-full bg-accent text-navy text-sm font-bold hover:brightness-105 transition shadow-sm">Gabung Bersama Kami</component>
        <button type="button" @click="open = !open" :aria-expanded="open" :aria-label="open ? 'Tutup menu' : 'Buka menu'"
          class="lg:hidden w-11 h-11 rounded-full border border-accent/40 text-white flex items-center justify-center cursor-pointer">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path v-if="!open" stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16"/>
            <path v-else stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>
    </div>

    <!-- Mobile Drawer -->
    <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0 -translate-y-1" leave-active-class="transition duration-150" leave-to-class="opacity-0 -translate-y-1">
      <div v-if="open" class="lg:hidden px-4 pb-4 pt-3 border-t border-accent/20 space-y-1 max-h-[calc(100vh-100px)] overflow-y-auto">
        <template v-for="item in navItems" :key="item.label">
          <!-- Non-dropdown mobile -->
          <component
            v-if="!item.children"
            :is="navTag(item.href)"
            :href="item.href"
            @click="onNavClick(item.href)"
            class="block px-4 py-2.5 rounded-2xl text-sm font-semibold transition"
            :class="isActive(item.href) ? 'text-white bg-white/10' : 'text-on-navy-muted hover:text-white hover:bg-white/5'"
            :aria-current="isActive(item.href) ? 'page' : undefined"
          >
            {{ item.label }}
          </component>

          <!-- Dropdown mobile accordion -->
          <div v-else class="rounded-2xl overflow-hidden">
            <button
              type="button"
              @click="toggleMobileSubmenu(item.label)"
              class="flex items-center justify-between w-full px-4 py-2.5 text-sm font-semibold transition cursor-pointer"
              :class="isParentActive(item) ? 'text-white bg-white/10' : 'text-on-navy-muted hover:text-white hover:bg-white/5'"
            >
              <span>{{ item.label }}</span>
              <svg
                class="w-4 h-4 transition-transform duration-200"
                :class="mobileOpenMenus[item.label] ? 'rotate-180 text-accent' : 'text-on-navy-subtle'"
                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
              >
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
            <div v-show="mobileOpenMenus[item.label]" class="pl-6 pr-2 py-1 space-y-1 bg-white/5 rounded-xl mt-1 mb-1">
              <component
                :is="navTag(child.href)"
                v-for="child in item.children"
                :key="child.href"
                :href="child.href"
                @click="onNavClick(child.href)"
                class="block py-2 text-sm transition-colors"
                :class="isActive(child.href) ? 'text-accent font-semibold' : 'text-on-navy-muted hover:text-white'"
                :aria-current="isActive(child.href) ? 'page' : undefined"
              >
                {{ child.label }}
              </component>
            </div>
          </div>
        </template>

        <div class="pt-2">
          <component :is="navTag('/#spmb')" href="/#spmb" @click="open = false" class="inline-flex justify-center items-center h-11 w-full rounded-full bg-accent text-navy text-sm font-bold shadow-sm">Gabung Bersama Kami</component>
        </div>
      </div>
    </Transition>
  </nav>
</template>
