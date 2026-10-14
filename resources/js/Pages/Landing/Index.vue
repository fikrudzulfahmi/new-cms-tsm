<script setup>
import { Head } from '@inertiajs/vue3'
import { onMounted, onBeforeUnmount, ref } from 'vue'
import GearRatio from '@/Components/Landing/GearRatio.vue'
import { CONFIG } from './content.js'
import '../../../css/landing.css'

const menuOpen = ref(false)
const scrolled = ref(false)
const wa = ref('')
const waError = ref('')
const waSuccess = ref('')

const kompetensi = [
  { icon: 'mesin', dark: false, title: 'Mesin (Engine)', desc: 'Perawatan berkala, overhaul kepala silinder, piston, dan sistem bahan bakar motor 2 dan 4 tak.' },
  { icon: 'chip', dark: true, title: 'Sistem Injeksi (EFI)', desc: 'Diagnosis sensor dan ECU menggunakan scanner, serta penanganan kerusakan sistem injeksi modern.' },
  { icon: 'petir', dark: false, title: 'Kelistrikan', desc: 'Sistem pengapian, pengisian, starter, dan penerangan, termasuk dasar motor listrik.' },
  { icon: 'roda', dark: false, title: 'Sasis & Pemindah Tenaga', desc: 'Rem, suspensi, roda, kopling, transmisi manual dan CVT matic.' },
]

const alur = [
  { label: 'KELAS X', title: 'Dasar Otomotif', desc: 'Keselamatan kerja, penggunaan alat ukur dan perkakas, serta prinsip kerja mesin.', dark: false },
  { label: 'KELAS XI', title: 'Praktik Bengkel', desc: 'Servis mesin, kelistrikan, sasis, dan injeksi pada unit motor nyata di bengkel sekolah.', dark: false },
  { label: 'KELAS XII', title: 'PKL & Uji Kompetensi', desc: 'Praktik kerja lapangan di mitra industri, lalu uji kompetensi untuk sertifikat keahlian.', dark: true },
]

const karier = [
  { no: '01', title: 'Mekanik Bengkel Resmi', desc: 'Di dealer dan AHASS/bengkel resmi merek.' },
  { no: '02', title: 'Wirausaha Bengkel', desc: 'Membuka bengkel atau usaha suku cadang sendiri.' },
  { no: '03', title: 'Teknisi Industri', desc: 'Quality control dan perakitan di pabrik otomotif.' },
  { no: '04', title: 'Kuliah Lanjut', desc: 'Ke D3/D4/S1 Teknik Mesin atau Otomotif.' },
]

const navLinks = [
  { href: '#kompetensi', label: 'Kompetensi' },
  { href: '#alur', label: 'Alur Belajar' },
  { href: '#fasilitas', label: 'Fasilitas' },
  { href: '#karier', label: 'Prospek Karier' },
]

function submitWa() {
  waSuccess.value = ''
  const clean = wa.value.replace(/[\s-]/g, '')
  if (!/^(\+62|62|0)8[0-9]{7,12}$/.test(clean)) {
    waError.value = 'Nomor WhatsApp tidak valid. Contoh: 0812-3456-7890'
    return
  }
  waError.value = ''
  const admin = CONFIG.nomorAdmin
  if (/^\d+$/.test(admin)) {
    const text = encodeURIComponent(`Halo, saya ingin mendaftar Jurusan TSM. Nomor WA saya: ${clean}`)
    window.open(`https://wa.me/${admin}?text=${text}`, '_blank', 'noopener')
  }
  waSuccess.value = 'Terima kasih! Tim pendaftaran akan segera menghubungi kamu.'
  wa.value = ''
}

let io = null
function onScroll() { scrolled.value = window.scrollY > 16 }

onMounted(() => {
  onScroll()
  window.addEventListener('scroll', onScroll, { passive: true })
  const reduce = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches
  const els = document.querySelectorAll('.lp .reveal')
  if (reduce || !('IntersectionObserver' in window)) {
    els.forEach(el => el.classList.add('is-in'))
    return
  }
  io = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (!entry.isIntersecting) return
      const siblings = [...entry.target.parentElement.children].filter(c => c.classList.contains('reveal'))
      entry.target.style.transitionDelay = `${Math.max(0, siblings.indexOf(entry.target)) * 60}ms`
      entry.target.classList.add('is-in')
      io.unobserve(entry.target)
    })
  }, { threshold: 0.12 })
  els.forEach(el => io.observe(el))
})

onBeforeUnmount(() => {
  window.removeEventListener('scroll', onScroll)
  io?.disconnect()
})
</script>

<template>
  <Head>
    <title>Jurusan Teknik Sepeda Motor</title>
    <meta head-key="description" name="description" :content="`Jurusan Teknik Sepeda Motor ${CONFIG.namaSmk}: belajar mesin, injeksi, kelistrikan, dan sasis di bengkel praktik berstandar industri. Pendaftaran ${CONFIG.tahunAjaran} dibuka.`" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Manrope:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet" />
  </Head>

  <div class="lp">
    <!-- ================= HERO ================= -->
    <header class="hero grid-dark">
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
              <span class="brand-sub">{{ CONFIG.namaSmk }}</span>
            </span>
          </a>

          <ul class="nav-links">
            <li v-for="l in navLinks" :key="l.href"><a :href="l.href">{{ l.label }}</a></li>
          </ul>

          <div class="nav-actions">
            <a href="#kontak" class="btn btn-ghost btn-nav">Kontak</a>
            <a href="#daftar" class="btn btn-primary btn-nav">Daftar Sekarang</a>
          </div>

          <button
            type="button"
            class="nav-burger"
            :aria-label="menuOpen ? 'Tutup menu' : 'Buka menu'"
            :aria-expanded="menuOpen"
            aria-controls="mobile-menu"
            @click="menuOpen = !menuOpen"
          >
            <span></span><span></span><span></span>
          </button>
        </div>

        <div v-show="menuOpen" id="mobile-menu" class="mobile-menu">
          <div class="container">
            <a v-for="l in navLinks" :key="l.href" :href="l.href" @click="menuOpen = false">{{ l.label }}</a>
            <div class="mobile-actions">
              <a href="#kontak" class="btn btn-ghost" @click="menuOpen = false">Kontak</a>
              <a href="#daftar" class="btn btn-primary" @click="menuOpen = false">Daftar Sekarang</a>
            </div>
          </div>
        </div>
      </nav>

      <div class="container hero-content">
        <div class="hero-text">
          <span class="badge"><span class="badge-dot" aria-hidden="true"></span>PENDAFTARAN {{ CONFIG.tahunAjaran }} DIBUKA</span>
          <h1>Teknik Sepeda Motor. <span class="accent">Kuasai mesinnya, siap kerja.</span></h1>
          <p class="lead">
            Belajar langsung di bengkel praktik, dari mesin konvensional hingga sistem injeksi dan motor listrik.
            Lulus dengan keterampilan yang dicari bengkel resmi dan industri otomotif.
          </p>
          <div class="hero-buttons">
            <a href="#daftar" class="btn btn-primary btn-lg">
              Daftar Sekarang
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
            </a>
            <a href="#kompetensi" class="btn btn-ghost btn-lg">Lihat Kompetensi</a>
          </div>
          <dl class="hero-stats">
            <div><dt>Siswa aktif</dt><dd>{{ CONFIG.stats.siswa }}</dd></div>
            <div><dt>Mitra industri</dt><dd>{{ CONFIG.stats.mitra }}</dd></div>
            <div><dt>Lulusan terserap kerja</dt><dd>{{ CONFIG.stats.serapan }}</dd></div>
          </dl>
        </div>
        <div class="hero-gear">
          <GearRatio />
        </div>
      </div>
    </header>

    <main>
      <!-- ================= MITRA ================= -->
      <section class="mitra" aria-label="Mitra industri">
        <div class="container mitra-inner">
          <span class="mono-label muted">MITRA INDUSTRI &amp; DUDI</span>
          <ul class="mitra-logos">
            <li v-for="(m, i) in CONFIG.mitra" :key="i" class="mitra-box">{{ m }}</li>
          </ul>
        </div>
      </section>

      <!-- ================= KOMPETENSI ================= -->
      <section id="kompetensi" class="section">
        <div class="container">
          <div class="section-head split reveal">
            <div>
              <span class="mono-label">01 — KOMPETENSI KEAHLIAN</span>
              <h2>Apa saja yang kamu pelajari di TSM?</h2>
            </div>
            <p class="head-aside">Materi disusun bersama mitra industri, dengan porsi praktik yang besar di bengkel sekolah.</p>
          </div>
          <div class="komp-grid">
            <article v-for="k in kompetensi" :key="k.title" class="komp-card reveal" :class="{ dark: k.dark }">
              <div class="icon-box">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <template v-if="k.icon === 'mesin'"><rect x="7" y="3" width="10" height="8" rx="1"/><path d="M9 11v4h6v-4M12 15v6M8 21h8"/></template>
                  <template v-else-if="k.icon === 'chip'"><rect x="4" y="4" width="16" height="16" rx="2"/><rect x="9" y="9" width="6" height="6"/><path d="M9 2v2M15 2v2M9 20v2M15 20v2M2 9h2M2 15h2M20 9h2M20 15h2"/></template>
                  <template v-else-if="k.icon === 'petir'"><path d="M13 2L4 14h7l-1 8 9-12h-7z"/></template>
                  <template v-else><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="2.5"/><path d="M12 3v6.5M12 14.5V21M3 12h6.5M14.5 12H21"/></template>
                </svg>
              </div>
              <h3>{{ k.title }}</h3>
              <p>{{ k.desc }}</p>
            </article>
          </div>
        </div>
      </section>

      <!-- ================= ALUR ================= -->
      <section id="alur" class="section grid-light">
        <div class="container">
          <div class="section-head center reveal">
            <span class="mono-label">02 — ALUR BELAJAR</span>
            <h2 class="h2-narrow">Tiga tahun, dari dasar hingga siap terjun ke bengkel.</h2>
          </div>
          <div class="alur-grid">
            <span class="alur-line" aria-hidden="true"></span>
            <article v-for="s in alur" :key="s.label" class="alur-card reveal" :class="{ dark: s.dark }">
              <span class="mono-label alur-label">{{ s.label }}</span>
              <h3>{{ s.title }}</h3>
              <p>{{ s.desc }}</p>
            </article>
          </div>
        </div>
      </section>

      <!-- ================= FASILITAS ================= -->
      <section id="fasilitas" class="section">
        <div class="container">
          <div class="section-head reveal">
            <span class="mono-label">03 — FASILITAS</span>
            <h2>Bengkel praktik berstandar industri.</h2>
          </div>
          <div class="fas-panel grid-dark reveal">
            <div class="fas-card fas-big placeholder">
              <span class="mono-label accent">[FOTO BENGKEL PRAKTIK]</span>
              <h3>Bengkel Praktik Utama</h3>
              <p>{{ CONFIG.fasilitas.stall }} stall servis dengan {{ CONFIG.fasilitas.unit }} unit motor praktik.</p>
            </div>
            <div class="fas-col">
              <div class="fas-card fas-scan">
                <span class="mono-label accent">[FOTO]</span>
                <h3>Alat Diagnosis &amp; Scanner</h3>
              </div>
              <div class="fas-card fas-lab">
                <span class="mono-label">[FOTO]</span>
                <h3>Lab Kelistrikan &amp; Motor Listrik</h3>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ================= KARIER ================= -->
      <section id="karier" class="section">
        <div class="container karier-wrap">
          <div class="karier-intro reveal">
            <span class="mono-label">04 — PROSPEK KARIER</span>
            <h2>Lulus, lalu ke mana?</h2>
            <p>Keahlian otomotif roda dua dibutuhkan di banyak jalur, baik bekerja, membuka usaha, maupun melanjutkan studi.</p>
          </div>
          <div class="karier-grid">
            <article v-for="c in karier" :key="c.no" class="karier-card reveal">
              <span class="karier-no">{{ c.no }}</span>
              <div>
                <h3>{{ c.title }}</h3>
                <p>{{ c.desc }}</p>
              </div>
            </article>
          </div>
        </div>
      </section>

      <!-- ================= CTA ================= -->
      <section id="daftar" class="section section-cta">
        <div class="container">
          <div class="cta-panel grid-dark reveal">
            <div class="cta-text">
              <h2>Siap jadi <span class="accent">mekanik profesional?</span></h2>
              <p>Tinggalkan nomor WhatsApp, tim pendaftaran kami akan menghubungi kamu. Pendaftaran ditutup {{ CONFIG.tanggalTutup }}.</p>
            </div>
            <form class="cta-form" novalidate @submit.prevent="submitWa">
              <div class="cta-row">
                <label for="cta-wa" class="sr-only">Nomor WhatsApp</label>
                <input
                  id="cta-wa"
                  v-model="wa"
                  type="tel"
                  inputmode="tel"
                  autocomplete="tel"
                  placeholder="08xx-xxxx-xxxx"
                  :aria-invalid="!!waError"
                  aria-describedby="cta-wa-msg"
                />
                <button type="submit" class="btn btn-primary btn-lg">Daftar Sekarang</button>
              </div>
              <p id="cta-wa-msg" class="cta-msg">
                <span v-if="waError" role="alert" class="err">{{ waError }}</span>
                <span v-else-if="waSuccess" role="status" class="ok">{{ waSuccess }}</span>
              </p>
            </form>
          </div>
        </div>
      </section>
    </main>

    <!-- ================= FOOTER ================= -->
    <footer id="kontak" class="footer">
      <div class="container">
        <div class="footer-top">
          <div class="footer-brand">
            <div class="footer-logo">
              <svg width="30" height="30" viewBox="0 0 40 40" fill="none" aria-hidden="true">
                <circle cx="20" cy="20" r="17" stroke="currentColor" stroke-width="2"/>
                <circle cx="20" cy="20" r="6" fill="currentColor"/>
                <path d="M20 3v8M20 29v8M3 20h8M29 20h8M8 8l5.5 5.5M26.5 26.5L32 32M32 8l-5.5 5.5M13.5 26.5L8 32" stroke="currentColor" stroke-width="1.6"/>
              </svg>
              <span>Teknik Sepeda Motor</span>
            </div>
            <p>{{ CONFIG.namaSmk }}<br />{{ CONFIG.alamat }}</p>
          </div>
          <div class="footer-cols">
            <div>
              <h4 class="mono-label muted">JURUSAN</h4>
              <ul>
                <li><a href="#kompetensi">Kompetensi</a></li>
                <li><a href="#fasilitas">Fasilitas</a></li>
                <li><a href="#karier">Prospek Karier</a></li>
              </ul>
            </div>
            <div>
              <h4 class="mono-label muted">KONTAK</h4>
              <ul>
                <li>{{ CONFIG.telepon }}</li>
                <li>{{ CONFIG.email }}</li>
                <li>{{ CONFIG.instagram }}</li>
              </ul>
            </div>
          </div>
        </div>
        <div class="footer-bottom">© 2026 Jurusan Teknik Sepeda Motor — {{ CONFIG.namaSmk }}</div>
      </div>
    </footer>
  </div>
</template>
