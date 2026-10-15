<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import { computed, onMounted, onBeforeUnmount, ref, nextTick, watch } from 'vue'
import GearRatio from '@/Components/Landing/GearRatio.vue'
import { CONFIG } from './content.js'
import '../../../css/landing.css'

const props = defineProps({
  sliders: { type: Array, default: () => [] },
  competencies: { type: Array, default: () => [] },
  programs: { type: Array, default: () => [] },
  facilities: { type: Array, default: () => [] },
  achievements: { type: Array, default: () => [] },
  posts: { type: Array, default: () => [] },
  categories: { type: Array, default: () => [] },
  testimonials: { type: Array, default: () => [] },
  teachers: { type: Array, default: () => [] },
  partners: { type: Array, default: () => [] },
  gallery: { type: Array, default: () => [] },
  stats: { type: Object, default: () => ({}) },
})

const page = usePage()
const s = computed(() => page.props.settings || {})
const flash = computed(() => page.props.flash || {})
const img = (p) => (p ? (/^https?:\/\//.test(p) ? p : `/storage/${p}`) : null)
const schoolName = computed(() => s.value.school_name || CONFIG.namaSmk)
const address = computed(() => s.value.address || CONFIG.alamat)

const menuOpen = ref(false)
const scrolled = ref(false)

const navLinks = [
  { href: '#kompetensi', label: 'Kompetensi' },
  { href: '#program', label: 'Program' },
  { href: '#sarana', label: 'Sarana' },
  { href: '#prestasi', label: 'Prestasi' },
  { href: '#berita', label: 'Berita' },
  { href: '#alumni', label: 'Alumni' },
]

/* ---------- HEADLINE (hero berganti dari slider) ---------- */
const slideIdx = ref(0)
const headlines = computed(() => {
  if (props.sliders.length) return props.sliders
  return [{ title: s.value.hero_title || 'Teknik Sepeda Motor', subtitle: s.value.hero_subtitle || '', image: s.value.hero_bg }]
})
const current = computed(() => headlines.value[slideIdx.value % headlines.value.length])
let slideTimer = null
function goSlide(i) { slideIdx.value = (i + headlines.value.length) % headlines.value.length }

/* ---------- Ticker pengumuman ---------- */
const ticker = computed(() => {
  const ann = props.posts.filter(p => p.category?.slug === 'pengumuman')
  return (ann.length ? ann : props.posts).slice(0, 5)
})

/* ---------- Ikon ---------- */
const iconPaths = {
  'wrench-screwdriver': 'M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18v3h3l6.3-6.3a4 4 0 0 0 5.4-5.4l-2.5 2.5-2.4-.6-.6-2.4z',
  bolt: 'M13 2L4 14h7l-1 8 9-12h-7z',
  'cpu-chip': 'M9 9h6v6H9zM4 4h16v16H4zM9 2v2M15 2v2M9 20v2M15 20v2M2 9h2M2 15h2M20 9h2M20 15h2',
  'shield-check': 'M12 3l8 3v6c0 4.5-3.4 8.3-8 9-4.6-.7-8-4.5-8-9V6zM8.5 12l2.5 2.5 4.5-5',
  'building-storefront': 'M3 9l1.5-5h15L21 9M3 9v11h18V9M3 9h18M9 20v-6h6v6',
  briefcase: 'M3 7h18v13H3zM8 7V4h8v3M3 13h18',
  'document-check': 'M6 2h9l5 5v15H6zM14 2v6h6M9 15l2 2 4-4',
  users: 'M16 20v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 10a4 4 0 1 0 0-8 4 4 0 0 0 0 8M22 20v-2a4 4 0 0 0-3-3.9M16 2.1a4 4 0 0 1 0 7.8',
}
const iconFor = (name) => iconPaths[name] || 'M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18zM12 9.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5z'

const alur = [
  { label: 'KELAS X', title: 'Dasar Otomotif', desc: 'Keselamatan kerja, penggunaan alat ukur dan perkakas, serta prinsip kerja mesin.', dark: false },
  { label: 'KELAS XI', title: 'Praktik Bengkel', desc: 'Servis mesin, kelistrikan, sasis, dan injeksi pada unit motor nyata di bengkel sekolah.', dark: false },
  { label: 'KELAS XII', title: 'PKL & Uji Kompetensi', desc: 'Praktik kerja lapangan di mitra industri, lalu uji kompetensi untuk sertifikat keahlian.', dark: true },
]

/* ---------- Sarana ---------- */
const facCat = ref('Semua')
const facCats = computed(() => ['Semua', ...new Set(props.facilities.map(f => f.category).filter(Boolean))])
const facList = computed(() => facCat.value === 'Semua' ? props.facilities : props.facilities.filter(f => f.category === facCat.value))

/* ---------- Berita ---------- */
const postCat = ref('semua')
const postList = computed(() => {
  const list = postCat.value === 'semua' ? props.posts : props.posts.filter(p => p.category?.slug === postCat.value)
  return list.slice(0, 6)
})
const featuredPost = computed(() => postList.value[0])
const otherPosts = computed(() => postList.value.slice(1))
const fmtDate = (d) => d ? new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) : ''

/* ---------- Sambutan kepala jurusan ---------- */
const kajur = computed(() => props.teachers.find(t => /kepala/i.test(t.position || '')) || props.teachers[0])

const karier = [
  { no: '01', title: 'Mekanik Bengkel Resmi', desc: 'Di dealer dan AHASS/bengkel resmi merek.' },
  { no: '02', title: 'Wirausaha Bengkel', desc: 'Membuka bengkel atau usaha suku cadang sendiri.' },
  { no: '03', title: 'Teknisi Industri', desc: 'Quality control dan perakitan di pabrik otomotif.' },
  { no: '04', title: 'Kuliah Lanjut', desc: 'Ke D3/D4/S1 Teknik Mesin atau Otomotif.' },
]

const faqs = [
  { q: 'Apa syarat mendaftar SPMB di jurusan TSM?', a: 'Lulusan SMP/MTs sederajat, mendaftar melalui aplikasi SPMB sekolah, serta melampirkan fotokopi ijazah/SKL, KK, dan akta kelahiran.' },
  { q: 'Apakah siswa perempuan boleh masuk TSM?', a: 'Tentu boleh. Materi dan fasilitas praktik terbuka untuk semua siswa, dan banyak alumni perempuan berkarier di dunia otomotif.' },
  { q: 'Apakah ada sertifikat keahlian?', a: 'Ya. Di kelas XII siswa mengikuti Uji Kompetensi Keahlian (UKK) dan sertifikasi bersama mitra industri.' },
  { q: 'Di mana siswa melaksanakan PKL?', a: 'Di bengkel resmi, dealer, dan industri otomotif yang menjadi mitra jurusan, dengan pendampingan guru pembimbing.' },
  { q: 'Apakah tersedia beasiswa?', a: 'Tersedia beasiswa prestasi dan bantuan bagi siswa kurang mampu. Hubungi panitia SPMB untuk informasi lengkap.' },
]
const openFaq = ref(0)

/* ---------- Galeri lightbox ---------- */
const lightbox = ref(null)

/* ---------- Form ---------- */
const testiForm = useForm({ name: '', graduation_year: '', occupation: '', workplace: '', message: '', rating: 5 })
const showTesti = ref(false)
function submitTesti() {
  testiForm.post('/testimoni', { preserveScroll: true, onSuccess: () => { testiForm.reset(); showTesti.value = false } })
}
const contactForm = useForm({ name: '', email: '', phone: '', subject: '', message: '' })
function submitContact() {
  contactForm.post('/kontak', { preserveScroll: true, onSuccess: () => contactForm.reset() })
}

const waNumber = computed(() => {
  const raw = (s.value.whatsapp || CONFIG.nomorAdmin || '').replace(/\D/g, '')
  if (!raw) return ''
  return raw.startsWith('0') ? '62' + raw.slice(1) : raw
})
const waLink = computed(() => waNumber.value ? `https://wa.me/${waNumber.value}?text=${encodeURIComponent('Halo, saya ingin bertanya tentang pendaftaran Jurusan TSM.')}` : '#kontak')

const socials = computed(() => [
  { key: 'instagram_url', label: 'Instagram' },
  { key: 'facebook_url', label: 'Facebook' },
  { key: 'youtube_url', label: 'YouTube' },
  { key: 'tiktok_url', label: 'TikTok' },
].filter(x => s.value[x.key]).map(x => ({ ...x, url: s.value[x.key] })))

/* ---------- Scroll / reveal ---------- */
let io = null
function onScroll() { scrolled.value = window.scrollY > 16 }
function observeReveal() {
  const reduce = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches
  const els = document.querySelectorAll('.lp .reveal:not(.is-in)')
  if (reduce || !('IntersectionObserver' in window)) { els.forEach(el => el.classList.add('is-in')); return }
  if (!io) {
    io = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (!entry.isIntersecting) return
        const siblings = [...entry.target.parentElement.children].filter(c => c.classList.contains('reveal'))
        entry.target.style.transitionDelay = `${Math.min(6, Math.max(0, siblings.indexOf(entry.target))) * 60}ms`
        entry.target.classList.add('is-in')
        io.unobserve(entry.target)
      })
    }, { threshold: 0.1 })
  }
  els.forEach(el => io.observe(el))
}
// Item baru setelah ganti tab langsung tampil
watch([postCat, facCat], () => nextTick(() => document.querySelectorAll('.lp .reveal:not(.is-in)').forEach(el => el.classList.add('is-in'))))

onMounted(() => {
  onScroll()
  window.addEventListener('scroll', onScroll, { passive: true })
  observeReveal()
  if (headlines.value.length > 1) slideTimer = setInterval(() => goSlide(slideIdx.value + 1), 6000)
})
onBeforeUnmount(() => {
  window.removeEventListener('scroll', onScroll)
  io?.disconnect()
  clearInterval(slideTimer)
})
</script>

<template>
  <Head>
    <title>{{ s.department_name || 'Teknik Sepeda Motor' }} — {{ schoolName }}</title>
    <meta head-key="description" name="description" :content="`Jurusan Teknik Sepeda Motor ${schoolName}: kompetensi keahlian, program unggulan, sarana, prestasi, berita terkini, dan testimoni alumni.`" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Manrope:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet" />
  </Head>

  <div class="lp">
    <!-- ================= HERO / HEADLINE ================= -->
    <header class="hero grid-dark">
      <div class="hero-bg" aria-hidden="true">
        <template v-for="(h, i) in headlines" :key="i">
          <img v-if="h.image" :src="img(h.image)" :class="{ on: i === slideIdx }" alt="" />
        </template>
      </div>

      <nav class="nav" :class="{ 'is-scrolled': scrolled }" aria-label="Navigasi utama">
        <div class="container nav-inner">
          <a href="#" class="brand" aria-label="Beranda TSM">
            <svg class="brand-logo" width="40" height="40" viewBox="0 0 40 40" fill="none" aria-hidden="true">
              <circle cx="20" cy="20" r="17" stroke="currentColor" stroke-width="2"/>
              <circle cx="20" cy="20" r="6" fill="currentColor"/>
              <path d="M20 3v8M20 29v8M3 20h8M29 20h8M8 8l5.5 5.5M26.5 26.5L32 32M32 8l-5.5 5.5M13.5 26.5L8 32" stroke="currentColor" stroke-width="1.6"/>
            </svg>
            <span class="brand-text">
              <span class="brand-title">TSM</span>
              <span class="brand-sub">{{ schoolName }}</span>
            </span>
          </a>

          <ul class="nav-links">
            <li v-for="l in navLinks" :key="l.href"><a :href="l.href">{{ l.label }}</a></li>
          </ul>

          <div class="nav-actions">
            <a href="#kontak" class="btn btn-ghost btn-nav">Kontak</a>
            <a href="#spmb" class="btn btn-primary btn-nav">Gabung Bersama Kami</a>
          </div>

          <button type="button" class="nav-burger" :aria-label="menuOpen ? 'Tutup menu' : 'Buka menu'" :aria-expanded="menuOpen" aria-controls="mobile-menu" @click="menuOpen = !menuOpen">
            <span></span><span></span><span></span>
          </button>
        </div>

        <div v-show="menuOpen" id="mobile-menu" class="mobile-menu">
          <div class="container">
            <a v-for="l in navLinks" :key="l.href" :href="l.href" @click="menuOpen = false">{{ l.label }}</a>
            <div class="mobile-actions">
              <a href="#kontak" class="btn btn-ghost" @click="menuOpen = false">Kontak</a>
              <a href="#spmb" class="btn btn-primary" @click="menuOpen = false">Gabung Bersama Kami</a>
            </div>
          </div>
        </div>
      </nav>

      <div class="container hero-content">
        <div class="hero-text">
          <span class="badge"><span class="badge-dot" aria-hidden="true"></span>AYO GABUNG BERSAMA KAMI</span>
          <transition name="fade" mode="out-in">
            <h1 :key="slideIdx">{{ current.title }} <span v-if="current.subtitle" class="accent">{{ current.subtitle }}</span></h1>
          </transition>
          <p class="lead">{{ s.site_tagline || 'Belajar langsung di bengkel praktik, dari mesin konvensional hingga sistem injeksi dan motor listrik.' }}</p>
          <div class="hero-buttons">
            <a href="#spmb" class="btn btn-primary btn-lg">
              Gabung Bersama Kami
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
            </a>
            <a :href="current.button_url || '#kompetensi'" class="btn btn-ghost btn-lg">{{ current.button_text || 'Lihat Kompetensi' }}</a>
          </div>
          <div v-if="headlines.length > 1" class="hero-dots" aria-label="Pilih headline">
            <button v-for="(h, i) in headlines" :key="i" type="button" :class="{ on: i === slideIdx }" :aria-label="`Headline ${i + 1}`" @click="goSlide(i)"></button>
          </div>
        </div>
        <div class="hero-gear">
          <GearRatio />
        </div>
      </div>

      <!-- Ticker info terkini -->
      <div v-if="ticker.length" class="ticker">
        <div class="container ticker-inner">
          <span class="ticker-label">INFO TERKINI</span>
          <div class="ticker-track">
            <div class="ticker-move">
              <Link v-for="(p, i) in [...ticker, ...ticker]" :key="i" :href="`/berita/${p.slug}`">
                <b>{{ p.category?.name }}</b>{{ p.title }}
              </Link>
            </div>
          </div>
        </div>
      </div>
    </header>

    <main>
      <!-- ================= STATISTIK ================= -->
      <section class="stats-band" aria-label="Statistik">
        <dl class="container stats-grid">
          <div class="stat reveal"><dd>{{ stats.students }}</dd><dt>Siswa aktif</dt></div>
          <div class="stat reveal"><dd>{{ stats.graduates }}</dd><dt>Alumni</dt></div>
          <div class="stat reveal"><dd>{{ stats.absorption }}</dd><dt>Lulusan terserap kerja</dt></div>
          <div class="stat reveal"><dd>{{ stats.achievements }}</dd><dt>Prestasi</dt></div>
          <div class="stat reveal"><dd>{{ stats.partners }}</dd><dt>Mitra industri</dt></div>
          <div class="stat reveal"><dd>{{ stats.teachers }}</dd><dt>Guru &amp; instruktur</dt></div>
        </dl>
      </section>

      <!-- ================= MITRA ================= -->
      <section v-if="partners.length" class="mitra" aria-label="Mitra industri">
        <div class="container mitra-inner">
          <span class="mono-label muted">MITRA INDUSTRI &amp; DUDI</span>
          <div class="marquee">
            <ul class="marquee-move">
              <li v-for="(m, i) in [...partners, ...partners]" :key="i" class="mitra-box" :title="m.name">
                <img v-if="m.logo" :src="img(m.logo)" :alt="m.name" loading="lazy" />
                <span v-else>{{ m.name }}</span>
              </li>
            </ul>
          </div>
        </div>
      </section>

      <!-- ================= TENTANG + SAMBUTAN ================= -->
      <section id="tentang" class="section">
        <div class="container about-wrap">
          <div class="about-media reveal">
            <img v-if="s.about_image" :src="img(s.about_image)" alt="Tentang jurusan TSM" loading="lazy" />
          </div>
          <div class="about-text reveal">
            <span class="mono-label">TENTANG JURUSAN</span>
            <h2>{{ s.department_name || 'Teknik Sepeda Motor' }}</h2>
            <p>{{ s.about_text }}</p>
            <blockquote v-if="kajur" class="sambutan">
              <p>“Kami berkomitmen membekali setiap siswa dengan keterampilan nyata, karakter Islami, dan kesiapan kerja agar mampu bersaing di dunia industri otomotif.”</p>
              <footer>
                <img v-if="kajur.photo" :src="img(kajur.photo)" :alt="kajur.name" loading="lazy" />
                <span><b>{{ kajur.name }}</b><small>{{ kajur.position }}</small></span>
              </footer>
            </blockquote>
          </div>
        </div>
      </section>

      <!-- ================= KOMPETENSI ================= -->
      <section id="kompetensi" class="section section-tint">
        <div class="container">
          <div class="section-head split reveal">
            <div>
              <span class="mono-label">KOMPETENSI KEAHLIAN</span>
              <h2>Apa saja yang kamu pelajari di TSM?</h2>
            </div>
            <p class="head-aside">Materi disusun bersama mitra industri, dengan porsi praktik yang besar di bengkel sekolah.</p>
          </div>
          <div class="komp-grid">
            <article v-for="(k, i) in competencies" :key="k.id" class="komp-card reveal" :class="{ dark: i === 1 }">
              <div class="icon-box">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path :d="iconFor(k.icon)"/></svg>
              </div>
              <h3>{{ k.title }}</h3>
              <p>{{ k.short_description }}</p>
            </article>
          </div>
        </div>
      </section>

      <!-- ================= ALUR ================= -->
      <section id="alur" class="section grid-light">
        <div class="container">
          <div class="section-head center reveal">
            <span class="mono-label">ALUR BELAJAR</span>
            <h2 class="h2-narrow">Tiga tahun, dari dasar hingga siap terjun ke bengkel.</h2>
          </div>
          <div class="alur-grid">
            <span class="alur-line" aria-hidden="true"></span>
            <article v-for="st in alur" :key="st.label" class="alur-card reveal" :class="{ dark: st.dark }">
              <span class="mono-label alur-label">{{ st.label }}</span>
              <h3>{{ st.title }}</h3>
              <p>{{ st.desc }}</p>
            </article>
          </div>
        </div>
      </section>

      <!-- ================= PROGRAM UNGGULAN ================= -->
      <section id="program" class="section">
        <div class="container">
          <div class="section-head reveal">
            <span class="mono-label">PROGRAM UNGGULAN</span>
            <h2>Lebih dari sekadar belajar di kelas.</h2>
          </div>
          <div class="prog-grid">
            <article v-for="p in programs" :key="p.id" class="prog-card reveal">
              <div class="prog-img">
                <img v-if="p.image" :src="img(p.image)" :alt="p.title" loading="lazy" />
                <span class="prog-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path :d="iconFor(p.icon)"/></svg></span>
              </div>
              <div class="prog-body">
                <span v-if="p.subtitle" class="mono-label">{{ p.subtitle }}</span>
                <h3>{{ p.title }}</h3>
                <p>{{ p.description }}</p>
              </div>
            </article>
          </div>
        </div>
      </section>

      <!-- ================= SARANA ================= -->
      <section id="sarana" class="section section-tint">
        <div class="container">
          <div class="section-head reveal">
            <span class="mono-label">SARANA &amp; PRASARANA</span>
            <h2>Bengkel praktik berstandar industri.</h2>
            <div class="chips">
              <button v-for="c in facCats" :key="c" type="button" class="chip" :class="{ on: facCat === c }" @click="facCat = c">{{ c }}</button>
            </div>
          </div>
          <div class="fac-grid">
            <article v-for="(f, i) in facList" :key="f.id" class="fac-card reveal" :class="{ big: i === 0 }">
              <img v-if="f.image" :src="img(f.image)" :alt="f.name" loading="lazy" />
              <div class="fac-info">
                <span class="mono-label accent">{{ f.category }}</span>
                <h3>{{ f.name }}</h3>
                <p v-if="f.quantity" class="fac-meta">{{ f.quantity }} unit<span v-if="f.condition"> · {{ f.condition }}</span></p>
              </div>
            </article>
          </div>
        </div>
      </section>

      <!-- ================= PRESTASI ================= -->
      <section id="prestasi" class="section grid-dark section-dark">
        <div class="container">
          <div class="section-head split reveal">
            <div>
              <span class="mono-label accent">PRESTASI</span>
              <h2>Bukti kompetensi di ajang lomba.</h2>
            </div>
            <p class="head-aside">{{ stats.achievements }} prestasi tercatat, dari tingkat sekolah hingga nasional.</p>
          </div>
          <div class="ach-grid">
            <article v-for="a in achievements" :key="a.id" class="ach-card reveal">
              <div class="ach-img">
                <img v-if="a.image" :src="img(a.image)" :alt="a.title" loading="lazy" />
                <span v-if="a.rank" class="ach-rank">{{ a.rank }}</span>
              </div>
              <div class="ach-body">
                <span class="ach-level">{{ a.level || 'Umum' }}<template v-if="a.year"> · {{ a.year }}</template></span>
                <h3>{{ a.title }}</h3>
                <p>{{ a.student_name }}<span v-if="a.organizer"> — {{ a.organizer }}</span></p>
              </div>
            </article>
          </div>
        </div>
      </section>

      <!-- ================= BERITA TERKINI ================= -->
      <section id="berita" class="section">
        <div class="container">
          <div class="section-head split reveal">
            <div>
              <span class="mono-label">BERITA TERKINI</span>
              <h2>Kabar dari jurusan.</h2>
            </div>
            <Link href="/berita" class="btn btn-outline btn-sm">Semua berita →</Link>
          </div>
          <div class="chips chips-row reveal" aria-label="Kategori berita">
            <button type="button" class="chip" :class="{ on: postCat === 'semua' }" @click="postCat = 'semua'">Semua</button>
            <button v-for="c in categories" :key="c.id" type="button" class="chip" :class="{ on: postCat === c.slug }" @click="postCat = c.slug">
              <i :style="{ background: c.color }"></i>{{ c.name }} <small>{{ c.posts_count }}</small>
            </button>
          </div>

          <div v-if="featuredPost" class="news-wrap">
            <Link :href="`/berita/${featuredPost.slug}`" class="news-feature reveal">
              <img v-if="featuredPost.cover_image" :src="img(featuredPost.cover_image)" :alt="featuredPost.cover_alt || featuredPost.title" loading="lazy" />
              <div class="news-feature-body">
                <span class="cat-tag" :style="{ background: featuredPost.category?.color }">{{ featuredPost.category?.name }}</span>
                <h3>{{ featuredPost.title }}</h3>
                <p>{{ featuredPost.excerpt }}</p>
                <small>{{ fmtDate(featuredPost.published_at) }}<span v-if="featuredPost.reading_time"> · {{ featuredPost.reading_time }} menit baca</span></small>
              </div>
            </Link>
            <div class="news-list">
              <Link v-for="p in otherPosts" :key="p.id" :href="`/berita/${p.slug}`" class="news-item reveal">
                <img v-if="p.cover_image" :src="img(p.cover_image)" :alt="p.cover_alt || p.title" loading="lazy" />
                <div>
                  <span class="cat-text" :style="{ color: p.category?.color }">{{ p.category?.name }}</span>
                  <h4>{{ p.title }}</h4>
                  <small>{{ fmtDate(p.published_at) }}</small>
                </div>
              </Link>
            </div>
          </div>
          <p v-else class="empty">Belum ada tulisan di kategori ini.</p>
        </div>
      </section>

      <!-- ================= GALERI ================= -->
      <section v-if="gallery.length" id="galeri" class="section section-tint">
        <div class="container">
          <div class="section-head reveal">
            <span class="mono-label">GALERI</span>
            <h2>Suasana belajar di TSM.</h2>
          </div>
          <div class="gal-grid">
            <button v-for="g in gallery" :key="g.id" type="button" class="gal-item reveal" @click="lightbox = g">
              <img :src="img(g.file)" :alt="g.title || g.caption || 'Galeri'" loading="lazy" />
              <span>{{ g.album || g.title }}</span>
            </button>
          </div>
        </div>
      </section>

      <!-- ================= GURU ================= -->
      <section v-if="teachers.length" id="guru" class="section">
        <div class="container">
          <div class="section-head reveal">
            <span class="mono-label">GURU &amp; INSTRUKTUR</span>
            <h2>Dibimbing praktisi berpengalaman.</h2>
          </div>
          <div class="teach-grid">
            <article v-for="t in teachers" :key="t.id" class="teach-card reveal">
              <img v-if="t.photo" :src="img(t.photo)" :alt="t.name" loading="lazy" />
              <h3>{{ t.name }}</h3>
              <span class="mono-label">{{ t.position }}</span>
              <p>{{ t.subject }}</p>
            </article>
          </div>
        </div>
      </section>

      <!-- ================= KARIER ================= -->
      <section id="karier" class="section section-tint">
        <div class="container karier-wrap">
          <div class="karier-intro reveal">
            <span class="mono-label">PROSPEK KARIER</span>
            <h2>Lulus, lalu ke mana?</h2>
            <p>Keahlian otomotif roda dua dibutuhkan di banyak jalur, baik bekerja, membuka usaha, maupun melanjutkan studi.</p>
          </div>
          <div class="karier-grid">
            <article v-for="c in karier" :key="c.no" class="karier-card reveal">
              <span class="karier-no">{{ c.no }}</span>
              <div><h3>{{ c.title }}</h3><p>{{ c.desc }}</p></div>
            </article>
          </div>
        </div>
      </section>

      <!-- ================= UMPAN BALIK LULUSAN ================= -->
      <section id="alumni" class="section">
        <div class="container">
          <div class="section-head split reveal">
            <div>
              <span class="mono-label">UMPAN BALIK LULUSAN</span>
              <h2>Kata alumni tentang TSM.</h2>
            </div>
            <button type="button" class="btn btn-outline btn-sm" @click="showTesti = !showTesti">{{ showTesti ? 'Tutup formulir' : 'Kirim testimoni' }}</button>
          </div>

          <p v-if="flash.success && !showTesti" class="form-ok" role="status">{{ flash.success }}</p>
          <form v-if="showTesti" class="lp-form testi-form" @submit.prevent="submitTesti">
            <div class="form-grid">
              <label>Nama lengkap<input v-model="testiForm.name" required maxlength="150" /></label>
              <label>Tahun lulus<input v-model="testiForm.graduation_year" type="number" min="2000" :max="new Date().getFullYear()" required /></label>
              <label>Pekerjaan<input v-model="testiForm.occupation" maxlength="150" /></label>
              <label>Tempat kerja / kuliah<input v-model="testiForm.workplace" maxlength="150" /></label>
              <label class="full">Pesan &amp; kesan<textarea v-model="testiForm.message" rows="3" required maxlength="1000"></textarea></label>
              <div class="full rating-pick">
                <span>Penilaian:</span>
                <button v-for="n in 5" :key="n" type="button" :class="{ on: n <= testiForm.rating }" :aria-label="`${n} bintang`" @click="testiForm.rating = n">★</button>
              </div>
            </div>
            <p v-for="(e, k) in testiForm.errors" :key="k" class="form-err">{{ e }}</p>
            <button type="submit" class="btn btn-primary btn-sm" :disabled="testiForm.processing">Kirim testimoni</button>
          </form>

          <div class="testi-grid">
            <figure v-for="t in testimonials" :key="t.id" class="testi-card reveal">
              <div class="stars" :aria-label="`${t.rating} dari 5 bintang`">
                <span v-for="n in 5" :key="n" :class="{ on: n <= t.rating }">★</span>
              </div>
              <blockquote>“{{ t.message }}”</blockquote>
              <figcaption>
                <img v-if="t.photo" :src="img(t.photo)" :alt="t.name" loading="lazy" />
                <span v-else class="avatar">{{ t.name?.[0] }}</span>
                <span><b>{{ t.name }}</b><small>Lulusan {{ t.graduation_year }}<template v-if="t.occupation"> · {{ t.occupation }}</template><template v-if="t.workplace">, {{ t.workplace }}</template></small></span>
              </figcaption>
            </figure>
          </div>
        </div>
      </section>

      <!-- ================= FAQ ================= -->
      <section id="faq" class="section section-tint">
        <div class="container faq-wrap">
          <div class="faq-intro reveal">
            <span class="mono-label">FAQ</span>
            <h2>Pertanyaan yang sering diajukan.</h2>
          </div>
          <div class="faq-list reveal">
            <div v-for="(f, i) in faqs" :key="i" class="faq-item" :class="{ open: openFaq === i }">
              <button type="button" :aria-expanded="openFaq === i" @click="openFaq = openFaq === i ? -1 : i">
                {{ f.q }}<span aria-hidden="true">{{ openFaq === i ? '−' : '+' }}</span>
              </button>
              <p v-show="openFaq === i">{{ f.a }}</p>
            </div>
          </div>
        </div>
      </section>

      <!-- ================= CTA ================= -->
      <section id="spmb" class="section section-cta">
        <div class="container">
          <div class="cta-panel grid-dark reveal">
            <div class="cta-text">
              <span class="mono-label accent">SPMB — SISTEM PENERIMAAN MURID BARU</span>
              <h2>Siap jadi <span class="accent">mekanik profesional?</span></h2>
              <p>{{ s.spmb_open === '1' ? (s.spmb_info || 'Pendaftaran SPMB sudah dibuka.') : 'Pendaftaran SPMB belum dibuka. Pantau terus pengumuman terbaru dari kami.' }} Daftar online sekarang atau tanya panitia lewat WhatsApp.</p>
            </div>
            <div class="cta-actions">
              <a href="/spmb" target="_blank" rel="noopener" class="btn btn-primary btn-lg">Daftar SPMB</a>
              <a :href="waLink" class="btn btn-ghost btn-lg" :target="waNumber ? '_blank' : null" rel="noopener">Tanya via WhatsApp</a>
            </div>
          </div>
        </div>
      </section>

      <!-- ================= KONTAK ================= -->
      <section id="kontak" class="section">
        <div class="container contact-wrap">
          <div class="contact-info reveal">
            <span class="mono-label">HUBUNGI KAMI</span>
            <h2>Kunjungi atau hubungi kami.</h2>
            <ul>
              <li><b>Alamat</b>{{ address }}</li>
              <li v-if="s.phone"><b>Telepon</b>{{ s.phone }}</li>
              <li v-if="s.email"><b>Email</b><a :href="`mailto:${s.email}`">{{ s.email }}</a></li>
            </ul>
            <div v-if="s.maps_embed" class="map" v-html="s.maps_embed"></div>
          </div>
          <form class="lp-form contact-form reveal" @submit.prevent="submitContact">
            <div class="form-grid">
              <label>Nama<input v-model="contactForm.name" required maxlength="150" /></label>
              <label>No. WhatsApp<input v-model="contactForm.phone" type="tel" maxlength="20" /></label>
              <label class="full">Email<input v-model="contactForm.email" type="email" maxlength="150" /></label>
              <label class="full">Subjek<input v-model="contactForm.subject" maxlength="200" /></label>
              <label class="full">Pesan<textarea v-model="contactForm.message" rows="5" required maxlength="2000"></textarea></label>
            </div>
            <p v-for="(e, k) in contactForm.errors" :key="k" class="form-err">{{ e }}</p>
            <p v-if="contactForm.wasSuccessful" class="form-ok" role="status">Pesan terkirim. Terima kasih!</p>
            <button type="submit" class="btn btn-primary btn-sm" :disabled="contactForm.processing">Kirim pesan</button>
          </form>
        </div>
      </section>
    </main>

    <!-- ================= FOOTER ================= -->
    <footer class="footer">
      <div class="container">
        <div class="footer-top">
          <div class="footer-brand">
            <div class="footer-logo">
              <svg width="30" height="30" viewBox="0 0 40 40" fill="none" aria-hidden="true">
                <circle cx="20" cy="20" r="17" stroke="currentColor" stroke-width="2"/>
                <circle cx="20" cy="20" r="6" fill="currentColor"/>
                <path d="M20 3v8M20 29v8M3 20h8M29 20h8M8 8l5.5 5.5M26.5 26.5L32 32M32 8l-5.5 5.5M13.5 26.5L8 32" stroke="currentColor" stroke-width="1.6"/>
              </svg>
              <span>{{ s.department_name || 'Teknik Sepeda Motor' }}</span>
            </div>
            <p>{{ schoolName }}<br />{{ address }}</p>
            <div v-if="socials.length" class="socials">
              <a v-for="so in socials" :key="so.key" :href="so.url" target="_blank" rel="noopener">{{ so.label }}</a>
            </div>
          </div>
          <div class="footer-cols">
            <div>
              <h4 class="mono-label muted">JURUSAN</h4>
              <ul>
                <li><a href="#kompetensi">Kompetensi</a></li>
                <li><a href="#program">Program Unggulan</a></li>
                <li><a href="#sarana">Sarana</a></li>
                <li><a href="#prestasi">Prestasi</a></li>
              </ul>
            </div>
            <div>
              <h4 class="mono-label muted">INFORMASI</h4>
              <ul>
                <li><Link href="/berita">Semua Berita</Link></li>
                <li v-for="c in categories.slice(0, 4)" :key="c.id"><Link :href="`/berita?kategori=${c.slug}`">{{ c.name }}</Link></li>
                <li><a href="/spmb" target="_blank" rel="noopener">SPMB</a></li>
              </ul>
            </div>
            <div>
              <h4 class="mono-label muted">KONTAK</h4>
              <ul>
                <li v-if="s.phone">{{ s.phone }}</li>
                <li v-if="s.email">{{ s.email }}</li>
              </ul>
            </div>
          </div>
        </div>
        <div class="footer-bottom">{{ s.footer_text || `© ${new Date().getFullYear()} Jurusan Teknik Sepeda Motor — ${schoolName}` }}</div>
      </div>
    </footer>

    <!-- Lightbox galeri -->
    <div v-if="lightbox" class="lightbox" role="dialog" aria-modal="true" @click.self="lightbox = null">
      <button type="button" class="lb-close" aria-label="Tutup" @click="lightbox = null">×</button>
      <img :src="img(lightbox.file)" :alt="lightbox.title" />
      <p>{{ lightbox.caption || lightbox.title }}</p>
    </div>

    <!-- Tombol WA melayang -->
    <a v-if="waNumber" :href="waLink" class="wa-float" target="_blank" rel="noopener" aria-label="Chat WhatsApp">
      <svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm5.3 14.1c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .2-3.3-.7-2.8-1.1-4.5-4-4.7-4.2-.1-.2-1.1-1.5-1.1-2.9s.7-2.1 1-2.4c.3-.3.6-.3.8-.3h.6c.2 0 .4 0 .6.5l.9 2.1c.1.2.1.4 0 .5l-.4.6-.4.4c-.1.1-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.2 1 2.1 1.3 2.4 1.5.3.1.5.1.6-.1l.9-1c.2-.3.4-.2.6-.1l2 .9c.3.1.5.2.5.3.1.2.1.7-.2 1.4z"/></svg>
    </a>
  </div>
</template>
