<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
  status: { type: Number, default: 404 },
  label: { type: String, default: 'Halaman tidak ditemukan' },
  heading: { type: String, default: 'Halaman yang Anda cari tidak ada' },
  message: { type: String, default: 'Alamatnya mungkin salah ketik, atau halaman sudah dipindahkan.' },
})
</script>

<template>
  <div class="min-h-screen grid-dark flex flex-col">
    <header class="w-full max-w-6xl mx-auto px-5 sm:px-8 py-6 flex items-center justify-between">
      <Link href="/" class="flex items-center gap-3 no-underline text-white">
        <span class="w-11 h-11 rounded-full border border-accent/50 flex items-center justify-center font-mono text-[11px] tracking-[1.5px] text-accent">TSM</span>
        <span class="font-display font-bold leading-tight">
          Teknik Sepeda Motor
          <small class="block font-mono text-[10px] tracking-[2px] uppercase text-on-navy-subtle font-normal mt-0.5">SMKS Islam 1 Kota Blitar</small>
        </span>
      </Link>
      <Link href="/berita" class="hidden sm:inline-flex text-sm text-on-navy-muted hover:text-accent transition-colors no-underline">Berita</Link>
    </header>

    <main class="flex-1 flex items-center justify-center px-5 py-10">
      <section
        class="w-full max-w-2xl rounded-[28px] border border-accent/20 bg-white/[0.04] backdrop-blur px-6 sm:px-11 py-11 text-center relative overflow-hidden"
        role="alert"
      >
        <div class="relative inline-block">
          <svg class="gear absolute -right-10 -top-6 w-24 h-24 opacity-25" viewBox="0 0 48 48" fill="none" aria-hidden="true">
            <circle cx="24" cy="24" r="10" stroke="#7CC8FF" stroke-width="1.6" />
            <circle cx="24" cy="24" r="3.2" stroke="#7CC8FF" stroke-width="1.6" />
            <g stroke="#7CC8FF" stroke-width="1.6" stroke-linecap="round">
              <path d="M24 4v6M24 38v6M4 24h6M38 24h6M9.9 9.9l4.2 4.2M33.9 33.9l4.2 4.2M38.1 9.9l-4.2 4.2M14.1 33.9l-4.2 4.2" />
            </g>
          </svg>
          <div class="code font-display font-extrabold">{{ status }}</div>
        </div>

        <p class="font-mono text-[11px] tracking-[2.4px] uppercase text-accent mt-1 mb-3">{{ label }}</p>
        <h1 class="font-display text-white text-2xl sm:text-3xl font-bold leading-tight mb-3">{{ heading }}</h1>
        <p class="text-on-navy-muted text-[15px] leading-relaxed max-w-md mx-auto mb-8">{{ message }}</p>

        <div class="flex flex-col sm:flex-row gap-3 justify-center mb-6">
          <Link href="/" class="btn-primary justify-center">Kembali ke Beranda</Link>
          <button
            type="button"
            class="px-6 py-3 rounded-full border border-on-navy-muted/35 text-ice text-sm font-bold hover:border-accent hover:text-accent transition-colors"
            @click="() => (window.history.length > 1 ? window.history.back() : (window.location.href = '/'))"
          >
            Halaman Sebelumnya
          </button>
        </div>

        <p class="font-mono text-[11px] tracking-wide text-on-navy-subtle">
          Kode galat <span class="text-ice">{{ status }}</span> &middot; bila terus terjadi, hubungi pengelola website.
        </p>
      </section>
    </main>

    <footer class="text-center text-xs text-on-navy-subtle pb-6">
      &copy; {{ new Date().getFullYear() }} Teknik Sepeda Motor SMKS Islam 1 Kota Blitar
    </footer>
  </div>
</template>

<style scoped>
.code {
  font-size: clamp(72px, 15vw, 128px);
  line-height: 1;
  letter-spacing: -6px;
  background: linear-gradient(180deg, #ffffff 15%, var(--color-accent, #7cc8ff) 95%);
  -webkit-background-clip: text;
  background-clip: text;
  color: transparent;
}
.gear {
  animation: err-spin 26s linear infinite;
}
@keyframes err-spin {
  to { transform: rotate(360deg); }
}
@media (prefers-reduced-motion: reduce) {
  .gear { animation: none; }
}
</style>
