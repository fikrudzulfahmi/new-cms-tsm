<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps(['stats', 'monthlyVisits', 'topPosts', 'categoryStats'])
</script>

<template>
  <AdminLayout>
    <title>Laporan Periodik & Analisis - Admin TSM</title>
    <div class="space-y-6">
      <div>
        <h1 class="font-display text-[28px] font-bold text-navy tracking-[-0.5px]"><span class="block font-mono text-[11px] font-normal tracking-[2px] uppercase text-label mb-1">Panel Kendali · TSM</span>Laporan Periodik & Analisis</h1>
        <p class="text-slate-500 text-sm">Rekap kunjungan, performa berita, dan statistik interaksi website</p>
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
          <div class="text-slate-500 text-xs font-medium">Total Pendaftar SPMB</div>
          <div class="text-3xl font-bold text-slate-800 mt-1">{{ stats.total_ppdb }}</div>
          <div class="text-xs text-purple-600 mt-1">Calon siswa baru</div>
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
