<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps(['stats', 'topPosts', 'categoryStats', 'from', 'to', 'summary'])

const from = ref(props.from || '')
const to = ref(props.to || '')

const iso = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`

const printUrl = computed(() => {
  const params = new URLSearchParams()
  if (from.value) params.set('from', from.value)
  if (to.value) params.set('to', to.value)
  const q = params.toString()
  return `/admin/laporan/cetak${q ? '?' + q : ''}`
})

const apply = () => router.get('/admin/laporan', { from: from.value || undefined, to: to.value || undefined }, { preserveState: true, preserveScroll: true })

const preset = (kind) => {
  const now = new Date()
  if (kind === 'bulan-ini') {
    from.value = iso(new Date(now.getFullYear(), now.getMonth(), 1))
    to.value = iso(now)
  } else if (kind === 'bulan-lalu') {
    from.value = iso(new Date(now.getFullYear(), now.getMonth() - 1, 1))
    to.value = iso(new Date(now.getFullYear(), now.getMonth(), 0))
  } else {
    const d = new Date(now)
    d.setDate(d.getDate() - 29)
    from.value = iso(d)
    to.value = iso(now)
  }
  apply()
}
</script>

<template>
  <AdminLayout>
    <title>Laporan Periodik & Analisis - Admin TSM</title>
    <div class="space-y-6">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="font-display text-[28px] font-bold text-navy tracking-[-0.5px]"><span class="block font-mono text-[11px] font-normal tracking-[2px] uppercase text-label mb-1">Panel Kendali · TSM</span>Laporan Periodik &amp; Analisis</h1>
          <p class="text-slate-500 text-sm">Rekap kunjungan, performa berita, dan pembaruan konten — siap dicetak untuk atasan</p>
        </div>
        <a :href="printUrl" target="_blank" rel="noopener" class="btn-primary text-sm flex items-center gap-2">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V3h12v6M6 18H4a2 2 0 01-2-2v-4a2 2 0 012-2h16a2 2 0 012 2v4a2 2 0 01-2 2h-2M6 14h12v7H6z"/></svg>
          Cetak / Simpan PDF
        </a>
      </div>

      <!-- Filter periode -->
      <div class="card p-5 flex flex-wrap items-end gap-3">
        <div>
          <label class="form-label" for="lap-from">Dari tanggal</label>
          <input id="lap-from" v-model="from" type="date" class="form-input text-sm" />
        </div>
        <div>
          <label class="form-label" for="lap-to">Sampai tanggal</label>
          <input id="lap-to" v-model="to" type="date" class="form-input text-sm" />
        </div>
        <button type="button" @click="apply" class="btn-primary text-sm px-5 py-2.5">Terapkan</button>
        <div class="flex flex-wrap gap-2 sm:ml-auto">
          <button type="button" @click="preset('bulan-ini')" class="text-xs font-semibold px-3 py-2 rounded-full border border-line text-slate-600 hover:border-navy hover:text-navy">Bulan ini</button>
          <button type="button" @click="preset('bulan-lalu')" class="text-xs font-semibold px-3 py-2 rounded-full border border-line text-slate-600 hover:border-navy hover:text-navy">Bulan lalu</button>
          <button type="button" @click="preset('30hari')" class="text-xs font-semibold px-3 py-2 rounded-full border border-line text-slate-600 hover:border-navy hover:text-navy">30 hari</button>
        </div>
      </div>

      <div v-if="summary" class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="card p-5">
          <div class="text-slate-500 text-xs font-medium">Kunjungan periode</div>
          <div class="text-2xl font-bold text-slate-800 mt-1">{{ summary.total_visits }}</div>
          <div class="text-xs text-slate-500 mt-1">{{ summary.unique_visits }} pengunjung unik</div>
        </div>
        <div class="card p-5">
          <div class="text-slate-500 text-xs font-medium">Berita terbit periode</div>
          <div class="text-2xl font-bold text-slate-800 mt-1">{{ summary.total_published }}</div>
          <div class="text-xs text-slate-500 mt-1">{{ summary.total_posts_created }} berita dibuat (termasuk draf)</div>
        </div>
        <div class="card p-5 sm:col-span-2">
          <div class="text-slate-500 text-xs font-medium">Cetak laporan</div>
          <p class="text-sm text-slate-600 mt-1">Tombol <b>Cetak / Simpan PDF</b> membuka halaman laporan resmi (A4). Di dialog cetak, pilih <b>Save as PDF</b> lalu kirim ke atasan.</p>
        </div>
      </div>

      <!-- Quick Metrics -->
      <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="card p-6">
          <div class="text-slate-500 text-xs font-medium">Total Pengunjung</div>
          <div class="text-3xl font-bold text-slate-800 mt-1">{{ stats.total_visits }}</div>
          <div class="text-xs text-green-600 mt-1">↑ Trafik aktif real-time</div>
        </div>
        <div class="card p-6">
          <div class="text-slate-500 text-xs font-medium">Total Berita</div>
          <div class="text-3xl font-bold text-slate-800 mt-1">{{ stats.total_posts }}</div>
          <div class="text-xs text-blue-600 mt-1">Artikel & pengumuman terbit</div>
        </div>
        <div class="card p-6">
          <div class="text-slate-500 text-xs font-medium">Total Komentar & Interaksi</div>
          <div class="text-3xl font-bold text-slate-800 mt-1">{{ stats.total_comments }}</div>
          <div class="text-xs text-amber-600 mt-1">Tanggapan & diskusi</div>
        </div>
        <div class="card p-6">
          <div class="text-slate-500 text-xs font-medium">Total Pesan Masuk</div>
          <div class="text-3xl font-bold text-slate-800 mt-1">{{ stats.total_messages }}</div>
          <div class="text-xs text-purple-600 mt-1">Dari formulir kontak</div>
        </div>
      </div>

      <!-- Detailed Lists -->
      <div class="grid lg:grid-cols-2 gap-6">
        <!-- Top Posts -->
        <div class="card p-6">
          <h2 class="font-bold text-slate-800 mb-4">Berita Paling Populer (Berdasarkan Views)</h2>
          <div class="space-y-3">
            <div v-for="(p, i) in topPosts" :key="p.id" class="flex items-center justify-between p-3 rounded-xl bg-slate-50">
              <div class="flex items-center gap-3 min-w-0">
                <span class="w-6 h-6 rounded-full bg-blue-100 text-blue-900 font-bold text-xs flex items-center justify-center flex-shrink-0">{{ i+1 }}</span>
                <span class="font-semibold text-sm text-slate-800 truncate">{{ p.title }}</span>
              </div>
              <span class="badge bg-blue-50 text-blue-800 text-xs flex-shrink-0">{{ p.views_count }} views</span>
            </div>
          </div>
        </div>

        <!-- Category Breakdown -->
        <div class="card p-6">
          <h2 class="font-bold text-slate-800 mb-4">Distribusi Berita Per Kategori</h2>
          <div class="space-y-3">
            <div v-for="c in categoryStats" :key="c.id" class="flex items-center justify-between p-3 rounded-xl bg-slate-50">
              <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full" :style="`background:${c.color}`"></span>
                <span class="font-medium text-sm text-slate-800">{{ c.name }}</span>
              </div>
              <span class="font-bold text-sm text-blue-900">{{ c.posts_count }} artikel</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
