<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import GearRatioHero from '@/Components/GearRatioHero.vue'

const props = defineProps(['sliders','competencies','programs','facilities','achievements','latestPosts','categories','testimonials','teachers','partners','gallery','stats'])

// Slider
const currentSlide = ref(0)
let sliderInterval = null
const nextSlide = () => { currentSlide.value = (currentSlide.value + 1) % (props.sliders?.length || 1) }
const prevSlide = () => { currentSlide.value = (currentSlide.value - 1 + (props.sliders?.length || 1)) % (props.sliders?.length || 1) }
onMounted(() => { if ((props.sliders?.length || 0) > 1) sliderInterval = setInterval(nextSlide, 5000) })
onUnmounted(() => clearInterval(sliderInterval))

// Category filter
const selectedCat = ref(null)
const filteredPosts = computed(() => {
  if (!selectedCat.value) return props.latestPosts || []
  return (props.latestPosts || []).filter(p => p.category?.slug === selectedCat.value)
})

// Testimonial slider
const currentTestimonial = ref(0)
const nextTestimonial = () => { currentTestimonial.value = (currentTestimonial.value + 1) % Math.max((props.testimonials?.length || 1), 1) }
const prevTestimonial = () => { currentTestimonial.value = (currentTestimonial.value - 1 + Math.max((props.testimonials?.length || 1), 1)) % Math.max((props.testimonials?.length || 1), 1) }

// Testimonial form
const testimonialForm = useForm({ name: '', graduation_year: '', occupation: '', workplace: '', message: '', rating: 5 })
const submitTestimonial = () => testimonialForm.post('/testimoni', { preserveScroll: true, onSuccess: () => { testimonialForm.reset(); testimonialSent.value = true } })
const testimonialSent = ref(false)

// Contact form
const contactForm = useForm({ name: '', email: '', phone: '', subject: '', message: '' })
const submitContact = () => contactForm.post('/kontak', { preserveScroll: true, onSuccess: () => { contactForm.reset() } })

const formatDate = (d) => d ? new Date(d).toLocaleDateString('id-ID', { day:'numeric', month:'long', year:'numeric' }) : ''

const icons = {
  'wrench-screwdriver': 'M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z',
  'bolt': 'M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z',
  'cpu-chip': 'M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 002.25-2.25V6.75a2.25 2.25 0 00-2.25-2.25H6.75A2.25 2.25 0 004.5 6.75v10.5a2.25 2.25 0 002.25 2.25zm.75-12h9v9h-9v-9z',
  'shield-check': 'M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z',
  'building-storefront': 'M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 2.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .415.336.75.75.75z',
  'briefcase': 'M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0M12 12.75h.008v.008H12v-.008z',
  'document-check': 'M10.125 2.25h-4.5c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125v-9M10.125 2.25h.375a9 9 0 019 9v.375M10.125 2.25A3.375 3.375 0 0113.5 5.625v1.5c0 .621.504 1.125 1.125 1.125h1.5a3.375 3.375 0 013.375 3.375M9 15l2.25 2.25L15 12',
  'users': 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z',
}
const getIcon = (name) => icons[name] || icons['briefcase']
const levelBadge = { 'Internasional': 'bg-purple-100 text-purple-700', 'Nasional': 'bg-amber-100 text-amber-700', 'Provinsi': 'bg-blue-100 text-blue-700', 'Kota': 'bg-green-100 text-green-700', 'Sekolah': 'bg-slate-100 text-slate-600' }
</script>

<template>
  <AppLayout>
    <title>Beranda - TSM SMKS Islam 1 Blitar</title>

    <!-- HERO SLIDER -->
    <section class="relative overflow-hidden bg-blue-950 hero-slide flex items-center" id="beranda">
      <div class="absolute inset-0">
        <template v-if="sliders?.length">
          <div v-for="(slide, i) in sliders" :key="slide.id" :class="['absolute inset-0 transition-opacity duration-700', i === currentSlide ? 'opacity-100' : 'opacity-0']">
            <img v-if="slide.image" :src="`/storage/${slide.image}`" class="w-full h-full object-cover" :alt="slide.title" />
            <div class="absolute inset-0 gradient-overlay"></div>
          </div>
        </template>
        <div v-else class="absolute inset-0 gradient-navy"></div>
        <div class="absolute inset-0" style="background: linear-gradient(to right, rgba(23,37,84,0.8) 0%, rgba(23,37,84,0.3) 70%, transparent 100%)"></div>
      </div>

      <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 w-full">
        <div class="grid lg:grid-cols-2 gap-12 items-center">
          <div>
            <template v-if="sliders?.length">
              <div v-for="(slide, i) in sliders" :key="`text-${slide.id}`" :class="['transition-all duration-700', i === currentSlide ? 'opacity-100 translate-y-0' : 'opacity-0 absolute']">
                <h1 v-if="i===currentSlide" class="text-4xl md:text-5xl lg:text-6xl font-bold text-white leading-tight mb-4">{{ slide.title }}</h1>
                <p v-if="i===currentSlide" class="text-lg text-blue-100 mb-8 leading-relaxed">{{ slide.subtitle }}</p>
                <div v-if="i===currentSlide" class="flex flex-wrap gap-3">
                  <a v-if="slide.button_url" :href="slide.button_url" class="btn-light">{{ slide.button_text || 'Selengkapnya' }}</a>
                  <a href="/ppdb" class="btn-secondary border-white text-white hover:bg-white hover:text-blue-900">Daftar PPDB</a>
                </div>
              </div>
            </template>
            <template v-else>
              <h1 class="text-4xl md:text-6xl font-bold text-white leading-tight mb-4">Jurusan Teknik Sepeda Motor</h1>
              <p class="text-lg text-blue-100 mb-8">SMKS Islam 1 Kota Blitar — Mencetak Teknisi Handal Berkarakter Islami</p>
              <div class="flex gap-3"><a href="#tentang" class="btn-light">Pelajari Lebih</a><a href="/ppdb" class="btn-secondary border-white text-white hover:bg-white hover:text-blue-900">Daftar PPDB</a></div>
            </template>
          </div>
          <div class="hidden lg:flex items-center justify-center">
            <GearRatioHero />
          </div>
        </div>
      </div>

      <!-- Slider Controls -->
      <template v-if="(sliders?.length||0) > 1">
        <button @click="prevSlide" class="absolute left-4 top-1/2 -translate-y-1/2 w-10 h-10 bg-white/20 hover:bg-white/40 rounded-full flex items-center justify-center text-white transition">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <button @click="nextSlide" class="absolute right-4 top-1/2 -translate-y-1/2 w-10 h-10 bg-white/20 hover:bg-white/40 rounded-full flex items-center justify-center text-white transition">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </button>
        <div class="absolute bottom-6 left-1/2 -translate-x-1/2 flex gap-2">
          <button v-for="(_, i) in sliders" :key="i" @click="currentSlide=i" :class="['w-2.5 h-2.5 rounded-full transition-all', i===currentSlide ? 'bg-white w-6' : 'bg-white/50']"></button>
        </div>
      </template>
    </section>

    <!-- STATS -->
    <section class="bg-gradient-to-r from-blue-900 via-blue-800 to-blue-900 py-16 border-y-2 border-blue-700">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-8 text-center text-white">
          <div v-for="(label, key) in {total_students:'Siswa Aktif', total_graduates:'Alumni', total_achievements:'Prestasi', total_partners:'Mitra Industri'}" :key="key" class="py-4 px-2 rounded-xl backdrop-blur-sm bg-white/5 hover:bg-white/10 transition-all group">
            <div class="text-4xl lg:text-5xl font-black mb-2 text-blue-100 group-hover:text-white transition-colors">{{ stats?.[key] || '0' }}<span class="text-xl">+</span></div>
            <div class="text-blue-200 text-sm font-semibold tracking-wide uppercase">{{ label }}</div>
          </div>
        </div>
      </div>
    </section>

    <!-- TENTANG KAMI -->
    <section id="tentang" class="py-20 bg-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-12 items-center">
          <div>
            <div class="inline-block px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-semibold mb-4">Tentang Jurusan</div>
            <h2 class="text-3xl lg:text-4xl font-bold text-slate-900 mb-6">Teknik Sepeda Motor SMKS Islam 1 Blitar</h2>
            <div class="prose prose-blue max-w-none text-slate-600 leading-relaxed" v-html="$page.props.settings?.about_text || 'Jurusan Teknik Sepeda Motor SMKS Islam 1 Kota Blitar hadir untuk mencetak tenaga teknisi sepeda motor yang kompeten, berkarakter Islami, dan siap memasuki dunia industri.'"></div>
            <div class="mt-8 flex gap-3">
              <a href="#kompetensi" class="btn-primary">Kompetensi Kami</a>
              <a href="#program" class="btn-secondary">Program Unggulan</a>
            </div>
          </div>
          <div class="relative">
            <img v-if="$page.props.settings?.about_image" :src="`/storage/${$page.props.settings.about_image}`" class="w-full h-80 object-cover rounded-2xl shadow-lg" alt="Tentang Kami" />
            <div v-else class="w-full h-80 bg-gradient-to-br from-blue-100 to-blue-200 rounded-2xl shadow-lg flex items-center justify-center">
              <div class="text-center text-blue-400">
                <svg class="w-16 h-16 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <p class="text-sm">Foto Tentang Kami</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- KOMPETENSI -->
    <section id="kompetensi" class="py-20 bg-slate-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
          <div class="inline-block px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-semibold mb-3">Kompetensi</div>
          <h2 class="section-title">Kompetensi Keahlian</h2>
          <p class="section-subtitle">Yang diajarkan di jurusan Teknik Sepeda Motor</p>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <div v-for="c in competencies" :key="c.id" class="card p-6 hover:shadow-md transition-all hover:-translate-y-1 group">
            <div class="w-12 h-12 bg-blue-100 group-hover:bg-blue-900 rounded-xl flex items-center justify-center mb-4 transition-colors">
              <svg class="w-6 h-6 text-blue-900 group-hover:text-white transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" :d="getIcon(c.icon)" />
              </svg>
            </div>
            <h3 class="font-bold text-slate-800 mb-2 group-hover:text-blue-900 transition-colors">{{ c.title }}</h3>
            <p class="text-slate-500 text-sm leading-relaxed">{{ c.short_description }}</p>
          </div>
          <div v-if="!competencies?.length" v-for="i in 4" :key="i" class="card p-6 animate-pulse">
            <div class="w-12 h-12 bg-slate-200 rounded-xl mb-4"></div>
            <div class="h-4 bg-slate-200 rounded mb-2"></div>
            <div class="h-3 bg-slate-100 rounded"></div>
          </div>
        </div>
      </div>
    </section>

    <!-- PROGRAM UNGGULAN -->
    <section id="program" class="py-20 bg-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
          <div class="inline-block px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-semibold mb-3">Program</div>
          <h2 class="section-title">Program Unggulan</h2>
          <p class="section-subtitle">Keunggulan yang kami tawarkan untuk masa depan Anda</p>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <div v-for="p in programs" :key="p.id" class="text-center p-8 rounded-2xl bg-slate-50 hover:bg-blue-900 hover:text-white group transition-all hover:-translate-y-1 hover:shadow-lg cursor-default">
            <div class="w-14 h-14 bg-blue-100 group-hover:bg-blue-800 rounded-2xl flex items-center justify-center mx-auto mb-5 transition-colors">
              <svg class="w-7 h-7 text-blue-900 group-hover:text-white transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" :d="getIcon(p.icon)"/>
              </svg>
            </div>
            <h3 class="font-bold text-lg mb-1 group-hover:text-white">{{ p.title }}</h3>
            <p class="text-blue-600 group-hover:text-blue-200 text-sm font-medium mb-3">{{ p.subtitle }}</p>
            <p class="text-slate-500 group-hover:text-blue-100 text-sm leading-relaxed">{{ p.description }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- SARANA -->
    <section id="sarana" class="py-20 bg-slate-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
          <div class="inline-block px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-semibold mb-3">Sarana</div>
          <h2 class="section-title">Sarana & Prasarana</h2>
          <p class="section-subtitle">Fasilitas lengkap untuk mendukung pembelajaran</p>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <div v-for="f in facilities" :key="f.id" class="card overflow-hidden group hover:shadow-md transition-all">
            <div class="h-40 overflow-hidden">
              <img v-if="f.image" :src="`/storage/${f.image}`" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" :alt="f.name" />
              <div v-else class="w-full h-full bg-gradient-to-br from-blue-100 to-slate-100 flex items-center justify-center">
                <svg class="w-10 h-10 text-blue-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/></svg>
              </div>
            </div>
            <div class="p-4">
              <h3 class="font-semibold text-slate-800 text-sm">{{ f.name }}</h3>
              <p v-if="f.category" class="text-xs text-blue-600 mt-0.5">{{ f.category }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- PRESTASI -->
    <section id="prestasi" class="py-20 bg-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
          <div class="inline-block px-3 py-1 bg-amber-100 text-amber-800 rounded-full text-sm font-semibold mb-3">Prestasi</div>
          <h2 class="section-title">Prestasi Kami</h2>
          <p class="section-subtitle">Bangga dengan pencapaian para siswa terbaik</p>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
          <div v-for="a in achievements" :key="a.id" class="card p-6 hover:shadow-md transition-all">
            <div class="flex items-start gap-4">
              <div class="w-12 h-12 rounded-xl overflow-hidden flex-shrink-0">
                <img v-if="a.image" :src="`/storage/${a.image}`" class="w-full h-full object-cover" />
                <div v-else class="w-full h-full bg-amber-100 flex items-center justify-center">
                  <svg class="w-6 h-6 text-amber-500" fill="currentColor" viewBox="0 0 24 24"><path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                </div>
              </div>
              <div class="flex-1 min-w-0">
                <span :class="['badge text-xs mb-2', levelBadge[a.level] || 'bg-slate-100 text-slate-600']">{{ a.level || 'Umum' }}</span>
                <h3 class="font-bold text-slate-800 text-sm leading-tight">{{ a.title }}</h3>
                <p v-if="a.student_name" class="text-slate-500 text-xs mt-1">{{ a.student_name }}</p>
                <p v-if="a.organizer" class="text-slate-400 text-xs">{{ a.organizer }} {{ a.year ? `· ${a.year}` : '' }}</p>
              </div>
            </div>
          </div>
          <div v-if="!achievements?.length" class="lg:col-span-3 text-center py-12 text-slate-400">Belum ada data prestasi</div>
        </div>
      </div>
    </section>

    <!-- BERITA -->
    <section id="berita" class="py-20 bg-slate-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-8 gap-4">
          <div>
            <div class="inline-block px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-semibold mb-2">Berita</div>
            <h2 class="section-title">Berita Terkini</h2>
          </div>
          <a href="/berita" class="btn-secondary text-sm self-start sm:self-auto">Semua Berita →</a>
        </div>
        <!-- Category filter -->
        <div class="flex flex-wrap gap-2 mb-8">
          <button @click="selectedCat=null" :class="['px-4 py-1.5 rounded-full text-sm font-medium transition-all', !selectedCat ? 'bg-blue-900 text-white' : 'bg-white text-slate-600 hover:bg-blue-50']">Semua</button>
          <button v-for="cat in categories" :key="cat.id" @click="selectedCat = selectedCat===cat.slug ? null : cat.slug" :class="['px-4 py-1.5 rounded-full text-sm font-medium transition-all', selectedCat===cat.slug ? 'bg-blue-900 text-white' : 'bg-white text-slate-600 hover:bg-blue-50']">
            {{ cat.name }} <span class="opacity-60 text-xs">({{ cat.posts_count }})</span>
          </button>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
          <a v-for="post in filteredPosts.slice(0,6)" :key="post.id" :href="`/berita/${post.slug}`" class="card hover:shadow-md transition-all hover:-translate-y-1 block group">
            <div class="h-44 overflow-hidden">
              <img v-if="post.cover_image" :src="`/storage/${post.cover_image}`" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" :alt="post.cover_alt||post.title" />
              <div v-else class="w-full h-full bg-gradient-to-br from-blue-100 to-slate-100 flex items-center justify-center">
                <svg class="w-10 h-10 text-blue-200" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
              </div>
            </div>
            <div class="p-5">
              <div v-if="post.category" class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold mb-2" :style="`background:${post.category.color}20;color:${post.category.color}`">{{ post.category.name }}</div>
              <h3 class="font-bold text-slate-800 mb-2 line-clamp-2 group-hover:text-blue-900 transition-colors">{{ post.title }}</h3>
              <p class="text-slate-500 text-sm line-clamp-2 mb-3">{{ post.excerpt }}</p>
              <div class="flex items-center gap-3 text-xs text-slate-400">
                <span>{{ formatDate(post.published_at) }}</span>
                <span class="flex items-center gap-1"><svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>{{ post.views_count }}</span>
              </div>
            </div>
          </a>
          <div v-if="!filteredPosts?.length" class="lg:col-span-3 text-center py-12 text-slate-400">Belum ada berita</div>
        </div>
      </div>
    </section>

    <!-- GALERI -->
    <section id="galeri" class="py-20 bg-white" v-if="gallery?.length">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
          <div class="inline-block px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-semibold mb-3">Galeri</div>
          <h2 class="section-title">Galeri Foto</h2>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
          <div v-for="g in gallery" :key="g.id" class="aspect-square overflow-hidden rounded-xl group cursor-pointer">
            <img :src="`/storage/${g.file}`" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300" :alt="g.title||g.caption||'Galeri'" />
          </div>
        </div>
      </div>
    </section>

    <!-- GURU -->
    <section id="guru" class="py-20 bg-slate-50" v-if="teachers?.length">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
          <div class="inline-block px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-semibold mb-3">Tim Pengajar</div>
          <h2 class="section-title">Guru & Staf</h2>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <div v-for="t in teachers" :key="t.id" class="card p-6 text-center hover:shadow-md transition-all">
            <div class="w-20 h-20 rounded-full overflow-hidden mx-auto mb-4 border-4 border-blue-100">
              <img v-if="t.photo" :src="`/storage/${t.photo}`" class="w-full h-full object-cover" :alt="t.name" />
              <div v-else class="w-full h-full bg-blue-100 flex items-center justify-center text-blue-900 font-bold text-2xl">{{ t.name?.[0] }}</div>
            </div>
            <h3 class="font-bold text-slate-800">{{ t.name }}</h3>
            <p class="text-blue-700 text-sm font-medium">{{ t.position }}</p>
            <p class="text-slate-500 text-xs mt-1">{{ t.subject }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- TESTIMONI -->
    <section id="testimoni" class="py-20 bg-blue-900">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
          <div class="inline-block px-3 py-1 bg-blue-800 text-blue-200 rounded-full text-sm font-semibold mb-3">Umpan Balik</div>
          <h2 class="text-3xl font-bold text-white mb-2">Umpan Balik Lulusan</h2>
          <p class="text-blue-300">Apa kata alumni tentang jurusan TSM?</p>
        </div>
        <div class="grid lg:grid-cols-3 gap-6 mb-12" v-if="testimonials?.length">
          <div v-for="(t, i) in testimonials.slice(0,3)" :key="t.id" class="bg-white/10 backdrop-blur rounded-2xl p-6 text-white">
            <div class="flex mb-3">
              <svg v-for="s in 5" :key="s" :class="['w-4 h-4', s <= t.rating ? 'text-amber-400' : 'text-blue-700']" fill="currentColor" viewBox="0 0 24 24"><path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
            </div>
            <p class="text-blue-100 text-sm leading-relaxed mb-4 italic">"{{ t.message }}"</p>
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-full bg-blue-700 flex items-center justify-center text-white font-bold">{{ t.name?.[0] }}</div>
              <div>
                <div class="font-semibold text-sm">{{ t.name }}</div>
                <div class="text-blue-300 text-xs">{{ t.occupation }} {{ t.graduation_year ? `· Alumni ${t.graduation_year}` : '' }}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Testimonial Form -->
        <div class="bg-white/10 backdrop-blur rounded-2xl p-8 max-w-2xl mx-auto">
          <h3 class="text-white font-bold text-xl mb-6 text-center">Bagikan Pengalaman Anda</h3>
          <div v-if="testimonialSent" class="text-center py-4 text-green-300">
            <svg class="w-12 h-12 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p>Terima kasih! Testimoni Anda sedang direview.</p>
          </div>
          <form v-else @submit.prevent="submitTestimonial" class="space-y-4">
            <div class="grid sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-blue-200 text-sm font-medium mb-1.5">Nama Lengkap *</label>
                <input v-model="testimonialForm.name" type="text" placeholder="Nama Anda" class="form-input bg-white/10 border-blue-700 text-white placeholder-blue-400 focus:border-blue-400 focus:ring-blue-500/30" />
                <p v-if="testimonialForm.errors.name" class="text-red-300 text-xs mt-1">{{ testimonialForm.errors.name }}</p>
              </div>
              <div>
                <label class="block text-blue-200 text-sm font-medium mb-1.5">Tahun Lulus *</label>
                <input v-model="testimonialForm.graduation_year" type="number" placeholder="2024" min="2000" :max="new Date().getFullYear()" class="form-input bg-white/10 border-blue-700 text-white placeholder-blue-400" />
              </div>
              <div>
                <label class="block text-blue-200 text-sm font-medium mb-1.5">Pekerjaan Sekarang</label>
                <input v-model="testimonialForm.occupation" type="text" placeholder="Teknisi / Wirausaha" class="form-input bg-white/10 border-blue-700 text-white placeholder-blue-400" />
              </div>
              <div>
                <label class="block text-blue-200 text-sm font-medium mb-1.5">Tempat Kerja</label>
                <input v-model="testimonialForm.workplace" type="text" placeholder="Nama perusahaan" class="form-input bg-white/10 border-blue-700 text-white placeholder-blue-400" />
              </div>
            </div>
            <div>
              <label class="block text-blue-200 text-sm font-medium mb-2">Rating</label>
              <div class="flex gap-2">
                <button v-for="s in 5" :key="s" type="button" @click="testimonialForm.rating=s">
                  <svg :class="['w-8 h-8 transition-colors', s <= testimonialForm.rating ? 'text-amber-400' : 'text-blue-700']" fill="currentColor" viewBox="0 0 24 24"><path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                </button>
              </div>
            </div>
            <div>
              <label class="block text-blue-200 text-sm font-medium mb-1.5">Pesan / Testimoni *</label>
              <textarea v-model="testimonialForm.message" rows="3" placeholder="Ceritakan pengalaman Anda..." class="form-input bg-white/10 border-blue-700 text-white placeholder-blue-400 resize-none"></textarea>
              <p v-if="testimonialForm.errors.message" class="text-red-300 text-xs mt-1">{{ testimonialForm.errors.message }}</p>
            </div>
            <button type="submit" :disabled="testimonialForm.processing" class="w-full btn-light justify-center py-3">
              {{ testimonialForm.processing ? 'Mengirim...' : 'Kirim Testimoni' }}
            </button>
          </form>
        </div>
      </div>
    </section>

    <!-- MITRA -->
    <section id="mitra" class="py-20 bg-white" v-if="partners?.length">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
          <div class="inline-block px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-semibold mb-3">Mitra</div>
          <h2 class="section-title">Mitra Industri</h2>
        </div>
        <div class="grid grid-cols-3 sm:grid-cols-4 lg:grid-cols-6 gap-6">
          <div v-for="p in partners" :key="p.id" class="flex items-center justify-center p-4 border border-slate-100 rounded-xl hover:border-blue-200 hover:shadow-sm transition-all">
            <img v-if="p.logo" :src="`/storage/${p.logo}`" class="max-h-12 object-contain" :alt="p.name" />
            <span v-else class="text-sm font-semibold text-slate-600 text-center">{{ p.name }}</span>
          </div>
        </div>
      </div>
    </section>

    <!-- PPDB BANNER -->
    <section class="py-16 gradient-navy">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="inline-block px-3 py-1 bg-white/10 text-white rounded-full text-sm font-semibold mb-4">PPDB 2026/2027</div>
        <h2 class="text-3xl font-bold text-white mb-4">{{ $page.props.settings?.ppdb_info || 'Pendaftaran Peserta Didik Baru Dibuka!' }}</h2>
        <p class="text-blue-200 mb-8">Bergabunglah bersama kami dan wujudkan karir di dunia otomotif</p>
        <a href="/ppdb" class="btn-light px-8 py-3">Daftar Sekarang →</a>
      </div>
    </section>

    <!-- KONTAK -->
    <section id="kontak" class="py-20 bg-slate-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
          <div class="inline-block px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-semibold mb-3">Kontak</div>
          <h2 class="section-title">Hubungi Kami</h2>
          <p class="section-subtitle">Kami siap menjawab pertanyaan Anda</p>
        </div>
        <div class="grid lg:grid-cols-2 gap-12">
          <div class="space-y-6">
            <div class="card p-6">
              <h3 class="font-bold text-slate-800 mb-4">Informasi Kontak</h3>
              <div class="space-y-4 text-sm text-slate-600">
                <div class="flex items-start gap-3">
                  <svg class="w-5 h-5 text-blue-900 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                  <div><div class="font-medium text-slate-800">{{ $page.props.settings?.school_name }}</div><div>{{ $page.props.settings?.address }}</div></div>
                </div>
                <div class="flex items-center gap-3">
                  <svg class="w-5 h-5 text-blue-900 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                  {{ $page.props.settings?.phone || '-' }}
                </div>
                <div class="flex items-center gap-3">
                  <svg class="w-5 h-5 text-blue-900 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                  {{ $page.props.settings?.email || '-' }}
                </div>
              </div>
            </div>
            <div class="card p-0 overflow-hidden h-48 bg-blue-50">
              <div v-if="$page.props.settings?.maps_embed" v-html="$page.props.settings.maps_embed" class="w-full h-full"></div>
              <div v-else class="w-full h-full flex items-center justify-center text-slate-400">
                <div class="text-center"><svg class="w-10 h-10 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg><p class="text-sm">Jl. Musi No. 06 Kota Blitar</p></div>
              </div>
            </div>
          </div>
          <div class="card p-8">
            <h3 class="font-bold text-slate-800 mb-6">Kirim Pesan</h3>
            <form @submit.prevent="submitContact" class="space-y-4">
              <div class="grid sm:grid-cols-2 gap-4">
                <div>
                  <label class="form-label">Nama *</label>
                  <input v-model="contactForm.name" type="text" placeholder="Nama Anda" class="form-input" />
                  <p v-if="contactForm.errors.name" class="text-red-500 text-xs mt-1">{{ contactForm.errors.name }}</p>
                </div>
                <div>
                  <label class="form-label">Telepon</label>
                  <input v-model="contactForm.phone" type="text" placeholder="08xx" class="form-input" />
                </div>
              </div>
              <div>
                <label class="form-label">Email</label>
                <input v-model="contactForm.email" type="email" placeholder="email@anda.com" class="form-input" />
              </div>
              <div>
                <label class="form-label">Subjek</label>
                <input v-model="contactForm.subject" type="text" placeholder="Perihal pesan" class="form-input" />
              </div>
              <div>
                <label class="form-label">Pesan *</label>
                <textarea v-model="contactForm.message" rows="4" placeholder="Tulis pesan Anda..." class="form-input resize-none"></textarea>
                <p v-if="contactForm.errors.message" class="text-red-500 text-xs mt-1">{{ contactForm.errors.message }}</p>
              </div>
              <button type="submit" :disabled="contactForm.processing" class="w-full btn-primary justify-center py-3">
                {{ contactForm.processing ? 'Mengirim...' : 'Kirim Pesan' }}
              </button>
            </form>
          </div>
        </div>
      </div>
    </section>
  </AppLayout>
</template>
